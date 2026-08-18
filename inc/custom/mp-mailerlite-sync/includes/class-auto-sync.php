<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nightly self-healing sync.
 *
 * Every night the full member base is reconciled against MailerLite in small
 * WP-Cron batches (active members into plan groups, churned members into the
 * inactive group), so any drift caused by missed events corrects itself
 * within 24 hours.
 */
class MPMLS_Auto_Sync {

	const DAILY_HOOK     = 'mpmls_auto_sync_daily';
	const BATCH_HOOK     = 'mpmls_auto_sync_batch';
	const STATE_KEY      = 'mpmls_auto_sync_state';
	const LAST_RUN_KEY   = 'mpmls_auto_sync_last';
	const BATCH_SIZE     = 6;
	const BATCH_INTERVAL = 30;

	public function register() {
		add_action( 'init', array( $this, 'maybe_schedule' ) );
		add_action( self::DAILY_HOOK, array( $this, 'start' ) );
		add_action( self::BATCH_HOOK, array( $this, 'run_batch' ) );
	}

	/**
	 * Whether automatic syncing may run here. Guarded so a staging/local copy
	 * of the site holding a production API key never writes to MailerLite.
	 */
	public static function is_enabled() {
		$enabled = true;

		if ( defined( 'MPMLS_DISABLE_AUTO_SYNC' ) && MPMLS_DISABLE_AUTO_SYNC ) {
			$enabled = false;
		}

		if ( function_exists( 'wp_get_environment_type' ) && 'production' !== wp_get_environment_type() ) {
			$enabled = false;
		}

		return (bool) apply_filters( 'mpmls_auto_sync_enabled', $enabled );
	}

	public function maybe_schedule() {
		if ( ! self::is_enabled() ) {
			if ( wp_next_scheduled( self::DAILY_HOOK ) ) {
				wp_clear_scheduled_hook( self::DAILY_HOOK );
			}
			return;
		}

		if ( wp_next_scheduled( self::DAILY_HOOK ) ) {
			return;
		}

		// 03:30 site-local time as the UTC timestamp WP-Cron expects.
		$local_now  = (int) current_time( 'timestamp' );
		$local_next = strtotime( '03:30', $local_now );
		if ( false === $local_next ) {
			return;
		}
		if ( $local_next <= $local_now ) {
			$local_next += DAY_IN_SECONDS;
		}
		$utc_next = $local_next - (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

		wp_schedule_event( $utc_next, 'daily', self::DAILY_HOOK );
	}

	public function start() {
		if ( ! self::is_enabled() ) {
			return;
		}

		$engine = MPMLS_Sync_Engine::instance();
		if ( ! $engine->is_configured() ) {
			return;
		}

		// A run that never finished (deploy, fatal, ...) is abandoned after 6h.
		$state = get_option( self::STATE_KEY );
		if ( is_array( $state ) && ! empty( $state['started'] ) && ( time() - (int) $state['started'] ) < 6 * HOUR_IN_SECONDS ) {
			return;
		}

		update_option( self::STATE_KEY, array(
			'phase'   => 'active',
			'offset'  => 0,
			'started' => time(),
			'totals'  => array(
				'active_synced'    => 0,
				'active_skipped'   => 0,
				'inactive_synced'  => 0,
				'inactive_skipped' => 0,
				'errors'           => 0,
			),
		), false );

		wp_schedule_single_event( time(), self::BATCH_HOOK );
	}

	public function run_batch() {
		if ( ! self::is_enabled() ) {
			delete_option( self::STATE_KEY );
			return;
		}

		$state = get_option( self::STATE_KEY );
		if ( ! is_array( $state ) || empty( $state['phase'] ) ) {
			return;
		}

		$engine = MPMLS_Sync_Engine::instance();

		$result = ( 'active' === $state['phase'] )
			? $engine->run_active_batch( (int) $state['offset'], self::BATCH_SIZE, 'auto_sync' )
			: $engine->run_inactive_batch( (int) $state['offset'], self::BATCH_SIZE, 'auto_sync' );

		if ( is_wp_error( $result ) ) {
			// No inactive group configured simply skips the second phase.
			if ( 'inactive' === $state['phase'] && 'mpmls_no_inactive_group' === $result->get_error_code() ) {
				$this->finish( $state );
				return;
			}
			$this->finish( $state, $result->get_error_message() );
			return;
		}

		if ( 'active' === $state['phase'] ) {
			$state['totals']['active_synced']  += (int) $result['synced'];
			$state['totals']['active_skipped'] += (int) $result['skipped'];
		} else {
			$state['totals']['inactive_synced']  += (int) $result['synced'];
			$state['totals']['inactive_skipped'] += (int) $result['skipped'];
		}
		$state['totals']['errors'] += (int) $result['errors'];

		if ( ! empty( $result['done'] ) ) {
			if ( 'active' === $state['phase'] ) {
				$state['phase']  = 'inactive';
				$state['offset'] = 0;
			} else {
				$this->finish( $state );
				return;
			}
		} else {
			$state['offset'] = (int) $result['offset'];
		}

		update_option( self::STATE_KEY, $state, false );
		wp_schedule_single_event( time() + self::BATCH_INTERVAL, self::BATCH_HOOK );
	}

	protected function finish( $state, $error_message = '' ) {
		delete_option( self::STATE_KEY );

		$totals = wp_parse_args( isset( $state['totals'] ) ? $state['totals'] : array(), array(
			'active_synced'    => 0,
			'active_skipped'   => 0,
			'inactive_synced'  => 0,
			'inactive_skipped' => 0,
			'errors'           => 0,
		) );

		update_option( self::LAST_RUN_KEY, array(
			'finished' => time(),
			'totals'   => $totals,
			'error'    => $error_message,
		), false );

		MPMLS_Logger::log( array(
			'event'   => 'auto_sync',
			'email'   => '',
			'action'  => 'summary',
			'success' => ( '' === $error_message && empty( $totals['errors'] ) ) ? 1 : 0,
			'message' => sprintf(
				'Nightly sync finished: active %d synced / %d skipped, inactive %d synced / %d skipped, %d errors.%s',
				$totals['active_synced'],
				$totals['active_skipped'],
				$totals['inactive_synced'],
				$totals['inactive_skipped'],
				$totals['errors'],
				'' !== $error_message ? ' Aborted: ' . $error_message : ''
			),
		) );
	}

	public static function is_running() {
		return is_array( get_option( self::STATE_KEY ) );
	}

	public static function get_last_run() {
		$last = get_option( self::LAST_RUN_KEY, array() );
		return is_array( $last ) ? $last : array();
	}
}
