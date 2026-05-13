<?php
/**
 * Defensive mail handling for MemberPress webhook requests.
 */

add_filter( 'pre_wp_mail', 'theme_skip_memberpress_webhook_mail_when_transport_is_unavailable', 10, 2 );
function theme_skip_memberpress_webhook_mail_when_transport_is_unavailable( $pre_wp_mail, $atts ) {
	if ( null !== $pre_wp_mail || function_exists( 'mail' ) || ! theme_is_memberpress_gateway_notify_request() ) {
		return $pre_wp_mail;
	}

	if ( function_exists( 'error_log' ) ) {
		$subject = isset( $atts['subject'] ) ? wp_strip_all_tags( (string) $atts['subject'] ) : '';
		error_log( 'Zaher skipped wp_mail during MemberPress webhook because PHP mail() is unavailable. Subject: ' . $subject );
	}

	return false;
}

function theme_is_memberpress_gateway_notify_request() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

	if ( is_string( $path ) && false !== strpos( $path, '/mepr/notify/' ) ) {
		return true;
	}

	$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_key( wp_unslash( $_REQUEST['plugin'] ) ) : '';
	$pmt    = isset( $_REQUEST['pmt'] ) ? sanitize_key( wp_unslash( $_REQUEST['pmt'] ) ) : '';
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

	return 'mepr' === $plugin && '' !== $pmt && '' !== $action;
}
