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
		$this->process_deactivation( 'subscription_expired', $subscription, $transaction );
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

	public function handle_txn_transition_fallback( $txn, $old_status, $new_status ) {
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

		$subscriber_id = $client->upsert_subscriber( $context['email'] );
		if ( is_wp_error( $subscriber_id ) ) {
			$this->log_error( $event_name, $context, 'activate', $subscriber_id->get_error_message() );
			return;
		}

		$result = $client->add_to_group( $subscriber_id, $context['group_id'], $context['email'] );
		if ( is_wp_error( $result ) ) {
			$this->log_error( $event_name, $context, 'activate', $result->get_error_message() );
			return;
		}

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

		$subscriber_id = $client->upsert_subscriber( $context['email'] );
		if ( is_wp_error( $subscriber_id ) ) {
			$subscriber_id = $client->get_subscriber_id_by_email( $context['email'] );
			if ( is_wp_error( $subscriber_id ) ) {
				$subscriber_id = null;
			}
		}

		if ( $this->should_remove_on_expired() ) {
			if ( ! $subscriber_id ) {
				$this->log_error( $event_name, $context, 'deactivate', 'Could not determine subscriber ID for removal.' );
				return;
			}

			$result = $client->remove_from_group( $subscriber_id, $context['group_id'], $context['email'] );
			if ( is_wp_error( $result ) ) {
				$this->log_error( $event_name, $context, 'deactivate', $result->get_error_message() );
				return;
			}
		}

		$deactivation_group_id = $this->get_deactivation_group_id( $event_name );
		if ( $deactivation_group_id ) {
			if ( ! $subscriber_id ) {
				$this->log_error( $event_name, $context, 'deactivate', 'Could not determine subscriber ID for expired/cancelled group.' );
				return;
			}
			$result = $client->add_to_group( $subscriber_id, $deactivation_group_id, $context['email'] );
			if ( is_wp_error( $result ) ) {
				$this->log_error( $event_name, $context, 'deactivate', $result->get_error_message() );
				return;
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

		return array(
			'email'         => $email,
			'user_id'       => $this->get_user_id_from_object( $object, $transaction ),
			'membership_id' => $membership_id,
			'group_id'      => $group_id,
		);
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

	protected function get_expired_group_id() {
		$settings = get_option( MPMLS_OPTION_KEY, array() );
		return isset( $settings['expired_group_id'] ) ? (string) $settings['expired_group_id'] : '';
	}

	protected function get_cancelled_group_id() {
		$settings = get_option( MPMLS_OPTION_KEY, array() );
		$cancelled = isset( $settings['cancelled_group_id'] ) ? (string) $settings['cancelled_group_id'] : '';
		if ( $cancelled === '' ) {
			return $this->get_expired_group_id();
		}
		return $cancelled;
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

	protected function should_remove_on_expired() {
		$settings = get_option( MPMLS_OPTION_KEY, array() );
		return ! empty( $settings['remove_on_expired'] );
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
