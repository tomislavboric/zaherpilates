<?php
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
function remove_memberpress_meta_boxes() {
    remove_meta_box('mepr_metabox_postbox', 'post', 'normal');
}
add_action('add_meta_boxes', 'remove_memberpress_meta_boxes', 20);
