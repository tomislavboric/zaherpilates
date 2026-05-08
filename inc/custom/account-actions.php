<?php
/**
 * Custom account page URLs.
 */
function zaher_account_page_url( $args = array() ) {
	$page = get_page_by_path( 'moj-racun' );
	$url  = $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/moj-racun/' );

	if ( ! empty( $args ) && is_array( $args ) ) {
		$url = add_query_arg( $args, $url );
	}

	return $url;
}

function zaher_account_tab_url( $tab, $args = array() ) {
	$args        = is_array( $args ) ? $args : array();
	$args['tab'] = sanitize_key( $tab );

	return zaher_account_page_url( $args );
}

function zaher_account_subscription_action_url( $action, $sub_id, $args = array() ) {
	$args           = is_array( $args ) ? $args : array();
	$args['action'] = sanitize_key( $action );
	$args['sub']    = absint( $sub_id );

	$url = zaher_account_page_url( $args );

	if ( 'update' === $args['action'] && function_exists( 'zaher_get_account_subscription_context' ) ) {
		$context = zaher_get_account_subscription_context( $args['sub'] );
		$pm      = is_wp_error( $context ) ? null : $context['gateway'];

		if ( is_object( $pm ) && method_exists( $pm, 'force_ssl' ) && $pm->force_ssl() ) {
			$url = set_url_scheme( $url, 'https' );
		}
	}

	return $url;
}

add_filter( 'mepr-account-page-permalink', 'zaher_use_custom_account_page_for_memberpress_links' );
function zaher_use_custom_account_page_for_memberpress_links( $url ) {
	return zaher_account_page_url();
}

add_filter( 'mepr-account-nav-home-link', 'zaher_memberpress_account_home_link' );
function zaher_memberpress_account_home_link( $url ) {
	return zaher_account_tab_url( 'profile' );
}

add_filter( 'mepr-account-nav-subscriptions-link', 'zaher_memberpress_account_subscriptions_link' );
function zaher_memberpress_account_subscriptions_link( $url ) {
	return zaher_account_tab_url( 'subscription' );
}

add_filter( 'mepr-account-nav-payments-link', 'zaher_memberpress_account_payments_link' );
function zaher_memberpress_account_payments_link( $url ) {
	return zaher_account_tab_url( 'payments' );
}

add_filter( 'mepr-account-nav-change-password', 'zaher_memberpress_account_password_link' );
add_filter( 'mepr-rl-change-password-url', 'zaher_memberpress_account_password_link' );
function zaher_memberpress_account_password_link( $url ) {
	return zaher_account_tab_url( 'password' );
}

/**
 * Redirect legacy MemberPress account navigation to the custom account tabs.
 * Keep operational subscription actions on the custom account page so gateway flows still work.
 */
add_action( 'template_redirect', 'zaher_redirect_legacy_memberpress_account_urls', 0 );
function zaher_redirect_legacy_memberpress_account_urls() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	$request_args = array();
	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$query = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_QUERY );
		if ( ! empty( $query ) ) {
			parse_str( $query, $request_args );
		}
	}
	$request_args = array_merge( $request_args, wp_unslash( $_GET ) );

	$is_custom_account_page = is_page( 'moj-racun' );
	$is_legacy_account_page = false;

	if ( class_exists( 'MeprOptions' ) ) {
		$mepr_options = MeprOptions::fetch();
		if ( is_object( $mepr_options ) && ! empty( $mepr_options->account_page_id ) ) {
			$is_legacy_account_page = is_page( (int) $mepr_options->account_page_id );
		}
	}

	if ( ! $is_custom_account_page && ! $is_legacy_account_page ) {
		return;
	}

	$action = isset( $request_args['action'] ) ? sanitize_key( $request_args['action'] ) : '';

	if ( '' === $action && $is_legacy_account_page ) {
		wp_safe_redirect( zaher_account_tab_url( 'profile' ), 302 );
		exit;
	}

	$tab_map = array(
		'home'          => 'profile',
		'account'       => 'profile',
		'subscriptions' => 'subscription',
		'payments'      => 'payments',
		'newpassword'   => 'password',
	);

	if ( isset( $tab_map[ $action ] ) ) {
		$args = array(
			'tab' => $tab_map[ $action ],
		);

		if ( 'home' === $action && isset( $request_args['message'] ) && 'password_updated' === sanitize_key( $request_args['message'] ) ) {
			$args['tab']              = 'password';
			$args['password_changed'] = 1;
		}

		if ( 'newpassword' === $action && isset( $request_args['error'] ) ) {
			$args['password_error'] = sanitize_key( $request_args['error'] );
		}

		wp_safe_redirect( zaher_account_page_url( $args ), 302 );
		exit;
	}

	$subscription_actions = array( 'update', 'upgrade', 'cancel', 'suspend', 'resume' );

	if ( $is_legacy_account_page && in_array( $action, $subscription_actions, true ) ) {
		$args = array(
			'action' => $action,
		);

		foreach ( array( 'sub', 'message', 'errors' ) as $key ) {
			if ( isset( $request_args[ $key ] ) ) {
				$args[ $key ] = sanitize_text_field( $request_args[ $key ] );
			}
		}

		wp_safe_redirect( zaher_account_page_url( $args ), 302 );
		exit;
	}
}

