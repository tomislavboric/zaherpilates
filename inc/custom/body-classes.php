<?php
/**
 * Body class helpers.
 */
add_filter( 'body_class', 'theme_member_body_class' );
function theme_member_body_class( $classes ) {
	if ( is_user_logged_in() ) {
		$classes[] = 'is-member';
	}
	return $classes;
}
