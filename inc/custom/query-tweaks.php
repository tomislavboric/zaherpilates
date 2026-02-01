<?php
// This function will change the posts_per_page parameter to -1 (which means all posts) for the main query on the taxonomy archive page for 'kategorija'. Adjust the is_tax('kategorija') check if necessary to target the correct pages.
function modify_main_query( $query ) {
	if ( ! is_admin() && $query->is_main_query() ) {
			if ( is_tax('kategorija') ) { // If you're on a taxonomy archive page
					$query->set( 'posts_per_page', -1 );
			}
	}
}
add_action( 'pre_get_posts', 'modify_main_query' );
