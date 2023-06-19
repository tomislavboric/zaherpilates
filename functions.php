<?php
/**
 * Author: Ole Fredrik Lie
 * URL: http://olefredrik.com
 *
 * FoundationPress functions and definitions
 *
 * Set up the theme and provides some helper functions, which are used in the
 * theme as custom template tags. Others are attached to action and filter
 * hooks in WordPress to change core functionality.
 *
 * @link https://codex.wordpress.org/Theme_Development
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

 define('THEME_DIR', get_stylesheet_directory() . '/');
 define('THEME_URI', get_stylesheet_directory_uri() . '/');

 require THEME_DIR . 'inc/init.php';

 // Extracts ID from Vimeo link
 function getVimeoVideoId($vimeoUrl) {
	$parts = parse_url($vimeoUrl);
	if (isset($parts['path'])) {
			$pathParts = explode('/', trim($parts['path'], '/'));
			return $pathParts[count($pathParts) - 1];
	}
	return false;
}

// Updates function for WooCommerce membership so it could be easily replaced with some other membership plugin like memberpress
function custom_can_user_view_content($plan_slug = '') {
	return wc_memberships_is_user_member(null, $plan_slug) || current_user_can('administrator');
}

