<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPMLS_MemberPress_Hooks {
	const DEBOUNCE_TTL = 30;

	public function register() {
		if ( ! class_exists( 'MeprSubscription' ) && ! class_exists( 'MeprTransaction' ) ) {
			return;
		}

		// Preferred MemberPress event hooks.
		add_action( 'mepr-event-subscription-created', array( $this, 'handle_subscription_created' ) );
		add_action( 'mepr-event-subscription-stopped', array( $this, 'handle_subscription_stopped' ) );
		add_action( 'mepr-event-subscription-expired', array( $this, 'handle_subscription_expired' ), 10, 2 );

		add_action( 'mepr-event-transaction-completed', array( $this, 'handle_transaction_completed' ) );
		add_action( 'mepr-event-non-recurring-transaction-completed', array( $this, 'handle_transaction_completed' ) );
		add_action( 'mepr-event-recurring-transaction-completed', array( $this, 'handle_renewal_completed' ) );
		add_action( 'mepr-event-renewal-transaction-completed', array( $this, 'handle_renewal_completed' ) );

		add_action( 'mepr-event-transaction-refunded', array( $this, 'handle_transaction_refunded' ) );
		add_action( 'mepr-event-transaction-expired', array( $this, 'handle_transaction_expired' ) );
		add_action( 'mepr-event-recurring-transaction-expired', array( $this, 'handle_transaction_expired' ) );
		add_action( 'mepr-event-non-recurring-transaction-expired', array( $this, 'handle_transaction_expired' ) );

		// Fallbacks for older MP versions.
		add_action( 'mepr-txn-status-complete', array( $this, 'handle_txn_status_complete_fallback' ) );
		add_action( 'mepr-txn-transition-status', array( $this, 'handle_txn_transition_fallback' ), 10, 3 );
	}

	public function handle_subscription_created( $event ) {
		$sub = $this->get_event_data( $event );
		$this->process_activation( 'subscription_created', $sub );
	}

	public function handle_subscription_stopped( $event ) {
		$sub = $this->get_event_data( $event );
		$this->process_deactivation( 'subscription_stopped', $sub );
	}

	public function handle_subscription_expired( $subscription, $transaction = null ) {
		// MemberPress passes a MeprEvent here, not the subscription itself.
		$sub = $this->get_event_data( $subscription );
		$this->process_deactivation( 'subscription_expired', $sub, $transaction );
	}

	public function handle_transaction_completed( $event ) {
		$txn = $this->get_event_data( $event );
		$this->process_activation( 'transaction_completed', $txn );
	}

	public function handle_renewal_completed( $event ) {
		$txn = $this->get_event_data( $event );
		$this->process_activation( 'subscription_renewed', $txn );
	}

	public function handle_transaction_refunded( $event ) {
		$txn = $this->get_event_data( $event );
		$this->process_deactivation( 'transaction_refunded', $txn );
	}

	public function handle_transaction_expired( $event ) {
		$txn = $this->get_event_data( $event );
		$this->process_deactivation( 'transaction_expired', $txn );
	}

	public function handle_txn_status_complete_fallback( $txn ) {
		$this->process_activation( 'txn_status_complete', $txn );
	}

	public function handle_txn_transition_fallback( $old_status, $new_status, $txn ) {
		// MemberPress fires this as ( $old_status, $new_status, $txn ).
		if ( $new_status === $this->get_complete_status() ) {
			$this->process_activation( 'txn_transition_complete', $txn );
		}
	}

	protected function get_complete_status() {
		if ( class_exists( 'MeprTransaction' ) && property_exists( 'MeprTransaction', 'complete_str' ) ) {
			return MeprTransaction::$complete_str;
		}
		return 'complete';
	}

	protected function get_event_data( $event ) {
		if ( is_object( $event ) && method_exists( $event, 'get_data' ) ) {
			return $event->get_data();
		}
		return $event;
	}

	protected function process_activation( $event_name, $object ) {
		$context = $this->build_context( $object );
		if ( ! $context ) {
			return;
		}

		// Only push members who are actually active on this membership.
		// subscription-created fires before payment, so pending/unpaid signups
		// are skipped here and picked up by the transaction-completed events.
		if ( ! $this->is_user_active_on_membership( $context['user_id'], $context['membership_id'] ) ) {
			MPMLS_Logger::log( array(
				'event'         => $event_name,
				'email'         => $context['email'],
				'wp_user_id'    => $context['user_id'],
				'membership_id' => $context['membership_id'],
				'group_id'      => $context['group_id'],
				'action'        => 'skip',
				'success'       => 1,
				'message'       => 'Skipped activation: membership not active yet (pending/unpaid).',
			) );
			return;
		}

		if ( $this->is_debounced( $context['email'], $context['membership_id'], 'activate' ) ) {
			MPMLS_Logger::log( array(
				'event'         => $event_name,
				'email'         => $context['email'],
				'wp_user_id'    => $context['user_id'],
				'membership_id' => $context['membership_id'],
				'group_id'      => $context['group_id'],
				'action'        => 'activate',
				'success'       => 1,
				'message'       => 'Debounced duplicate event.',
			) );
			return;
		}

		$client = $this->get_client();
		if ( is_wp_error( $client ) ) {
			$this->log_error( $event_name, $context, 'activate', $client->get_error_message() );
			return;
		}

		$fields = $this->build_subscriber_fields( $context, 'active' );

		$subscriber_id = $client->upsert_subscriber( $context['email'], $fields );
		if ( is_wp_error( $subscriber_id ) ) {
			$this->log_error( $event_name, $context, 'activate', $subscriber_id->get_error_message() );
			return;
		}

		$result = $client->add_to_group( $subscriber_id, $context['group_id'], $context['email'], $fields );
		if ( is_wp_error( $result ) ) {
			$this->log_error( $event_name, $context, 'activate', $result->get_error_message() );
			return;
		}

		$this->remove_from_inactive_mapped_groups( $client, $subscriber_id, $context, $event_name );

		MPMLS_Logger::log( array(
			'event'         => $event_name,
			'email'         => $context['email'],
			'wp_user_id'    => $context['user_id'],
			'membership_id' => $context['membership_id'],
			'group_id'      => $context['group_id'],
			'action'        => 'activate',
			'success'       => 1,
			'message'       => 'Subscriber upserted and added to group.',
		) );
	}

	protected function process_deactivation( $event_name, $object, $transaction = null ) {
		$context = $this->build_context( $object, $transaction );
		if ( ! $context ) {
			return;
		}

		// MemberPress signup creates a short-lived "confirmed" transaction whose
		// expiry fires transaction-expired a day later even though the member is
		// still active. Never deactivate someone who is still active on this plan.
		if ( $this->is_user_active_on_membership( $context['user_id'], $context['membership_id'] ) ) {
			MPMLS_Logger::log( array(
				'event'         => $event_name,
				'email'         => $context['email'],
				'wp_user_id'    => $context['user_id'],
				'membership_id' => $context['membership_id'],
				'group_id'      => $context['group_id'],
				'action'        => 'skip',
				'success'       => 1,
				'message'       => 'Skipped deactivation: membership still active.',
			) );
			return;
		}

		if ( $this->is_debounced( $context['email'], $context['membership_id'], 'deactivate' ) ) {
			MPMLS_Logger::log( array(
				'event'         => $event_name,
				'email'         => $context['email'],
				'wp_user_id'    => $context['user_id'],
				'membership_id' => $context['membership_id'],
				'group_id'      => $context['group_id'],
				'action'        => 'deactivate',
				'success'       => 1,
				'message'       => 'Debounced duplicate event.',
			) );
			return;
		}

		$client = $this->get_client();
		if ( is_wp_error( $client ) ) {
			$this->log_error( $event_name, $context, 'deactivate', $client->get_error_message() );
			return;
		}

		$status = in_array( $event_name, array( 'subscription_stopped', 'transaction_refunded' ), true ) ? 'cancelled' : 'expired';
		$fields = $this->build_subscriber_fields( $context, $status );

		$subscriber_id = $client->upsert_subscriber( $context['email'], $fields );
		if ( is_wp_error( $subscriber_id ) ) {
			$subscriber_id = $client->get_subscriber_id_by_email( $context['email'] );
			if ( is_wp_error( $subscriber_id ) ) {
				$subscriber_id = null;
			}
		}

		if ( ! $subscriber_id ) {
			$this->log_error( $event_name, $context, 'deactivate', 'Could not determine subscriber ID for deactivation.' );
			return;
		}

		// Remove from mapped plan groups that are no longer active (including the
		// group of the plan being deactivated). Groups of plans the member is
		// still active on (e.g. after a plan change) are kept.
		$this->remove_from_inactive_mapped_groups( $client, $subscriber_id, $context, $event_name, false, false );

		// Only truly churned members belong in the inactive group: nobody with an
		// active plan (plan changes), and nobody who never completed a payment.
		$deactivation_group_id = $this->get_deactivation_group_id( $event_name );
		if ( $deactivation_group_id ) {
			$active_membership_ids = $this->get_active_membership_ids_for_user( $context['user_id'] );
			if ( ! empty( $active_membership_ids ) ) {
				MPMLS_Logger::log( array(
					'event'         => $event_name,
					'email'         => $context['email'],
					'wp_user_id'    => $context['user_id'],
					'membership_id' => $context['membership_id'],
					'group_id'      => $deactivation_group_id,
					'action'        => 'skip',
					'success'       => 1,
					'message'       => 'Skipped inactive group: member still active on another plan.',
				) );
			} elseif ( ! $this->user_has_paid_transaction( $context['user_id'] ) ) {
				MPMLS_Logger::log( array(
					'event'         => $event_name,
					'email'         => $context['email'],
					'wp_user_id'    => $context['user_id'],
					'membership_id' => $context['membership_id'],
					'group_id'      => $deactivation_group_id,
					'action'        => 'skip',
					'success'       => 1,
					'message'       => 'Skipped inactive group: no completed payment on record (unpaid signup).',
				) );
			} else {
				$result = $client->add_to_group( $subscriber_id, $deactivation_group_id, $context['email'] );
				if ( is_wp_error( $result ) ) {
					$this->log_error( $event_name, $context, 'deactivate', $result->get_error_message() );
					return;
				}
			}
		}

		MPMLS_Logger::log( array(
			'event'         => $event_name,
			'email'         => $context['email'],
			'wp_user_id'    => $context['user_id'],
			'membership_id' => $context['membership_id'],
			'group_id'      => $context['group_id'],
			'action'        => 'deactivate',
			'success'       => 1,
			'message'       => 'Subscriber updated for cancellation/expiry.',
		) );
	}

	protected function build_context( $object, $transaction = null ) {
		$membership_id = $this->get_membership_id( $object, $transaction );
		if ( ! $membership_id ) {
			return null;
		}

		$group_id = $this->get_group_id_for_membership( $membership_id );
		if ( ! $group_id ) {
			MPMLS_Logger::log( array(
				'event'         => 'mapping_missing',
				'email'         => $this->get_email_from_object( $object, $transaction ),
				'wp_user_id'    => $this->get_user_id_from_object( $object, $transaction ),
				'membership_id' => $membership_id,
				'action'        => 'skip',
				'success'       => 0,
				'message'       => 'No mapping found for membership ID.',
			) );
			return null;
		}

		$email = $this->get_email_from_object( $object, $transaction );
		if ( empty( $email ) ) {
			return null;
		}

		$expires_at = '';
		foreach ( array( $transaction, $object ) as $item ) {
			if ( is_object( $item ) && isset( $item->expires_at ) && $item->expires_at !== '0000-00-00 00:00:00' ) {
				$expires_at = (string) $item->expires_at;
				break;
			}
		}

		return array(
			'email'         => $email,
			'user_id'       => $this->get_user_id_from_object( $object, $transaction ),
			'membership_id' => $membership_id,
			'group_id'      => $group_id,
			'expires_at'    => $expires_at,
		);
	}

	protected function build_subscriber_fields( $context, $status = 'active' ) {
		$fields = array();

		$user = $context['user_id'] ? get_userdata( $context['user_id'] ) : null;
		if ( $user ) {
			$fields['name']      = $user->first_name ?: '';
			$fields['last_name'] = $user->last_name ?: '';
			$fields['signup_date'] = $user->user_registered
				? date( 'Y-m-d', strtotime( $user->user_registered ) )
				: '';
		}

		$membership_id = $context['membership_id'];
		if ( $membership_id ) {
			$fields['membership_name'] = get_the_title( $membership_id ) ?: '';
		}

		if ( ! empty( $context['expires_at'] ) ) {
			$fields['membership_expiry'] = date( 'Y-m-d', strtotime( $context['expires_at'] ) );
		}

		$fields['membership_status'] = $status;

		return array_filter( $fields, function ( $v ) {
			return $v !== '';
		} );
	}

	protected function get_user_id_from_object( $object, $transaction = null ) {
		foreach ( array( $transaction, $object ) as $item ) {
			if ( is_object( $item ) && isset( $item->user_id ) ) {
				return (int) $item->user_id;
			}
		}
		return 0;
	}

	protected function get_email_from_object( $object, $transaction = null ) {
		$user_id = $this->get_user_id_from_object( $object, $transaction );
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user && ! empty( $user->user_email ) ) {
				return $user->user_email;
			}
		}

		foreach ( array( $transaction, $object ) as $item ) {
			if ( is_object( $item ) && method_exists( $item, 'user' ) ) {
				$user = $item->user();
				if ( $user && isset( $user->user_email ) ) {
					return $user->user_email;
				}
			}
		}

		return '';
	}

	protected function get_membership_id( $object, $transaction = null ) {
		foreach ( array( $transaction, $object ) as $item ) {
			if ( is_object( $item ) && isset( $item->product_id ) ) {
				return (int) $item->product_id;
			}
		}
		return 0;
	}

	protected function get_group_id_for_membership( $membership_id ) {
		$settings = get_option( MPMLS_OPTION_KEY, array() );
		if ( empty( $settings['mapping'] ) || ! is_array( $settings['mapping'] ) ) {
			return '';
		}

		return isset( $settings['mapping'][ $membership_id ] ) ? (string) $settings['mapping'][ $membership_id ] : '';
	}

	protected function get_mapping() {
		$settings = get_option( MPMLS_OPTION_KEY, array() );
		if ( empty( $settings['mapping'] ) || ! is_array( $settings['mapping'] ) ) {
			return array();
		}
		return $settings['mapping'];
	}

	protected function get_active_membership_ids_for_user( $user_id ) {
		global $wpdb;

		if ( ! $user_id ) {
			return array();
		}

		// Prefer MemberPress' own notion of "active" (handles confirmed txns,
		// lifetimes, trials, fallback txns) over raw SQL.
		if ( class_exists( 'MeprUser' ) ) {
			$user = new MeprUser( (int) $user_id );
			if ( method_exists( $user, 'active_product_subscriptions' ) ) {
				$ids = (array) $user->active_product_subscriptions( 'ids', true );
				return array_values( array_unique( array_map( 'intval', $ids ) ) );
			}
		}

		// Fallback: the same definition in SQL — an unexpired complete/confirmed
		// transaction, regardless of subscription status.
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT t.product_id
			FROM {$wpdb->prefix}mepr_transactions t
			WHERE t.status IN ('complete', 'confirmed')
			AND (t.expires_at IS NULL OR t.expires_at = '0000-00-00 00:00:00' OR t.expires_at >= %s)
			AND t.user_id = %d",
			current_time( 'mysql' ),
			$user_id
		) );

		return array_map( 'intval', $ids );
	}

	protected function is_user_active_on_membership( $user_id, $membership_id ) {
		return in_array( (int) $membership_id, $this->get_active_membership_ids_for_user( $user_id ), true );
	}

	protected function user_has_paid_transaction( $user_id ) {
		global $wpdb;

		if ( ! $user_id ) {
			return false;
		}

		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}mepr_transactions WHERE user_id = %d AND status = 'complete' LIMIT 1",
			$user_id
		) );
	}

	protected function get_active_group_ids_for_user( $user_id ) {
		$mapping = $this->get_mapping();
		if ( empty( $mapping ) ) {
			return array();
		}

		$membership_ids = $this->get_active_membership_ids_for_user( $user_id );
		if ( empty( $membership_ids ) ) {
			return array();
		}

		$active = array();
		foreach ( $membership_ids as $membership_id ) {
			if ( isset( $mapping[ $membership_id ] ) ) {
				$group_id = (string) $mapping[ $membership_id ];
				if ( $group_id !== '' ) {
					$active[] = $group_id;
				}
			}
		}

		return array_values( array_unique( $active ) );
	}

	protected function remove_from_inactive_mapped_groups( $client, $subscriber_id, $context, $event_name, $keep_current = true, $include_expired_group = true ) {
		$mapping = $this->get_mapping();

		$active_group_ids = $this->get_active_group_ids_for_user( $context['user_id'] );
		if ( $keep_current ) {
			$active_group_ids[] = (string) $context['group_id'];
		}
		$active_group_ids = array_values( array_unique( $active_group_ids ) );

		$candidate_group_ids = array();
		foreach ( $mapping as $membership_id => $group_id ) {
			$candidate_group_ids[] = (string) $group_id;
		}
		// On activation also clean up the inactive/expired group.
		if ( $include_expired_group ) {
			$expired_group_id = $this->get_expired_group_id();
			if ( $expired_group_id !== '' ) {
				$candidate_group_ids[] = $expired_group_id;
			}
		}
		$candidate_group_ids = array_values( array_unique( $candidate_group_ids ) );

		foreach ( $candidate_group_ids as $group_id ) {
			if ( $group_id === '' ) {
				continue;
			}
			if ( in_array( $group_id, $active_group_ids, true ) ) {
				continue;
			}

			$result = $client->remove_from_group( $subscriber_id, $group_id, $context['email'] );
			if ( is_wp_error( $result ) ) {
				MPMLS_Logger::log( array(
					'event'         => $event_name,
					'email'         => $context['email'],
					'wp_user_id'    => $context['user_id'],
					'membership_id' => $context['membership_id'],
					'group_id'      => $group_id,
					'action'        => 'remove_inactive',
					'success'       => 0,
					'message'       => $result->get_error_message(),
				) );
				continue;
			}

			MPMLS_Logger::log( array(
				'event'         => $event_name,
				'email'         => $context['email'],
				'wp_user_id'    => $context['user_id'],
				'membership_id' => $context['membership_id'],
				'group_id'      => $group_id,
				'action'        => 'remove_inactive',
				'success'       => 1,
				'message'       => 'Removed from inactive mapped group.',
			) );
		}
	}

	protected function get_expired_group_id() {
		$settings = get_option( MPMLS_OPTION_KEY, array() );
		return isset( $settings['expired_group_id'] ) ? (string) $settings['expired_group_id'] : '';
	}

	protected function get_cancelled_group_id() {
		return $this->get_expired_group_id();
	}

	protected function get_deactivation_group_id( $event_name ) {
		$event_name = (string) $event_name;
		if ( in_array( $event_name, array( 'subscription_stopped', 'transaction_refunded' ), true ) ) {
			return $this->get_cancelled_group_id();
		}
		if ( in_array( $event_name, array( 'subscription_expired', 'transaction_expired' ), true ) ) {
			return $this->get_expired_group_id();
		}
		return $this->get_expired_group_id();
	}

	protected function get_client() {
		$api_key = mpmls_get_setting( 'api_key', '' );
		if ( empty( $api_key ) ) {
			return new WP_Error( 'mpmls_missing_api_key', 'MailerLite API key is missing.' );
		}
		return new MPMLS_MailerLite_Client( $api_key );
	}

	protected function is_debounced( $email, $membership_id, $action ) {
		$key = 'mpmls_' . md5( strtolower( $email ) . '|' . (int) $membership_id . '|' . $action );
		if ( get_transient( $key ) ) {
			return true;
		}
		set_transient( $key, 1, self::DEBOUNCE_TTL );
		return false;
	}

	protected function log_error( $event_name, $context, $action, $message ) {
		MPMLS_Logger::log( array(
			'event'         => $event_name,
			'email'         => $context['email'],
			'wp_user_id'    => $context['user_id'],
			'membership_id' => $context['membership_id'],
			'group_id'      => $context['group_id'],
			'action'        => $action,
			'success'       => 0,
			'message'       => $message,
		) );
	}
}
