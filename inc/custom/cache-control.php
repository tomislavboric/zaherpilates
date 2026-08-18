<?php
/**
 * Keep member-specific and nonce-bearing responses out of full-page caches.
 *
 * A page cache that keys on the URL alone gets this site wrong in two ways:
 * "/" serves the marketing homepage to visitors and the catalog to members
 * (see custom/catalog-router.php), and the checkout form carries a nonce that
 * expires within a day. The cache plugin's own settings are the first line of
 * defence; this is the second, so a misconfigured plugin or an upstream proxy
 * cannot hand a member the anonymous copy of a page.
 */

add_action( 'template_redirect', 'theme_send_nocache_headers_for_dynamic_pages', 20 );
function theme_send_nocache_headers_for_dynamic_pages() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$reason = theme_uncacheable_response_reason();

	if ( ! $reason ) {
		return;
	}

	nocache_headers();

	// LiteSpeed Cache decides through its own API rather than Cache-Control.
	do_action( 'litespeed_control_set_nocache', $reason );
}

/**
 * Why the current response must not be stored, or an empty string if it may be.
 *
 * @return string
 */
function theme_uncacheable_response_reason() {
	// Members get a different header, a different homepage and their own
	// progress state on every template.
	if ( is_user_logged_in() ) {
		return 'logged-in member';
	}

	// Checkout and thank-you carry mepr_checkout_nonce.
	if ( function_exists( 'theme_is_memberpress_checkout_shell_context' ) && theme_is_memberpress_checkout_shell_context() ) {
		return 'memberpress checkout nonce';
	}

	if ( function_exists( 'theme_is_memberpress_auth_context' ) && theme_is_memberpress_auth_context() ) {
		return 'login form';
	}

	return '';
}
