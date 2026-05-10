<?php
/**
 * Register custom blocks and block/pattern categories.
 */

if ( ! function_exists( 'theme_register_block_categories' ) ) :
	function theme_register_block_categories( $categories, $post ) {
		$slugs = wp_list_pluck( $categories, 'slug' );
		if ( ! in_array( 'theme-landing', $slugs, true ) ) {
			$categories[] = array(
				'slug'  => 'theme-landing',
				'title' => __( 'Landing Sections', 'foundationpress' ),
			);
		}

		return $categories;
	}

	add_filter( 'block_categories_all', 'theme_register_block_categories', 10, 2 );
endif;

if ( ! function_exists( 'theme_register_pattern_categories' ) ) :
	function theme_register_pattern_categories() {
		if ( ! function_exists( 'register_block_pattern_category' ) || ! class_exists( 'WP_Block_Patterns_Registry' ) ) {
			return;
		}

		$registry = WP_Block_Patterns_Registry::get_instance();
		if ( ! $registry->is_registered( 'theme-landing' ) ) {
			register_block_pattern_category(
				'theme-landing',
				array(
					'label' => __( 'Landing Sections', 'foundationpress' ),
				)
			);
		}
	}

	add_action( 'init', 'theme_register_pattern_categories' );
endif;

if ( ! function_exists( 'theme_register_landing_blocks' ) ) :
	function theme_register_landing_blocks() {
		$blocks = array(
			'landing-hero',
			'landing-countdown',
			'landing-countdown-final',
			'landing-intro',
			'landing-story',
			'landing-offer',
			'landing-testimonials',
			'landing-faq',
			'landing-final-cta',
		);

		foreach ( $blocks as $block ) {
			$path = get_template_directory() . '/blocks/' . $block;
			if ( file_exists( $path . '/block.json' ) ) {
				register_block_type( $path );
			}
		}
	}

	add_action( 'init', 'theme_register_landing_blocks' );
endif;