add_action( 'wp_enqueue_scripts', 'zaher_enqueue_memberpress_account_action_assets', 20 );
function zaher_enqueue_memberpress_account_action_assets() {
	if ( ! is_page( 'moj-racun' ) || ! isset( $_GET['action'] ) || 'update' !== sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
		return;
	}

	if ( class_exists( 'MeprAccountCtrl' ) ) {
		$account_ctrl = new MeprAccountCtrl();
		$account_ctrl->enqueue_scripts( true );
	}
}

/**
 * Custom account page display helpers.
 */
function zaher_account_is_lifetime_date( $date ) {
	$date = trim( (string) $date );

	if ( '' === $date || false !== stripos( $date, '0000-00' ) ) {
		return true;
	}

	return class_exists( 'MeprUtils' ) && MeprUtils::db_lifetime() === $date;
}

function zaher_account_format_date( $date, $format = 'j. F Y.' ) {
	$date = trim( (string) $date );

	if ( '' === $date ) {
		return '';
	}

	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $date, $matches ) ) {
		$timestamp = mktime( 12, 0, 0, (int) $matches[2], (int) $matches[3], (int) $matches[1] );
	} else {
		$timestamp = strtotime( $date );
	}

	if ( ! $timestamp ) {
		return '';
	}

	return function_exists( 'wp_date' ) ? wp_date( $format, $timestamp ) : date_i18n( $format, $timestamp );
}

function zaher_account_date_timestamp( $date ) {
	$date = trim( (string) $date );

	if ( '' === $date || zaher_account_is_lifetime_date( $date ) ) {
		return 0;
	}

	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $date, $matches ) ) {
		return mktime( 12, 0, 0, (int) $matches[2], (int) $matches[3], (int) $matches[1] );
	}

	$timestamp = strtotime( $date );

	return $timestamp ? (int) $timestamp : 0;
}

function zaher_account_success_transaction_statuses() {
	if ( ! class_exists( 'MeprTransaction' ) ) {
		return array( 'complete', 'confirmed', 'refunded' );
	}

	return array(
		MeprTransaction::$complete_str,
		MeprTransaction::$confirmed_str,
		MeprTransaction::$refunded_str,
	);
}

function zaher_account_payment_transaction_types() {
	if ( ! class_exists( 'MeprTransaction' ) ) {
		return array( 'payment', 'wc_transaction', 'fallback' );
	}

	return array(
		MeprTransaction::$payment_str,
		MeprTransaction::$woo_txn_str,
		MeprTransaction::$fallback_str,
	);
}

function zaher_account_payment_display_total( $txn ) {
	if ( ! $txn instanceof MeprTransaction ) {
		return 0.0;
	}

	$total = isset( $txn->total ) ? (float) $txn->total : 0.0;

	if (
		class_exists( 'MeprTransaction' ) &&
		class_exists( 'MeprSubscription' ) &&
		isset( $txn->txn_type ) &&
		MeprTransaction::$subscription_confirmation_str === (string) $txn->txn_type
	) {
		$sub = method_exists( $txn, 'subscription' ) ? $txn->subscription() : false;

		if ( $sub instanceof MeprSubscription ) {
			if ( ! empty( $sub->trial ) && (int) $sub->trial_days > 0 ) {
				return isset( $sub->trial_total ) ? (float) $sub->trial_total : 0.0;
			}

			return isset( $sub->total ) ? (float) $sub->total : $total;
		}
	}

	return $total;
}

function zaher_account_is_subscription_confirmation_payment( $txn, $payment_subscription_ids = array() ) {
	if ( ! class_exists( 'MeprTransaction' ) || ! $txn instanceof MeprTransaction ) {
		return false;
	}

	$txn_type = isset( $txn->txn_type ) ? (string) $txn->txn_type : '';
	if ( MeprTransaction::$subscription_confirmation_str !== $txn_type ) {
		return false;
	}

	$status = isset( $txn->status ) ? (string) $txn->status : '';
	if ( MeprTransaction::$confirmed_str !== $status && MeprTransaction::$complete_str !== $status ) {
		return false;
	}

	$subscription_id = isset( $txn->subscription_id ) ? (int) $txn->subscription_id : 0;
	if ( $subscription_id <= 0 || in_array( $subscription_id, array_map( 'intval', (array) $payment_subscription_ids ), true ) ) {
		return false;
	}

	return zaher_account_payment_display_total( $txn ) > 0.00001;
}

function zaher_account_is_displayable_payment_transaction( $txn, $payment_subscription_ids = array() ) {
	if ( ! $txn instanceof MeprTransaction ) {
		return false;
	}

	if ( zaher_account_is_subscription_confirmation_payment( $txn, $payment_subscription_ids ) ) {
		return true;
	}

	$status = isset( $txn->status ) ? (string) $txn->status : '';
	if ( ! in_array( $status, zaher_account_success_transaction_statuses(), true ) ) {
		return false;
	}

	$txn_type = isset( $txn->txn_type ) ? (string) $txn->txn_type : '';
	if ( $txn_type && ! in_array( $txn_type, zaher_account_payment_transaction_types(), true ) ) {
		return false;
	}

	if ( class_exists( 'MeprTransaction' ) && MeprTransaction::$refunded_str === $status ) {
		return true;
	}

	return zaher_account_payment_display_total( $txn ) > 0.00001;
}

