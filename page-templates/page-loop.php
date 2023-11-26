<?php
/*
Template Name: Loop
*/
get_header(); ?>

	<main class="main">

		<?php get_template_part( 'page-templates/loop/hero' ); ?>

		<?php get_template_part( 'page-templates/loop/intro' ); ?>

		<?php get_template_part( 'page-templates/loop/about' ); ?>

		<?php get_template_part( 'page-templates/loop/testimonials' ); ?>

		<?php get_template_part( 'page-templates/loop/programs' ); ?>

		<?php get_template_part( 'page-templates/loop/pricing-plans' ); ?>

		<?php get_template_part( 'page-templates/loop/video' ); ?>

		<?php get_template_part( 'page-templates/loop/faq' ); ?>

	</main>

<?php get_footer();
