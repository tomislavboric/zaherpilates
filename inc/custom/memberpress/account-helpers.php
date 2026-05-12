<?php
/**
 * Custom account page display helpers, transaction filtering,
 * subscription queries, and context resolution.
 */

function theme_account_is_lifetime_date( $date ) {
	$date = trim( (string) $date );

	if ( '' === $date || false !== stripos( $date, '0000-00' ) ) {
		return true;
	}

	return class_exists( 'MeprUtils' ) && MeprUtils::db_lifetime() === $date;
}

function theme_account_format_date( $date, $format = 'j. F Y.' ) {
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

function theme_account_date_timestamp( $date ) {
	$date = trim( (string) $date );

	if ( '' === $date || theme_account_is_lifetime_date( $date ) ) {
		return 0;
	}

	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $date, $matches ) ) {
		return mktime( 12, 0, 0, (int) $matches[2], (int) $matches[3], (int) $matches[1] );
	}

	$timestamp = strtotime( $date );

	return $timestamp ? (int) $timestamp : 0;
}

function theme_account_success_transaction_statuses() {
	if ( ! class_exists( 'MeprTransaction' ) ) {
		return array( 'complete', 'confirmed', 'refunded' );
	}

	return array(
		MeprTransaction::$complete_str,
		MeprTransaction::$confirmed_str,
		MeprTransaction::$refunded_str,
	);
}

function theme_account_payment_transaction_types() {
	if ( ! class_exists( 'MeprTransaction' ) ) {
		return array( 'payment', 'wc_transaction', 'fallback' );
	}

	return array(
		MeprTransaction::$payment_str,
		MeprTransaction::$woo_txn_str,
		MeprTransaction::$fallback_str,
	);
}

function theme_account_payment_display_total( $txn ) {
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

function theme_account_is_subscription_confirmation_payment( $txn, $payment_subscription_ids = array() ) {
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

	return theme_account_payment_display_total( $txn ) > 0.00001;
}

function theme_account_is_displayable_payment_transaction( $txn, $payment_subscription_ids = array() ) {
	if ( ! $txn instanceof MeprTransaction ) {
		return false;
	}

	if ( theme_account_is_subscription_confirmation_payment( $txn, $payment_subscription_ids ) ) {
		return true;
	}

	$status = isset( $txn->status ) ? (string) $txn->status : '';
	if ( ! in_array( $status, theme_account_success_transaction_statuses(), true ) ) {
		return false;
	}

	$txn_type = isset( $txn->txn_type ) ? (string) $txn->txn_type : '';
	if ( $txn_type && ! in_array( $txn_type, theme_account_payment_transaction_types(), true ) ) {
		return false;
	}

	if ( class_exists( 'MeprTransaction' ) && MeprTransaction::$refunded_str === $status ) {
		return true;
	}

	return theme_account_payment_display_total( $txn ) > 0.00001;
}

function theme_filter_account_payment_transactions( $transactions, $limit = 10 ) {
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
			in_array( $status, theme_account_success_transaction_statuses(), true ) &&
			in_array( $txn_type, theme_account_payment_transaction_types(), true ) &&
			theme_account_payment_display_total( $txn ) > 0.00001
		) {
			$payment_subscription_ids[] = (int) $txn->subscription_id;
		}
	}

	$payment_subscription_ids = array_unique( $payment_subscription_ids );
	$filtered                 = array();

	foreach ( $normalized as $txn ) {
		if ( ! theme_account_is_displayable_payment_transaction( $txn, $payment_subscription_ids ) ) {
			continue;
		}

		$filtered[] = $txn;

		if ( count( $filtered ) >= $limit ) {
			break;
		}
	}

	return $filtered;
}

