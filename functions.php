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

add_filter( 'rank_math/metabox/priority', function( $priority ) {
	return 'low';
});

// GTM u <head>
add_action('wp_head', 'zaher_add_gtm_head', 1);
function zaher_add_gtm_head() {
  ?>
  <!-- Google Tag Manager -->
  <script>
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-TZGV2HD6');
  </script>
  <!-- End Google Tag Manager -->
  <?php
}

// GTM noscript u <body>
add_action('wp_body_open', 'zaher_add_gtm_body');
function zaher_add_gtm_body() {
  ?>
  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TZGV2HD6"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->
  <?php
}

function remove_memberpress_meta_boxes() {
    remove_meta_box('mepr_metabox_postbox', 'post', 'normal');
}
add_action('add_meta_boxes', 'remove_memberpress_meta_boxes', 20);
