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

// This function will change the posts_per_page parameter to -1 (which means all posts) for the main query on the taxonomy archive page for 'kategorija'. Adjust the is_tax('kategorija') check if necessary to target the correct pages.
function modify_main_query( $query ) {
	if ( ! is_admin() && $query->is_main_query() ) {
			if ( is_tax('kategorija') ) { // If you're on a taxonomy archive page
					$query->set( 'posts_per_page', -1 );
			}
	}
}
add_action( 'pre_get_posts', 'modify_main_query' );

// check if the user is an admin or has a membership subscription.
function user_has_membership() {
	if(class_exists('MeprUser')) {
			$user = MeprUser::get_current_user();

			// Check if the user has a specific membership subscription.
			// Replace 'membership-slug' with the slug of your membership.
			if($user->is_member('membership-slug')) {
					return true;
			}
	}
	return false;
}

// whether a user has any membership at all (rather than a specific membership)
function user_has_memberships() {
	if(class_exists('MeprUser')) {
			$user = MeprUser::get_current_user();

			// Get all memberships
			$memberships = get_posts(array(
					'post_type' => 'memberpressproduct',
					'numberposts' => -1
			));

			// Check if the user has any membership subscription.
			foreach($memberships as $membership) {
					if($user->is_member($membership->post_name)) {
							return true;
					}
			}
	}
	return false;
}

// Check if a user is an admin or has a membership:
function user_is_admin_or_has_memberships() {
	if(current_user_can('administrator') || user_has_memberships()) {
			return true;
	}
	return false;
}

// This code will add a CSS rule to the admin page for editing tags and terms, which will hide the description field. Please be aware that the field is just hidden, not removed entirely, and it won't affect your site's frontend.
function remove_tax_description_field() {
	echo '<style>
			.term-description-wrap {
					display: none;
			}
	</style>';
}

add_action( 'admin_head-edit-tags.php', 'remove_tax_description_field' );
add_action( 'admin_head-term.php', 'remove_tax_description_field' );

// Remove admin bar for non-wp users
add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
	if (!current_user_can('administrator') && !is_admin()) {
		show_admin_bar(false);
	}
}
