<?php
/**
 *
 * Other Scripts
 *
 */

// Edit Post in new tab
add_filter( 'edit_post_link', function( $link, $post_id, $text )
{
    // Add the target attribute
    if( false === strpos( $link, 'target=' ) )
        $link = str_replace( '<a ', '<a target="_blank" ', $link );

    return $link;
}, 10, 3 );


// Excerpt Length
function excerpt_length_category( $length ) {
if ( in_category( 'Reviews' ) ) {
		return 20;
	}
	else {
		return 80;
	}
}
add_filter( 'excerpt_length', 'excerpt_length_category' );

// Excerpt Read More
function excerpt_readmore($more) {
	//return '... <a href="'. get_permalink($post->ID) . '" class="readmore">' . 'Read More' . '</a>';
	return '...';
}

add_filter('excerpt_more', 'excerpt_readmore');
