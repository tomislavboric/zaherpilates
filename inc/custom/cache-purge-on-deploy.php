<?php
/**
 * Purge the page cache when a deploy renames the theme's assets.
 *
 * Assets are revisioned (see dist/assets/css/rev-manifest.json), so a deploy
 * that touches app.css also changes its filename. Cached HTML keeps pointing
 * at the previous name, which the deploy has already removed, and every
 * visitor served from cache gets a 404 for the stylesheet and an unstyled
 * page. The cure is a purge, and the failure mode is silent, so it should not
 * depend on anyone remembering to run one.
 *
 * Only requests that reach PHP can notice, which a cached page does not. In
 * practice login, checkout, wp-admin and wp-cron are all uncacheable, so the
 * purge lands within seconds of a deploy rather than instantly.
 */

add_action( 'init', 'theme_purge_cache_on_asset_revision', 5 );
function theme_purge_cache_on_asset_revision() {
	$manifest = get_stylesheet_directory() . '/dist/assets/css/rev-manifest.json';

	if ( ! is_readable( $manifest ) ) {
		return;
	}

	$revision = md5_file( $manifest );

	if ( ! $revision || get_option( 'theme_asset_revision' ) === $revision ) {
		return;
	}

	// Recorded first: a purge that fails should not queue itself up on every
	// subsequent request.
	update_option( 'theme_asset_revision', $revision, true );

	// A no-op while LiteSpeed Cache is inactive.
	do_action( 'litespeed_purge_all' );
}
