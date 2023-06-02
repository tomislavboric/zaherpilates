<?php
/**
 *
 * Custom project scripts
 *
 */

if ( ! function_exists( 'inweb_scripts' ) ) :
	function inweb_scripts() {

		// Google Fonts
		wp_enqueue_style( 'main-stylesheet', '//fonts.googleapis.com/css?family=Montserrat:700|Open+Sans:400,400i,700&display=swap&subset=latin-ext', array(), '', 'all' );

		// Font Awesome CDN (CSS ver)
		//wp_enqueue_style( 'fontawesome', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/fontawesome.min.css', array(), '', 'all' );

		// Enqueue FontAwesome from CDN. Uncomment the line below if you need FontAwesome.
		//wp_enqueue_script( 'fontawesome', '//kit.fontawesome.com/93e5e0e741.js', array(), '4.7.0', false );

	}

	add_action( 'wp_enqueue_scripts', 'inweb_scripts' );
endif;
