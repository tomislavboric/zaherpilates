<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single source of truth for member state and per-member MailerLite sync.
 *
 * Used by the manual "Sync now" button, the nightly auto-sync and the
 * diagnostics page, so every consumer works from the same definition of an
 * active member — MemberPress' own: an unexpired complete/confirmed
 * transaction for the product.
 */
class MPMLS_Sync_Engine {

	protected static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/* -------------------------------------------------- configuration -- */

	public function get_mapping() {
		$settings = get_option( MPMLS_OPTION_KEY, array() );
		if ( empty( $settings['mapping'] ) || ! is_array( $settings['mapping'] ) ) {
			return array();
		}
		return $settings['mapping'];
	}

	public function get_inactive_group_id() {
		return (string) mpmls_get_setting( 'expired_group_id', '' );
	}

	public function is_configured() {
		return '' !== (string) mpmls_get_setting( 'api_key', '' ) && ! empty( $this->get_mapping() );
	}

	public function get_client() {
		$api_key = mpmls_get_setting( 'api_key', '' );
		if ( empty( $api_key ) ) {
			return new WP_Error( 'mpmls_missing_api_key', 'MailerLite API key is missing.' );
		}

		/** Filter the client instance (used to inject a mock in tests). */
		return apply_filters( 'mpmls_mailerlite_client', new MPMLS_MailerLite_Client( $api_key ) );
	}

	/* --------------------------- member state (MemberPress truth) ----- */

	public function get_active_members_sql( $user_id = 0, $with_order = true ) {
		global $wpdb;

		$now = current_time( 'mysql' );

		// MemberPress' own definition of an active member: an unexpired
		// complete/confirmed transaction for the product. Subscription status
		// is irrelevant here — a stopped subscription that is paid until the
		// end of the period still counts as active (matches the MP Members
		// screen).
		$sql = "SELECT t.user_id, t.product_id, MAX(t.expires_at) AS expires_at
			FROM {$wpdb->prefix}mepr_transactions t
			WHERE t.user_id > 0
			AND t.status IN ('complete', 'confirmed')
			AND (t.expires_at IS NULL OR t.expires_at = '0000-00-00 00:00:00' OR t.expires_at >= %s)";
		if ( $user_id ) {
			$sql .= ' AND t.user_id = %d';
			$sql  = $wpdb->prepare( $sql, $now, $user_id );
		} else {
			$sql = $wpdb->prepare( $sql, $now );
		}
		$sql .= ' GROUP BY t.user_id, t.product_id';
		if ( $with_order ) {
			$sql .= ' ORDER BY t.user_id, t.product_id';
		}
		return $sql;
	}

	public function get_expired_members_sql( $with_order = true, $user_id = 0 ) {
		global $wpdb;

		$now = current_time( 'mysql' );

		// Complement of get_active_members_sql(): paid (or refunded) history
		// on the product, but no currently valid transaction for it.
		$sql = "SELECT t.user_id, t.product_id, MAX(t.expires_at) AS expires_at
			FROM {$wpdb->prefix}mepr_transactions t
			WHERE t.user_id > 0
			AND t.status IN ('complete', 'confirmed', 'refunded')";
		if ( $user_id ) {
			$sql .= $wpdb->prepare( ' AND t.user_id = %d', $user_id );
		}
		$sql .= " GROUP BY t.user_id, t.product_id
			HAVING SUM( CASE WHEN t.status IN ('complete', 'confirmed')
				AND ( t.expires_at IS NULL OR t.expires_at = '0000-00-00 00:00:00' OR t.expires_at >= " . $wpdb->prepare( '%s', $now ) . ' )
				THEN 1 ELSE 0 END ) = 0';
		if ( $with_order ) {
			$sql .= ' ORDER BY t.user_id, t.product_id';
		}
		return $sql;
	}

	public function get_active_members() {
		global $wpdb;
		return $wpdb->get_results( $this->get_active_members_sql( 0, true ), ARRAY_A );
	}

	public function get_expired_members() {
		global $wpdb;
		return $wpdb->get_results( $this->get_expired_members_sql( true, 0 ), ARRAY_A );
	}

	public function get_active_membership_ids_for_user( $user_id ) {
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

	public function get_active_group_ids_for_user( $user_id ) {
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

	public function user_has_paid_transaction( $user_id ) {
		global $wpdb;

		if ( ! $user_id ) {
			return false;
		}

		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}mepr_transactions WHERE user_id = %d AND status = 'complete' LIMIT 1",
			$user_id
		) );
	}

