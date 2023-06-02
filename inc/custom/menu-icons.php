<?php
/**
 *
 * Add My Account to menu
 *
 */

function add_loginout_link( $items, $args ) {

	$current_user = wp_get_current_user();

	if (is_user_logged_in() && $args->theme_location == 'top-bar-r' || $args->theme_location == 'mobile-nav' ) {
		$items .= '<li class="menu-item menu-item--icon"><a class="button small icon icon--logout" href="'. wp_logout_url( home_url() ) .'"><span>'. __("Odjava") .'</span></a></li>';
	}
	elseif (!is_user_logged_in() && $args->theme_location == 'top-bar-r' || $args->theme_location == 'mobile-nav') {
		$items .= '<li class="menu-item menu-item--icon"><a class="button small icon icon--login" href="' . home_url() . '/prijava/"><span>'. __("Prijava") .'</span></a></li>';
	}
	return $items;
}
add_filter( 'wp_nav_menu_items', 'add_loginout_link', 10, 2 );
