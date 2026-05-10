<?php
/**
 * Custom login and password reset helpers.
 */

function theme_auth_page_url( $args = array() ) {
	$page = get_page_by_path( 'prijava' );
	$url  = $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/prijava/' );

	if ( ! empty( $args ) && is_array( $args ) ) {
		$url = add_query_arg( $args, $url );
	}

	return $url;
}

function theme_auth_action_url( $action, $args = array() ) {
	$args           = is_array( $args ) ? $args : array();
	$args['action'] = sanitize_key( $action );

	return theme_auth_page_url( $args );
}

function theme_catalog_page_url() {
	$page = get_page_by_path( 'katalog' );

	return $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/katalog/' );
}

function theme_pricing_page_url() {
	$page = get_page_by_path( 'cjenik' );

	return $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/cjenik/' );
}

function theme_is_memberpress_auth_context() {
	if ( is_page( 'prijava' ) ) {
		return true;
	}

	if ( class_exists( 'MeprOptions' ) ) {
		$mepr_options = MeprOptions::fetch();
		if ( is_object( $mepr_options ) && ! empty( $mepr_options->login_page_id ) && is_page( (int) $mepr_options->login_page_id ) ) {
			return true;
		}
	}

	return false;
}

add_filter( 'login_url', 'theme_custom_login_url', 20, 3 );
function theme_custom_login_url( $login_url, $redirect, $force_reauth ) {
	$args = array();

	if ( ! empty( $redirect ) ) {
		$args['redirect_to'] = $redirect;
	}

	if ( $force_reauth ) {
		$args['reauth'] = 1;
	}

	return theme_auth_page_url( $args );
}

add_filter( 'lostpassword_url', 'theme_custom_lostpassword_url', 20, 2 );
function theme_custom_lostpassword_url( $lostpassword_url, $redirect ) {
	$args = array();

	if ( ! empty( $redirect ) ) {
		$args['redirect_to'] = $redirect;
	}

	return theme_auth_action_url( 'forgot_password', $args );
}

add_filter( 'register_url', 'theme_custom_register_url' );
function theme_custom_register_url( $register_url ) {
	return theme_pricing_page_url();
}

add_action( 'wp_enqueue_scripts', 'theme_enqueue_memberpress_auth_styles', 1000001 );
function theme_enqueue_memberpress_auth_styles() {
	if ( ! theme_is_memberpress_auth_context() ) {
		return;
	}

	wp_enqueue_style(
		'my-mepr-auth-style',
		get_stylesheet_directory_uri() . '/dist/assets/css/' . foundationpress_asset_path( 'app.css' ),
		array( 'mp-pro-login' ),
		wp_get_theme()->get( 'Version' )
	);
}

add_filter( 'mepr_design_style_handles', 'theme_allow_memberpress_auth_styles' );
function theme_allow_memberpress_auth_styles( $handles ) {
	$handles[] = 'my-mepr-auth-style';

	return array_unique( $handles );
}