	/* ------------------------------------------------------- fields --- */

	public function build_subscriber_fields( $user, $product_id, $expires_at, $status ) {
		$fields = array(
			'name'              => $user->first_name ?: '',
			'last_name'         => $user->last_name ?: '',
			'membership_name'   => get_the_title( $product_id ) ?: '',
			'membership_status' => $status,
		);
		if ( $user->user_registered ) {
			$fields['signup_date'] = date( 'Y-m-d', strtotime( $user->user_registered ) );
		}
		if ( $expires_at !== '' && $expires_at !== '0000-00-00 00:00:00' ) {
			$fields['membership_expiry'] = date( 'Y-m-d', strtotime( $expires_at ) );
		}

		return array_filter( $fields, function ( $v ) {
			return $v !== '';
		} );
	}

	/* ------------------------------------------------ group cleanup --- */

	public function remove_from_inactive_mapped_groups( $client, $subscriber_id, $user_id, $email, $event_name, $include_expired_group = false ) {
		$mapping = $this->get_mapping();
		if ( empty( $mapping ) && ! $include_expired_group ) {
			return;
		}

		$active_group_ids = $this->get_active_group_ids_for_user( $user_id );
		$active_group_ids = array_values( array_unique( $active_group_ids ) );

		$mapped_group_ids = array();
		foreach ( $mapping as $group_id ) {
			$mapped_group_ids[] = (string) $group_id;
		}
		// When reconciling active members, also pull them out of the inactive group.
		if ( $include_expired_group ) {
			$expired_group_id = $this->get_inactive_group_id();
			if ( $expired_group_id !== '' ) {
				$mapped_group_ids[] = $expired_group_id;
			}
		}
		$mapped_group_ids = array_values( array_unique( $mapped_group_ids ) );

		foreach ( $mapped_group_ids as $group_id ) {
			if ( $group_id === '' ) {
				continue;
			}
			if ( in_array( $group_id, $active_group_ids, true ) ) {
				continue;
			}

			$result = $client->remove_from_group( $subscriber_id, $group_id, $email );

			MPMLS_Logger::log( array(
				'event'         => $event_name,
				'email'         => $email,
				'wp_user_id'    => (int) $user_id,
				'membership_id' => 0,
				'group_id'      => $group_id,
				'action'        => 'remove_inactive',
				'success'       => is_wp_error( $result ) ? 0 : 1,
				'message'       => is_wp_error( $result ) ? $result->get_error_message() : 'Removed from inactive mapped group.',
			) );
		}
	}

	/* --------------------------------------------------- subscriber --- */

	protected function resolve_subscriber_id( $client, $email, $fields ) {
		$subscriber_id = $client->upsert_subscriber( $email, $fields );
		if ( ! is_wp_error( $subscriber_id ) ) {
			return $subscriber_id;
		}

		// Unsubscribed contacts reject the upsert (HTTP 422) but can still be
		// looked up, so their group bookkeeping stays correct.
		$lookup = $client->get_subscriber_id_by_email( $email );

		return is_wp_error( $lookup ) ? $subscriber_id : $lookup;
	}

	/* ------------------------------------------------ per-member sync -- */

