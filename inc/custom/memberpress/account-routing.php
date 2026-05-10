<?php
/**
 * Custom account page URLs, MemberPress route filters, and asset enqueue.
 */

function theme_account_page_url( $args = array() ) {
	$page = get_page_by_path( 'moj-racun' );
	$url  = $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/moj-racun/' );

	if ( ! empty( $args ) && is_array( $args ) ) {
		$url = add_query_arg( $args, $url );
	}

	return $url;
}

function theme_account_tab_url( $tab, $args = array() ) {
	$args        = is_array( $args ) ? $args : array();
	$args['tab'] = sanitize_key( $tab );

	return theme_account_page_url( $args );
}

function theme_account_subscription_action_url( $action, $sub_id, $args = array() ) {
	$args           = is_array( $args ) ? $args : array();
	$args['action'] = sanitize_key( $action );
	$args['sub']    = absint( $sub_id );

	$url = theme_account_page_url( $args );

	if ( 'update' === $args['action'] && function_exists( 'theme_get_account_subscription_context' ) ) {
		$context = theme_get_account_subscription_context( $args['sub'] );
		$pm      = is_wp_error( $context ) ? null : $context['gateway'];

		if ( is_object( $pm ) && method_exists( $pm, 'force_ssl' ) && $pm->force_ssl() ) {
			$url = set_url_scheme( $url, 'https' );
		}
	}

	return $url;
}

add_filter( 'mepr-account-page-permalink', 'theme_use_custom_account_page_for_memberpress_links' );
function theme_use_custom_account_page_for_memberpress_links( $url ) {
	return theme_account_page_url();
}

add_filter( 'mepr-account-nav-home-link', 'theme_memberpress_account_home_link' );
function theme_memberpress_account_home_link( $url ) {
	return theme_account_tab_url( 'profile' );
}

add_filter( 'mepr-account-nav-subscriptions-link', 'theme_memberpress_account_subscriptions_link' );
function theme_memberpress_account_subscriptions_link( $url ) {
	return theme_account_tab_url( 'subscription' );
}

add_filter( 'mepr-account-nav-payments-link', 'theme_memberpress_account_payments_link' );
function theme_memberpress_account_payments_link( $url ) {
	return theme_account_tab_url( 'payments' );
}

add_filter( 'mepr-account-nav-change-password', 'theme_memberpress_account_password_link' );
add_filter( 'mepr-rl-change-password-url', 'theme_memberpress_account_password_link' );
function theme_memberpress_account_password_link( $url ) {
	return theme_account_tab_url( 'password' );
}

/**
 * Redirect legacy MemberPress account navigation to the custom account tabs.
 * Keep operational subscription actions on the custom account page so gateway flows still work.
 */
add_action( 'template_redirect', 'theme_redirect_legacy_memberpress_account_urls', 0 );
function theme_redirect_legacy_memberpress_account_urls() {
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
		wp_safe_redirect( theme_account_tab_url( 'profile' ), 302 );
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

		wp_safe_redirect( theme_account_page_url( $args ), 302 );
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

		wp_safe_redirect( theme_account_page_url( $args ), 302 );
		exit;
	}
}

add_action( 'wp_enqueue_scripts', 'theme_enqueue_memberpress_account_action_assets', 20 );
function theme_enqueue_memberpress_account_action_assets() {
	if ( ! is_page( 'moj-racun' ) || ! isset( $_GET['action'] ) || 'update' !== sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
		return;
	}

	if ( class_exists( 'MeprAccountCtrl' ) ) {
		$account_ctrl = new MeprAccountCtrl();
		$account_ctrl->enqueue_scripts( true );
	}
}
