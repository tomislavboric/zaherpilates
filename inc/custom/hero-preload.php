<?php
/**
 * Announce the hero background image in <head>.
 *
 * The hero is a CSS background (see scss/sections/_hero.scss), so the browser
 * cannot discover the file until it has fetched and parsed the stylesheet. On
 * the two templates that render it the hero is also the largest element, so
 * that late discovery is what holds LCP back — compressing the file further
 * would not move it. Preloading lets the download start with the document.
 */

add_action( 'wp_head', 'theme_preload_hero_background', 1 );
function theme_preload_hero_background() {
	if ( ! theme_renders_hero_background() ) {
		return;
	}

	printf(
		'<link rel="preload" as="image" href="%s" type="image/webp" fetchpriority="high" />' . "\n",
		esc_url( get_stylesheet_directory_uri() . '/dist/assets/images/hero-loop.webp' )
	);
}

/**
 * Whether the current request renders the .hero section.
 *
 * @return bool
 */
function theme_renders_hero_background() {
	// Members get the catalog at "/", not the marketing hero.
	if ( function_exists( 'theme_is_catalog_page' ) && theme_is_catalog_page() ) {
		return false;
	}

	$template = get_page_template_slug( get_queried_object_id() );

	return in_array(
		$template,
		array( 'page-templates/page-home.php', 'page-templates/page-loop.php' ),
		true
	);
}