	public function sync_active_member( $member, $client, $mapping, $event_name ) {
		$product_id = (int) $member['product_id'];
		$user_id    = (int) $member['user_id'];
		$group_id   = isset( $mapping[ $product_id ] ) ? (string) $mapping[ $product_id ] : '';

		if ( $group_id === '' ) {
			return 'skipped';
		}

		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->user_email ) ) {
			return 'skipped';
		}

		$expires_at = isset( $member['expires_at'] ) ? (string) $member['expires_at'] : '';
		$fields     = $this->build_subscriber_fields( $user, $product_id, $expires_at, 'active' );

		$subscriber_id = $this->resolve_subscriber_id( $client, $user->user_email, $fields );
		if ( is_wp_error( $subscriber_id ) ) {
			$this->log_member( $event_name, $user, $product_id, $group_id, 'activate', 0, $subscriber_id->get_error_message() );
			return 'error';
		}

		$result = $client->add_to_group( $subscriber_id, $group_id, $user->user_email, $fields );
		if ( is_wp_error( $result ) ) {
			$this->log_member( $event_name, $user, $product_id, $group_id, 'activate', 0, $result->get_error_message() );
			return 'error';
		}

		$this->remove_from_inactive_mapped_groups( $client, $subscriber_id, $user_id, $user->user_email, $event_name, true );

		$this->log_member( $event_name, $user, $product_id, $group_id, 'activate', 1, 'Active member synced to plan group.' );
		return 'synced';
	}

	public function sync_inactive_member( $member, $client, $inactive_group_id, $event_name ) {
		$product_id = (int) $member['product_id'];
		$user_id    = (int) $member['user_id'];
		$expires_at = isset( $member['expires_at'] ) ? (string) $member['expires_at'] : '';

		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->user_email ) ) {
			return 'skipped';
		}

		// A member with any active plan (e.g. after a plan change) is not churned.
		if ( ! empty( $this->get_active_membership_ids_for_user( $user_id ) ) ) {
			return 'skipped';
		}

		// Signups that never completed a payment were never customers.
		if ( ! $this->user_has_paid_transaction( $user_id ) ) {
			return 'skipped';
		}

		$fields = $this->build_subscriber_fields( $user, $product_id, $expires_at, 'expired' );

		$subscriber_id = $this->resolve_subscriber_id( $client, $user->user_email, $fields );
		if ( is_wp_error( $subscriber_id ) ) {
			$this->log_member( $event_name, $user, $product_id, $inactive_group_id, 'deactivate', 0, $subscriber_id->get_error_message() );
			return 'error';
		}

		$result = $client->add_to_group( $subscriber_id, $inactive_group_id, $user->user_email, $fields );
		if ( is_wp_error( $result ) ) {
			$this->log_member( $event_name, $user, $product_id, $inactive_group_id, 'deactivate', 0, $result->get_error_message() );
			return 'error';
		}

		$this->remove_from_inactive_mapped_groups( $client, $subscriber_id, $user_id, $user->user_email, $event_name, false );

		$this->log_member( $event_name, $user, $product_id, $inactive_group_id, 'deactivate', 1, 'Churned member synced to inactive group.' );
		return 'synced';
	}

	/* -------------------------------------------------- per-user sync -- */

	/**
	 * Converge one user to their current desired state: every active
	 * membership into its plan group, or — when fully churned — into the
	 * inactive group. Used by the retry mechanism.
	 */
	public function sync_user( $user_id, $event_name = 'retry_sync', $client = null ) {
		global $wpdb;

		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return new WP_Error( 'mpmls_no_user', 'Invalid user ID.' );
		}

		$mapping = $this->get_mapping();
		if ( empty( $mapping ) ) {
			return new WP_Error( 'mpmls_no_mapping', 'No membership - group mapping found.' );
		}

		if ( null === $client ) {
			$client = $this->get_client();
		}
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$had_error   = false;
		$active_rows = $wpdb->get_results( $this->get_active_members_sql( $user_id, true ), ARRAY_A );

		if ( ! empty( $active_rows ) ) {
			foreach ( $active_rows as $row ) {
				if ( 'error' === $this->sync_active_member( $row, $client, $mapping, $event_name ) ) {
					$had_error = true;
				}
			}
		} else {
			$inactive_group_id = $this->get_inactive_group_id();
			if ( '' !== $inactive_group_id ) {
				$expired_rows = $wpdb->get_results( $this->get_expired_members_sql( true, $user_id ), ARRAY_A );
				if ( ! empty( $expired_rows ) ) {
					// One representative row (latest expiry) is enough — the
					// member-level guards inside sync_inactive_member() apply.
					usort( $expired_rows, function ( $a, $b ) {
						return strcmp( (string) $a['expires_at'], (string) $b['expires_at'] );
					} );
					$row = end( $expired_rows );
					if ( 'error' === $this->sync_inactive_member( $row, $client, $inactive_group_id, $event_name ) ) {
						$had_error = true;
					}
				}
			}
		}

		return $had_error ? new WP_Error( 'mpmls_sync_user_failed', 'One or more sync operations failed for this user.' ) : true;
	}

	/* --------------------------------------------------- batch runners -- */

	public function run_active_batch( $offset, $batch_size, $event_name, $client = null ) {
		$mapping = $this->get_mapping();
		if ( empty( $mapping ) ) {
			return new WP_Error( 'mpmls_no_mapping', 'No membership - group mapping found.' );
		}

		if ( null === $client ) {
			$client = $this->get_client();
		}
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$members = $this->get_active_members();
		$total   = count( $members );
		$slice   = array_slice( $members, $offset, $batch_size );

		$counts = array( 'synced' => 0, 'skipped' => 0, 'errors' => 0 );
		foreach ( $slice as $member ) {
			$status = $this->sync_active_member( $member, $client, $mapping, $event_name );
			$key    = ( 'synced' === $status ) ? 'synced' : ( ( 'error' === $status ) ? 'errors' : 'skipped' );
			$counts[ $key ]++;
		}

		return $this->batch_result( $offset, $batch_size, $total, $counts );
	}

	public function run_inactive_batch( $offset, $batch_size, $event_name, $client = null ) {
		$inactive_group_id = $this->get_inactive_group_id();
		if ( '' === $inactive_group_id ) {
			return new WP_Error( 'mpmls_no_inactive_group', 'Inactive group ID is not configured.' );
		}

		if ( null === $client ) {
			$client = $this->get_client();
		}
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$members = $this->get_expired_members();
		$total   = count( $members );
		$slice   = array_slice( $members, $offset, $batch_size );

		$counts = array( 'synced' => 0, 'skipped' => 0, 'errors' => 0 );
		foreach ( $slice as $member ) {
			$status = $this->sync_inactive_member( $member, $client, $inactive_group_id, $event_name );
			$key    = ( 'synced' === $status ) ? 'synced' : ( ( 'error' === $status ) ? 'errors' : 'skipped' );
			$counts[ $key ]++;
		}

		return $this->batch_result( $offset, $batch_size, $total, $counts );
	}

	protected function batch_result( $offset, $batch_size, $total, $counts ) {
		$new_offset = $offset + $batch_size;

		return array(
			'processed' => min( $new_offset, $total ),
			'total'     => $total,
			'synced'    => $counts['synced'],
			'skipped'   => $counts['skipped'],
			'errors'    => $counts['errors'],
			'done'      => $new_offset >= $total,
			'offset'    => $new_offset,
		);
	}

	protected function log_member( $event, $user, $membership_id, $group_id, $action, $success, $message ) {
		MPMLS_Logger::log( array(
			'event'         => $event,
			'email'         => $user->user_email,
			'wp_user_id'    => (int) $user->ID,
			'membership_id' => (int) $membership_id,
			'group_id'      => (string) $group_id,
			'action'        => $action,
			'success'       => $success ? 1 : 0,
			'message'       => $message,
		) );
	}
}