function zaher_filter_account_payment_transactions( $transactions, $limit = 10 ) {
	$normalized               = array();
	$payment_subscription_ids = array();

	foreach ( (array) $transactions as $txn ) {
		if ( ! $txn instanceof MeprTransaction && isset( $txn->id ) ) {
			$txn = new MeprTransaction( $txn->id );
		}

		if ( ! $txn instanceof MeprTransaction ) {
			continue;
		}

		$normalized[] = $txn;

		$status   = isset( $txn->status ) ? (string) $txn->status : '';
		$txn_type = isset( $txn->txn_type ) ? (string) $txn->txn_type : '';
		if (
			isset( $txn->subscription_id ) &&
			(int) $txn->subscription_id > 0 &&
			in_array( $status, zaher_account_success_transaction_statuses(), true ) &&
			in_array( $txn_type, zaher_account_payment_transaction_types(), true ) &&
			zaher_account_payment_display_total( $txn ) > 0.00001
		) {
			$payment_subscription_ids[] = (int) $txn->subscription_id;
		}
	}

	$payment_subscription_ids = array_unique( $payment_subscription_ids );
	$filtered                 = array();

	foreach ( $normalized as $txn ) {
		if ( ! zaher_account_is_displayable_payment_transaction( $txn, $payment_subscription_ids ) ) {
			continue;
		}

		$filtered[] = $txn;

		if ( count( $filtered ) >= $limit ) {
			break;
		}
	}

	return $filtered;
}

function zaher_account_payment_status_label( $status, $txn = null ) {
	$status = (string) $status;

	if ( class_exists( 'MeprTransaction' ) ) {
		if ( $txn instanceof MeprTransaction && zaher_account_is_subscription_confirmation_payment( $txn ) ) {
			return 'Plaćeno';
		}

		if ( MeprTransaction::$complete_str === $status ) {
			return 'Plaćeno';
		}

		if ( MeprTransaction::$confirmed_str === $status ) {
			return 'Potvrđeno';
		}

		if ( MeprTransaction::$refunded_str === $status ) {
			return 'Refundirano';
		}
	}

	$labels = array(
		'complete'  => 'Plaćeno',
		'confirmed' => 'Potvrđeno',
		'refunded'  => 'Refundirano',
	);

	return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
}

function zaher_account_payment_status_class( $status ) {
	if ( class_exists( 'MeprTransaction' ) && MeprTransaction::$refunded_str === $status ) {
		return 'pending';
	}

	return 'success';
}

function zaher_account_payment_invoice_url( $txn ) {
	if ( ! $txn instanceof MeprTransaction || ! class_exists( 'MePdfInvoicesCtrl' ) || ! class_exists( 'MeprUtils' ) ) {
		return '';
	}

	if ( ! zaher_account_is_displayable_payment_transaction( $txn ) || zaher_account_payment_display_total( $txn ) <= 0.00001 ) {
		return '';
	}

	return MeprUtils::admin_url(
		'admin-ajax.php',
		array( 'download_invoice', 'mepr_invoices_nonce' ),
		array(
			'action' => 'mepr_download_invoice',
			'txn'    => $txn->id,
		)
	);
}

function zaher_account_payment_has_invoice_links( $transactions ) {
	foreach ( (array) $transactions as $txn ) {
		if ( zaher_account_payment_invoice_url( $txn ) ) {
			return true;
		}
	}

	return false;
}

function zaher_account_product_group_key( $product ) {
	if ( ! $product ) {
		return '';
	}

	$product_id = isset( $product->ID ) ? (int) $product->ID : ( isset( $product->id ) ? (int) $product->id : 0 );
	$group      = method_exists( $product, 'group' ) ? $product->group() : false;
	$group_id   = 0;

	if ( is_object( $group ) ) {
		$group_id = isset( $group->ID ) ? (int) $group->ID : ( isset( $group->id ) ? (int) $group->id : 0 );
	}

	if ( $group_id > 0 ) {
		return 'group:' . $group_id;
	}

	return $product_id > 0 ? 'product:' . $product_id : '';
}

