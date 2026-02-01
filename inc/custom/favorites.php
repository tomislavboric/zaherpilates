<?php
/**
 * Customize favorites button markup inside the favorites list.
 */
add_filter( 'favorites/button/html', 'zaher_favorites_list_button_html', 10, 4 );
function zaher_favorites_list_button_html( $html, $post_id, $favorited, $site_id ) {
	if ( empty( $GLOBALS['zaher_favorites_list_context'] ) ) {
		return $html;
	}

	$label = $favorited ? 'Ukloni' : 'Dodaj';
	$icon  = $favorited ? 'xmark' : 'heart';

	return zaher_lineicon_svg( $icon ) . '<span class="simplefavorite-label">' . esc_html( $label ) . '</span>';
}