function theme_account_payment_status_label( $status, $txn = null ) {
	$status = (string) $status;

	if ( class_exists( 'MeprTransaction' ) ) {
		if ( $txn instanceof MeprTransaction && theme_account_is_subscription_confirmation_payment( $txn ) ) {
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

function theme_account_payment_status_class( $status ) {
	if ( class_exists( 'MeprTransaction' ) && MeprTransaction::$refunded_str === $status ) {
		return 'pending';
	}

	return 'success';
}

function theme_account_payment_invoice_url( $txn ) {
	if ( ! $txn instanceof MeprTransaction || ! class_exists( 'MePdfInvoicesCtrl' ) || ! class_exists( 'MeprUtils' ) ) {
		return '';
	}

	if ( ! theme_account_is_displayable_payment_transaction( $txn ) || theme_account_payment_display_total( $txn ) <= 0.00001 ) {
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

function theme_account_payment_has_invoice_links( $transactions ) {
	foreach ( (array) $transactions as $txn ) {
		if ( theme_account_payment_invoice_url( $txn ) ) {
			return true;
		}
	}

	return false;
}

function theme_account_product_group_key( $product ) {
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

function theme_filter_account_subscription_rows( $rows ) {
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
				$group_key = theme_account_product_group_key( $product );
			}

			if ( in_array( $status, array( MeprSubscription::$active_str, MeprSubscription::$suspended_str ), true ) && $group_key ) {
				$current_group_keys[ $group_key ] = true;
			}
		} elseif ( class_exists( 'MeprTransaction' ) ) {
			$txn = new MeprTransaction( $row->id );

			if ( ! empty( $txn->id ) ) {
				$product   = method_exists( $txn, 'product' ) ? $txn->product() : null;
				$group_key = theme_account_product_group_key( $product );
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

function theme_account_get_subscription_transactions( $sub ) {
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

function theme_account_latest_subscription_transaction( $sub, $successful_only = true ) {
	$transactions = theme_account_get_subscription_transactions( $sub );

	foreach ( $transactions as $txn ) {
		if ( ! $successful_only || in_array( (string) $txn->status, theme_account_success_transaction_statuses(), true ) ) {
			return $txn;
		}
	}

	return method_exists( $sub, 'latest_txn' ) ? $sub->latest_txn() : false;
}

function theme_account_subscription_period_end( $sub ) {
	$transactions = theme_account_get_subscription_transactions( $sub );
	$latest_end   = '';
	$latest_ts    = 0;

	foreach ( $transactions as $txn ) {
		if ( ! in_array( (string) $txn->status, theme_account_success_transaction_statuses(), true ) ) {
			continue;
		}

		if ( empty( $txn->expires_at ) || theme_account_is_lifetime_date( $txn->expires_at ) ) {
			return $txn->expires_at;
		}

		$expires_ts = theme_account_date_timestamp( $txn->expires_at );
		if ( $expires_ts > $latest_ts ) {
			$latest_ts  = $expires_ts;
			$latest_end = $txn->expires_at;
		}
	}

	return $latest_end;
}

function theme_account_stripe_subscription_period_end( $sub ) {
	if ( ! $sub instanceof MeprSubscription || empty( $sub->subscr_id ) || 0 !== strpos( (string) $sub->subscr_id, 'sub_' ) ) {
		return '';
	}

	$cache_key = 'theme_acc_stripe_period_' . md5( (string) $sub->subscr_id );
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
	} catch ( Throwable $e ) {
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

function theme_account_subscription_date_display( $sub ) {
	if ( ! $sub instanceof MeprSubscription ) {
		return array(
			'label' => '',
			'value' => '',
		);
	}

	$status     = isset( $sub->status ) ? (string) $sub->status : '';
	$period_end = theme_account_subscription_period_end( $sub );
	$stripe_end = theme_account_stripe_subscription_period_end( $sub );

	if ( $stripe_end && ! theme_account_is_lifetime_date( $stripe_end ) ) {
		$stripe_ts = theme_account_date_timestamp( $stripe_end );
		$period_ts = theme_account_date_timestamp( $period_end );

		if ( $stripe_ts > $period_ts ) {
			$period_end = $stripe_end;
		}
	}

	if ( MeprSubscription::$active_str === $status && ! empty( $sub->next_billing_at ) ) {
		$date = $sub->next_billing_at;

		if ( $period_end && ! theme_account_is_lifetime_date( $period_end ) ) {
			$next_ts   = theme_account_date_timestamp( $date );
			$period_ts = theme_account_date_timestamp( $period_end );

			if ( $period_ts > $next_ts + DAY_IN_SECONDS ) {
				$date = $period_end;
			}
		}

		return array(
			'label' => 'Sljedeća naplata',
			'value' => theme_account_is_lifetime_date( $date ) ? 'Doživotno' : theme_account_format_date( $date ),
		);
	}

	$date = $period_end ? $period_end : ( isset( $sub->expires_at ) ? $sub->expires_at : '' );

	if ( theme_account_is_lifetime_date( $date ) ) {
		return array(
			'label' => 'Ističe',
			'value' => 'Doživotno',
		);
	}

	return array(
		'label' => MeprSubscription::$cancelled_str === $status ? 'Pristup vrijedi do' : 'Ističe',
		'value' => theme_account_format_date( $date ),
	);
}

function theme_get_account_subscription_context( $sub_id ) {
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

function theme_account_gateway_can( $pm, $capability ) {
	if ( ! is_object( $pm ) || ! method_exists( $pm, 'can' ) ) {
		return false;
	}

	try {
		return (bool) $pm->can( $capability );
	} catch ( Throwable $e ) {
		if ( function_exists( 'error_log' ) ) {
			error_log(
				sprintf(
					'Zaher account gateway capability check failed: gateway=%s capability=%s error=%s message=%s',
					get_class( $pm ),
					(string) $capability,
					get_class( $e ),
					$e->getMessage()
				)
			);
		}
	}

	return false;
}

function theme_account_subscription_action_available( $action, $sub, $pm = null ) {
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
				&& theme_account_gateway_can( $pm, 'update-subscriptions' )
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
				&& theme_account_gateway_can( $pm, 'cancel-subscriptions' )
				&& method_exists( $pm, 'process_cancel_subscription' );

		case 'suspend':
			return is_object( $mepr_options )
				&& ! empty( $mepr_options->allow_suspend_subs )
				&& MeprSubscription::$active_str === $status
				&& ( ! method_exists( $sub, 'in_free_trial' ) || ! $sub->in_free_trial() )
				&& is_object( $pm )
				&& theme_account_gateway_can( $pm, 'suspend-subscriptions' )
				&& method_exists( $pm, 'process_suspend_subscription' );

		case 'resume':
			return is_object( $mepr_options )
				&& ! empty( $mepr_options->allow_suspend_subs )
				&& MeprSubscription::$suspended_str === $status
				&& is_object( $pm )
				&& theme_account_gateway_can( $pm, 'suspend-subscriptions' )
				&& method_exists( $pm, 'process_resume_subscription' );
	}

	return false;
}