function zaher_filter_account_subscription_rows( $rows ) {
	if ( empty( $rows ) || ! class_exists( 'MeprSubscription' ) ) {
		return (array) $rows;
	}

	$contexts           = array();
	$current_group_keys = array();

	foreach ( (array) $rows as $row ) {
		$type      = isset( $row->sub_type ) ? trim( (string) $row->sub_type ) : 'subscription';
		$status    = isset( $row->status ) ? (string) $row->status : '';
		$group_key = '';

		if ( 'subscription' === $type ) {
			$sub = new MeprSubscription( $row->id );

			if ( ! empty( $sub->id ) ) {
				$status    = isset( $sub->status ) ? (string) $sub->status : $status;
				$product   = method_exists( $sub, 'product' ) ? $sub->product() : null;
				$group_key = zaher_account_product_group_key( $product );
			}

			if ( in_array( $status, array( MeprSubscription::$active_str, MeprSubscription::$suspended_str ), true ) && $group_key ) {
				$current_group_keys[ $group_key ] = true;
			}
		} elseif ( class_exists( 'MeprTransaction' ) ) {
			$txn = new MeprTransaction( $row->id );

			if ( ! empty( $txn->id ) ) {
				$product   = method_exists( $txn, 'product' ) ? $txn->product() : null;
				$group_key = zaher_account_product_group_key( $product );
			}

			if ( $group_key && isset( $row->active ) && false !== strpos( (string) $row->active, 'mepr-active' ) ) {
				$current_group_keys[ $group_key ] = true;
			}
		}

		$contexts[] = array(
			'row'       => $row,
			'type'      => $type,
			'status'    => $status,
			'group_key' => $group_key,
		);
	}

	$filtered              = array();
	$shown_cancelled_groups = array();

	foreach ( $contexts as $context ) {
		$is_cancelled_subscription = 'subscription' === $context['type']
			&& MeprSubscription::$cancelled_str === $context['status'];

		if ( $is_cancelled_subscription && $context['group_key'] ) {
			if ( isset( $current_group_keys[ $context['group_key'] ] ) ) {
				continue;
			}

			if ( isset( $shown_cancelled_groups[ $context['group_key'] ] ) ) {
				continue;
			}

			$shown_cancelled_groups[ $context['group_key'] ] = true;
		}

		$filtered[] = $context['row'];
	}

	return $filtered;
}

function zaher_account_get_subscription_transactions( $sub ) {
	if ( ! $sub instanceof MeprSubscription || ! class_exists( 'MeprTransaction' ) || ! method_exists( 'MeprTransaction', 'get_all_by_subscription_id' ) ) {
		return array();
	}

	$transactions = MeprTransaction::get_all_by_subscription_id( $sub->id );
	$normalized   = array();

	foreach ( (array) $transactions as $txn ) {
		if ( ! $txn instanceof MeprTransaction && isset( $txn->id ) ) {
			$txn = new MeprTransaction( $txn->id );
		}

		if ( $txn instanceof MeprTransaction && ! empty( $txn->id ) ) {
			$normalized[] = $txn;
		}
	}

	usort(
		$normalized,
		function( $a, $b ) {
			$a_time = ! empty( $a->created_at ) ? strtotime( $a->created_at ) : 0;
			$b_time = ! empty( $b->created_at ) ? strtotime( $b->created_at ) : 0;

			if ( $a_time === $b_time ) {
				return (int) $b->id <=> (int) $a->id;
			}

			return $b_time <=> $a_time;
		}
	);

	return $normalized;
}

function zaher_account_latest_subscription_transaction( $sub, $successful_only = true ) {
	$transactions = zaher_account_get_subscription_transactions( $sub );

	foreach ( $transactions as $txn ) {
		if ( ! $successful_only || in_array( (string) $txn->status, zaher_account_success_transaction_statuses(), true ) ) {
			return $txn;
		}
	}

	return method_exists( $sub, 'latest_txn' ) ? $sub->latest_txn() : false;
}

function zaher_account_subscription_period_end( $sub ) {
	$transactions = zaher_account_get_subscription_transactions( $sub );
	$latest_end   = '';
	$latest_ts    = 0;

	foreach ( $transactions as $txn ) {
		if ( ! in_array( (string) $txn->status, zaher_account_success_transaction_statuses(), true ) ) {
			continue;
		}

		if ( empty( $txn->expires_at ) || zaher_account_is_lifetime_date( $txn->expires_at ) ) {
			return $txn->expires_at;
		}

		$expires_ts = zaher_account_date_timestamp( $txn->expires_at );
		if ( $expires_ts > $latest_ts ) {
			$latest_ts  = $expires_ts;
			$latest_end = $txn->expires_at;
		}
	}

	return $latest_end;
}

function zaher_account_stripe_subscription_period_end( $sub ) {
	if ( ! $sub instanceof MeprSubscription || empty( $sub->subscr_id ) || 0 !== strpos( (string) $sub->subscr_id, 'sub_' ) ) {
		return '';
	}

	$cache_key = 'zaher_acc_stripe_period_' . md5( (string) $sub->subscr_id );
	$cached    = get_transient( $cache_key );

	if ( is_string( $cached ) && '' !== $cached ) {
		return $cached;
	}

	$pm = method_exists( $sub, 'payment_method' ) ? $sub->payment_method() : null;
	if ( ! is_object( $pm ) || ! method_exists( $pm, 'retrieve_subscription' ) ) {
		return '';
	}

	try {
		$stripe_sub = $pm->retrieve_subscription( $sub->subscr_id );
	} catch ( Exception $e ) {
		return '';
	}

	$current_period_end = isset( $stripe_sub->current_period_end ) ? (int) $stripe_sub->current_period_end : 0;
	if ( $current_period_end <= 0 ) {
		return '';
	}

	$period_end = function_exists( 'wp_date' )
		? wp_date( 'Y-m-d H:i:s', $current_period_end )
		: date_i18n( 'Y-m-d H:i:s', $current_period_end );

	set_transient( $cache_key, $period_end, 15 * MINUTE_IN_SECONDS );

	return $period_end;
}

