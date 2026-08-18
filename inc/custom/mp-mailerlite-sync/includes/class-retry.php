<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retry for failed live-event syncs.
 *
 * When a MemberPress event fails to sync (MailerLite down, timeout, ...), a
 * retry is scheduled with backoff. The retry does not replay the original
 * event — the member's state may have changed since — it converges the user
 * to their current desired state via MPMLS_Sync_Engine::sync_user(). After
 * the attempts are exhausted the nightly auto-sync remains the safety net.
 */
class MPMLS_Retry {

	const HOOK = 'mpmls_retry_user_sync';

	/** Backoff per attempt: 5 min, 30 min, 2 h. */
	const DELAYS = array( 300, 1800, 7200 );

	public function register() {
		add_action( self::HOOK, array( $this, 'run' ), 10, 2 );
	}

	public static function schedule( $user_id, $attempt = 0 ) {
		$user_id = (int) $user_id;
		$attempt = (int) $attempt;

		if ( ! $user_id || $attempt >= count( self::DELAYS ) ) {
			return false;
		}

		// Same guard as the nightly sync: no cron writes from staging/local.
		if ( ! class_exists( 'MPMLS_Auto_Sync' ) || ! MPMLS_Auto_Sync::is_enabled() ) {
			return false;
		}

		// One pending retry per user is enough — sync_user() converges the
		// whole user, whichever event originally failed.
		foreach ( array_keys( self::DELAYS ) as $a ) {
			if ( wp_next_scheduled( self::HOOK, array( $user_id, $a ) ) ) {
				return false;
			}
		}

		wp_schedule_single_event( time() + self::DELAYS[ $attempt ], self::HOOK, array( $user_id, $attempt ) );

		return true;
	}

	public function run( $user_id, $attempt = 0 ) {
		if ( ! class_exists( 'MPMLS_Auto_Sync' ) || ! MPMLS_Auto_Sync::is_enabled() ) {
			return;
		}

		$user_id = (int) $user_id;
		$result  = MPMLS_Sync_Engine::instance()->sync_user( $user_id, 'retry_sync' );

		if ( ! is_wp_error( $result ) ) {
			return;
		}

		if ( ! self::schedule( $user_id, (int) $attempt + 1 ) ) {
			$user = get_userdata( $user_id );
			MPMLS_Logger::log( array(
				'event'      => 'retry_sync',
				'email'      => $user ? $user->user_email : '',
				'wp_user_id' => $user_id,
				'action'     => 'summary',
				'success'    => 0,
				'message'    => 'Retry attempts exhausted; the nightly sync will reconcile this member.',
			) );
		}
	}
}
