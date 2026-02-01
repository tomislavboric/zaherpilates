<?php
/**
 * Body class helpers.
 */
add_filter( 'body_class', 'zaher_member_body_class' );
function zaher_member_body_class( $classes ) {
	if ( is_user_logged_in() ) {
		$classes[] = 'is-member';
	}
	return $classes;
}
