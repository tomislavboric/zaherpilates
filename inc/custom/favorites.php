<?php
/**
 * Customize favorites button markup inside the favorites list.
 */
add_filter( 'favorites/button/html', 'theme_favorites_list_button_html', 10, 4 );
function theme_favorites_list_button_html( $html, $post_id, $favorited, $site_id ) {
	if ( empty( $GLOBALS['theme_favorites_list_context'] ) ) {
		return $html;
	}

	$label = $favorited ? 'Ukloni' : 'Dodaj';
	$icon  = $favorited ? 'xmark' : 'heart';

	return theme_lineicon_svg( $icon ) . '<span class="simplefavorite-label">' . esc_html( $label ) . '</span>';
}
