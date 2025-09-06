<?php
/**
 *
 * Custom project scripts
 *
 */

if ( ! function_exists( 'inweb_scripts' ) ) :
	function inweb_scripts() {
		  wp_enqueue_script(
				'emoji-picker-element',
				'https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js',
				[],
				null,
				true
			);
			// Tell WP this is a <script type="module">
			if (function_exists('wp_script_add_data')) {
				wp_script_add_data('emoji-picker-element', 'type', 'module');
			}
	}

	add_action( 'wp_enqueue_scripts', 'inweb_scripts' );
endif;
