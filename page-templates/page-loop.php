<?php
/*
Template Name: Loop
*/
get_header(); ?>

	<main class="main">

		<?php

		get_template_part( 'page-templates/loop/hero' );

		get_template_part( 'page-templates/loop/programs' );

		get_template_part( 'page-templates/loop/pricing-plans' );

		get_template_part( 'page-templates/loop/video' );

		get_template_part( 'page-templates/loop/faq' );

		get_template_part( 'page-templates/loop/intro' );

		// get_template_part( 'page-templates/loop/about' );

		// get_template_part( 'page-templates/loop/testimonials' );

		?>

	</main>

<?php get_footer();