function zaher_account_subscription_date_display( $sub ) {
	if ( ! $sub instanceof MeprSubscription ) {
		return array(
			'label' => '',
			'value' => '',
		);
	}

	$status     = isset( $sub->status ) ? (string) $sub->status : '';
	$period_end = zaher_account_subscription_period_end( $sub );
	$stripe_end = zaher_account_stripe_subscription_period_end( $sub );

	if ( $stripe_end && ! zaher_account_is_lifetime_date( $stripe_end ) ) {
		$stripe_ts = zaher_account_date_timestamp( $stripe_end );
		$period_ts = zaher_account_date_timestamp( $period_end );

		if ( $stripe_ts > $period_ts ) {
			$period_end = $stripe_end;
		}
	}

	if ( MeprSubscription::$active_str === $status && ! empty( $sub->next_billing_at ) ) {
		$date = $sub->next_billing_at;

		if ( $period_end && ! zaher_account_is_lifetime_date( $period_end ) ) {
			$next_ts   = zaher_account_date_timestamp( $date );
			$period_ts = zaher_account_date_timestamp( $period_end );

			if ( $period_ts > $next_ts + DAY_IN_SECONDS ) {
				$date = $period_end;
			}
		}

		return array(
			'label' => 'Sljedeća naplata',
			'value' => zaher_account_is_lifetime_date( $date ) ? 'Doživotno' : zaher_account_format_date( $date ),
		);
	}

	$date = $period_end ? $period_end : ( isset( $sub->expires_at ) ? $sub->expires_at : '' );

	if ( zaher_account_is_lifetime_date( $date ) ) {
		return array(
			'label' => 'Ističe',
			'value' => 'Doživotno',
		);
	}

	return array(
		'label' => MeprSubscription::$cancelled_str === $status ? 'Pristup vrijedi do' : 'Ističe',
		'value' => zaher_account_format_date( $date ),
	);
}

function zaher_get_account_subscription_context( $sub_id ) {
	if ( ! is_user_logged_in() || ! class_exists( 'MeprSubscription' ) ) {
		return new WP_Error( 'unavailable', 'Pretplata trenutno nije dostupna.' );
	}

	$sub_id = absint( $sub_id );
	if ( ! $sub_id ) {
		return new WP_Error( 'missing_subscription', 'Pretplata nije pronađena.' );
	}

	$sub = new MeprSubscription( $sub_id );
	if ( empty( $sub->id ) ) {
		return new WP_Error( 'missing_subscription', 'Pretplata nije pronađena.' );
	}

	if ( (int) $sub->user_id !== get_current_user_id() ) {
		return new WP_Error( 'forbidden', 'Nemaš pristup ovoj pretplati.' );
	}

	$product = method_exists( $sub, 'product' ) ? $sub->product() : null;
	$pm      = method_exists( $sub, 'payment_method' ) ? $sub->payment_method() : null;

	return array(
		'sub'     => $sub,
		'product' => $product,
		'gateway' => $pm,
	);
}

function zaher_account_subscription_action_available( $action, $sub, $pm = null ) {
	if ( ! $sub instanceof MeprSubscription ) {
		return false;
	}

	$action       = sanitize_key( $action );
	$mepr_options = class_exists( 'MeprOptions' ) ? MeprOptions::fetch() : null;
	$status       = isset( $sub->status ) ? $sub->status : '';

	switch ( $action ) {
		case 'update':
			return MeprSubscription::$pending_str !== $status
				&& MeprSubscription::$cancelled_str !== $status
				&& MeprSubscription::$suspended_str !== $status
				&& ( ! method_exists( $sub, 'in_grace_period' ) || ! $sub->in_grace_period() )
				&& is_object( $pm )
				&& ( ! class_exists( 'MeprBaseRealGateway' ) || $pm instanceof MeprBaseRealGateway )
				&& method_exists( $pm, 'can' )
				&& $pm->can( 'update-subscriptions' )
				&& method_exists( $pm, 'display_update_account_form' );

		case 'upgrade':
			$product = method_exists( $sub, 'product' ) ? $sub->product() : null;
			$group   = $product && method_exists( $product, 'group' ) ? $product->group() : false;

			return MeprSubscription::$pending_str !== $status
				&& $group
				&& method_exists( $group, 'products' )
				&& method_exists( $group, 'buyable_products' )
				&& count( $group->products( 'ids' ) ) > 1
				&& count( $group->buyable_products() ) >= 1;

		case 'cancel':
			return is_object( $mepr_options )
				&& ! empty( $mepr_options->allow_cancel_subs )
				&& MeprSubscription::$active_str === $status
				&& is_object( $pm )
				&& method_exists( $pm, 'can' )
				&& $pm->can( 'cancel-subscriptions' )
				&& method_exists( $pm, 'process_cancel_subscription' );

		case 'suspend':
			return is_object( $mepr_options )
				&& ! empty( $mepr_options->allow_suspend_subs )
				&& MeprSubscription::$active_str === $status
				&& ( ! method_exists( $sub, 'in_free_trial' ) || ! $sub->in_free_trial() )
				&& is_object( $pm )
				&& method_exists( $pm, 'can' )
				&& $pm->can( 'suspend-subscriptions' )
				&& method_exists( $pm, 'process_suspend_subscription' );

		case 'resume':
			return is_object( $mepr_options )
				&& ! empty( $mepr_options->allow_suspend_subs )
				&& MeprSubscription::$suspended_str === $status
				&& is_object( $pm )
				&& method_exists( $pm, 'can' )
				&& $pm->can( 'suspend-subscriptions' )
				&& method_exists( $pm, 'process_resume_subscription' );
	}

	return false;
}

function zaher_store_profile_errors( $user_id, $errors ) {
	$errors = array_values( array_filter( array_map( 'wp_strip_all_tags', (array) $errors ) ) );
	if ( empty( $errors ) ) {
		return;
	}

	set_transient( 'zaher_profile_errors_' . absint( $user_id ), $errors, 5 * MINUTE_IN_SECONDS );
}

function zaher_get_profile_errors( $user_id ) {
	$key    = 'zaher_profile_errors_' . absint( $user_id );
	$errors = get_transient( $key );

	if ( false !== $errors ) {
		delete_transient( $key );
	}

	return is_array( $errors ) ? $errors : array();
}

add_action( 'admin_post_zaher_account_subscription_action', 'zaher_handle_account_subscription_action' );
function zaher_handle_account_subscription_action() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/prijava/' ) );
		exit;
	}

	$action = isset( $_POST['subscription_action'] ) ? sanitize_key( wp_unslash( $_POST['subscription_action'] ) ) : '';
	$sub_id = isset( $_POST['sub_id'] ) ? absint( $_POST['sub_id'] ) : 0;
	$nonce  = isset( $_POST['zaher_account_subscription_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['zaher_account_subscription_nonce'] ) ) : '';

	$redirect_url = zaher_account_tab_url( 'subscription' );

	if ( ! in_array( $action, array( 'cancel', 'suspend', 'resume' ), true ) || ! wp_verify_nonce( $nonce, 'zaher_account_subscription_' . $action . '_' . $sub_id ) ) {
		wp_safe_redirect( add_query_arg( 'account_error', 'security', $redirect_url ) );
		exit;
	}

	$context = zaher_get_account_subscription_context( $sub_id );
	if ( is_wp_error( $context ) ) {
		wp_safe_redirect( add_query_arg( 'account_error', $context->get_error_code(), $redirect_url ) );
		exit;
	}

	$sub = $context['sub'];
	$pm  = $context['gateway'];

	if ( ! zaher_account_subscription_action_available( $action, $sub, $pm ) ) {
		wp_safe_redirect( add_query_arg( 'account_error', 'not_available', $redirect_url ) );
		exit;
	}

	try {
		if ( 'cancel' === $action ) {
			$pm->process_cancel_subscription( $sub->id );
			$message = 'cancelled';
		} elseif ( 'suspend' === $action ) {
			$pm->process_suspend_subscription( $sub->id );
			$message = 'suspended';
		} else {
			$pm->process_resume_subscription( $sub->id );
			$message = 'resumed';
		}

		wp_safe_redirect( add_query_arg( 'account_message', $message, $redirect_url ) );
		exit;
	} catch ( Exception $e ) {
		wp_safe_redirect( add_query_arg( 'account_error', 'action_failed', $redirect_url ) );
		exit;
	}
}

function zaher_account_notice_text( $type, $code ) {
	$code = sanitize_key( $code );

	if ( 'success' === $type ) {
		$messages = array(
			'cancelled' => 'Pretplata je otkazana.',
			'suspended' => 'Pretplata je zaustavljena.',
			'resumed'   => 'Pretplata je ponovno aktivna.',
		);

		return isset( $messages[ $code ] ) ? $messages[ $code ] : '';
	}

	$errors = array(
		'security'             => 'Sigurnosna provjera nije uspjela. Pokušaj ponovno.',
		'unavailable'          => 'Pretplata trenutno nije dostupna.',
		'missing_subscription' => 'Pretplata nije pronađena.',
		'forbidden'            => 'Nemaš pristup ovoj pretplati.',
		'not_available'        => 'Ova akcija trenutno nije dostupna za tvoju pretplatu.',
		'action_failed'        => 'Akciju nije moguće dovršiti. Pokušaj ponovno ili nam se javi.',
	);

	return isset( $errors[ $code ] ) ? $errors[ $code ] : 'Došlo je do greške. Pokušaj ponovno.';
}

function zaher_account_subscription_back_link( $url ) {
	?>
	<a class="account-page__back-link" href="<?php echo esc_url( $url ); ?>">
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
			<path d="M19 12H5M11 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round"></path>
		</svg>
		<span>Natrag</span>
	</a>
	<?php
}

function zaher_render_account_subscription_action( $action, $sub_id ) {
	$action  = sanitize_key( $action );
	$context = zaher_get_account_subscription_context( $sub_id );
	$back_url = zaher_account_tab_url( 'subscription' );

	if ( is_wp_error( $context ) ) {
		?>
		<div class="account-page__card account-page__action-card">
			<?php zaher_account_subscription_back_link( $back_url ); ?>
			<div class="account-page__message account-page__message--error"><?php echo esc_html( $context->get_error_message() ); ?></div>
		</div>
		<?php
		return;
	}

	$sub     = $context['sub'];
	$product = $context['product'];
	$pm      = $context['gateway'];
	$title   = $product && ! empty( $product->post_title ) ? $product->post_title : 'Pretplata';

	if ( ! zaher_account_subscription_action_available( $action, $sub, $pm ) ) {
		?>
		<div class="account-page__card account-page__action-card">
			<?php zaher_account_subscription_back_link( $back_url ); ?>
			<h2 class="account-page__action-title">Akcija nije dostupna</h2>
			<p class="account-page__action-text">Ova opcija trenutno nije dostupna za tvoju pretplatu.</p>
		</div>
		<?php
		return;
	}

	$action_titles = array(
		'update'  => 'Ažuriraj karticu',
		'upgrade' => 'Promijeni plan',
		'cancel'  => 'Otkaži pretplatu',
		'suspend' => 'Zaustavi pretplatu',
		'resume'  => 'Nastavi pretplatu',
	);
	?>
	<div class="account-page__card account-page__action-card account-page__action-card--<?php echo esc_attr( $action ); ?>">
		<?php zaher_account_subscription_back_link( $back_url ); ?>
		<div class="account-page__action-header">
			<span class="account-page__label"><?php echo esc_html( $title ); ?></span>
			<h2 class="account-page__action-title"><?php echo esc_html( isset( $action_titles[ $action ] ) ? $action_titles[ $action ] : 'Upravljanje pretplatom' ); ?></h2>
		</div>

		<?php if ( 'update' === $action ) : ?>
			<div class="account-page__memberpress-form">
				<?php
				$errors = array();
				$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';

				if ( isset( $_GET['errors'] ) ) {
					$errors[] = sanitize_text_field( wp_unslash( $_GET['errors'] ) );
				}

				if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
					$errors = method_exists( $pm, 'validate_update_account_form' ) ? $pm->validate_update_account_form( $errors ) : $errors;

					if ( empty( $errors ) && method_exists( $pm, 'process_update_account_form' ) ) {
						try {
							$pm->process_update_account_form( $sub->id );
							$message = 'Podaci za plaćanje su ažurirani.';
						} catch ( Exception $e ) {
							$errors[] = $e->getMessage();
						}
					}
				}

				$pm->display_update_account_form( $sub->id, $errors, $message );
				?>
			</div>
		<?php elseif ( 'upgrade' === $action ) : ?>
			<?php
			$group = $product && method_exists( $product, 'group' ) ? $product->group() : false;
			$plans = $group && method_exists( $group, 'products' ) ? $group->products() : array();
			$user  = method_exists( $sub, 'user' ) ? $sub->user() : null;
			?>
			<p class="account-page__action-text">Odaberi plan na koji želiš prijeći. MemberPress će na checkoutu obračunati promjenu plana.</p>
			<div class="account-page__plan-options">
				<?php $has_plan_options = false; ?>
				<?php foreach ( $plans as $plan ) : ?>
					<?php
					if ( ! $plan instanceof MeprProduct || (int) $plan->ID === (int) $product->ID || ! $plan->can_you_buy_me() ) {
						continue;
					}

					$has_plan_options = true;
					$terms = class_exists( 'MeprProductsHelper' ) ? MeprProductsHelper::product_terms( $plan, $user ) : '';
					?>
					<div class="account-page__plan-option">
						<div>
							<h3><?php echo esc_html( $plan->post_title ); ?></h3>
							<?php if ( $terms ) : ?>
								<p><?php echo esc_html( wp_strip_all_tags( $terms ) ); ?></p>
							<?php endif; ?>
						</div>
						<a class="button button--small" href="<?php echo esc_url( $plan->url() ); ?>">Odaberi</a>
					</div>
				<?php endforeach; ?>
				<?php if ( ! $has_plan_options ) : ?>
					<div class="account-page__message account-page__message--error account-page__message--inline">
						Trenutno nema dostupnih planova za promjenu.
					</div>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<?php
			$copy = array(
				'cancel'  => 'Nakon potvrde pretplata se više neće automatski obnavljati.',
				'suspend' => 'Nakon potvrde pretplata će biti zaustavljena.',
				'resume'  => 'Nakon potvrde pretplata će se ponovno aktivirati.',
			);
			$button_labels = array(
				'cancel'  => 'Da, otkaži pretplatu',
				'suspend' => 'Da, zaustavi pretplatu',
				'resume'  => 'Da, nastavi pretplatu',
			);
			?>
			<p class="account-page__action-text"><?php echo esc_html( $copy[ $action ] ); ?></p>
			<form class="account-page__action-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="zaher_account_subscription_action">
				<input type="hidden" name="subscription_action" value="<?php echo esc_attr( $action ); ?>">
				<input type="hidden" name="sub_id" value="<?php echo esc_attr( $sub->id ); ?>">
				<?php wp_nonce_field( 'zaher_account_subscription_' . $action . '_' . $sub->id, 'zaher_account_subscription_nonce' ); ?>
				<button class="button button--small <?php echo 'cancel' === $action ? 'account-page__danger-button' : ''; ?>" type="submit">
					<?php echo esc_html( $button_labels[ $action ] ); ?>
				</button>
				<a class="button button--small button--hollow" href="<?php echo esc_url( $back_url ); ?>">Odustani</a>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Handle password change from custom account page.
 */
add_action( 'admin_post_zaher_change_password', 'zaher_handle_password_change' );
function zaher_handle_password_change() {
	if ( ! is_user_logged_in() ) {
		wp_redirect( home_url( '/prijava/' ) );
		exit;
	}

	if ( ! isset( $_POST['zaher_password_nonce'] ) || ! wp_verify_nonce( $_POST['zaher_password_nonce'], 'zaher_change_password' ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=password&password_error=nonce' ) );
		exit;
	}

	$current_password = isset( $_POST['current_password'] ) ? $_POST['current_password'] : '';
	$new_password     = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';
	$confirm_password = isset( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : '';

	$user = wp_get_current_user();

	// Verify current password
	if ( ! wp_check_password( $current_password, $user->user_pass, $user->ID ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=password&password_error=wrong' ) );
		exit;
	}

	// Check if new passwords match
	if ( $new_password !== $confirm_password ) {
		wp_redirect( home_url( '/moj-racun/?tab=password&password_error=mismatch' ) );
		exit;
	}

	// Update password
	wp_set_password( $new_password, $user->ID );

	// Re-login the user
	wp_set_auth_cookie( $user->ID );

	wp_redirect( home_url( '/moj-racun/?tab=password&password_changed=1' ) );
	exit;
}

/**
 * Handle profile updates from custom account page.
 */
add_action( 'admin_post_zaher_update_profile', 'zaher_handle_profile_update' );
function zaher_handle_profile_update() {
	if ( ! is_user_logged_in() ) {
		wp_redirect( home_url( '/prijava/' ) );
		exit;
	}

	if ( ! isset( $_POST['zaher_profile_nonce'] ) || ! wp_verify_nonce( $_POST['zaher_profile_nonce'], 'zaher_update_profile' ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=nonce' ) );
		exit;
	}

	$user = wp_get_current_user();

	$first_name = '';
	if ( isset( $_POST['user_first_name'] ) ) {
		$first_name = sanitize_text_field( wp_unslash( $_POST['user_first_name'] ) );
	} elseif ( isset( $_POST['first_name'] ) ) {
		$first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ) );
	}

	$last_name = '';
	if ( isset( $_POST['user_last_name'] ) ) {
		$last_name = sanitize_text_field( wp_unslash( $_POST['user_last_name'] ) );
	} elseif ( isset( $_POST['last_name'] ) ) {
		$last_name = sanitize_text_field( wp_unslash( $_POST['last_name'] ) );
	}

	$email         = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
	$email_changed = $email !== $user->user_email;

	if ( $email === '' || ! is_email( $email ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=email' ) );
		exit;
	}

	$existing_id = email_exists( $email );
	if ( $existing_id && (int) $existing_id !== (int) $user->ID ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=exists' ) );
		exit;
	}

	$memberpress_errors = array();
	$mepr_current_user  = null;

	if ( class_exists( 'MeprUser' ) ) {
		$mepr_current_user = new MeprUser( $user->ID );
	}

	if ( class_exists( 'MeprUsersCtrl' ) ) {
		$memberpress_errors = MeprUsersCtrl::validate_extra_profile_fields( null, null, $mepr_current_user );
	}

	if ( class_exists( 'MeprUser' ) ) {
		$account_params = $_POST;
		$account_params['user_first_name'] = $first_name;
		$account_params['user_last_name']  = $last_name;
		$account_params['user_email']      = $email;

		$memberpress_errors = MeprUser::validate_account( $account_params, $memberpress_errors );
	}

	if ( class_exists( 'MeprHooks' ) ) {
		$memberpress_errors = MeprHooks::apply_filters( 'mepr-validate-account', $memberpress_errors, $mepr_current_user );
	} else {
		$memberpress_errors = apply_filters( 'mepr-validate-account', $memberpress_errors, $mepr_current_user );
	}

	if ( ! empty( $memberpress_errors ) ) {
		if ( function_exists( 'zaher_store_profile_errors' ) ) {
			zaher_store_profile_errors( $user->ID, $memberpress_errors );
		}

		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=memberpress' ) );
		exit;
	}

	$display_name = trim( $first_name . ' ' . $last_name );
	if ( $display_name === '' ) {
		$display_name = $user->display_name;
	}

	$result = wp_update_user(
		array(
			'ID'           => $user->ID,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'user_email'   => $email,
			'display_name' => $display_name,
			'nickname'     => $display_name,
		)
	);

	if ( is_wp_error( $result ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=save' ) );
		exit;
	}

	if ( class_exists( 'MeprUsersCtrl' ) ) {
		MeprUsersCtrl::save_extra_profile_fields( $user->ID, true );
	}

	if ( class_exists( 'MeprUser' ) ) {
		$address_fields    = array();
		$address_submitted = false;
		foreach ( array( 'mepr-address-one', 'mepr-address-two', 'mepr-address-city', 'mepr-address-state', 'mepr-address-zip', 'mepr-address-country' ) as $address_key ) {
			$address_submitted             = $address_submitted || isset( $_POST[ $address_key ] );
			$address_fields[ $address_key ] = isset( $_POST[ $address_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $address_key ] ) ) : '';
		}

		$mepr_user = new MeprUser( $user->ID );
		if ( $address_submitted ) {
			$mepr_user->set_address( $address_fields );
		}

		if ( class_exists( 'MeprHooks' ) ) {
			if ( $email_changed ) {
				MeprHooks::do_action( 'mepr-update-new-user-email', $mepr_user );
			}

			MeprHooks::do_action( 'mepr-save-account', $mepr_user );
		}
	}

	wp_redirect( home_url( '/moj-racun/?tab=profile&profile_updated=1' ) );
	exit;
}
