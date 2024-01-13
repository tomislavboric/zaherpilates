<?php
/*
Template Name: Home
*/
get_header(); ?>

	<main class="main">

		<?php get_template_part( 'page-templates/home/hero' ); ?>

		<?php get_template_part( 'page-templates/home/features' ); ?>

		<?php get_template_part( 'page-templates/home/about' ); ?>

		<?php get_template_part( 'page-templates/home/booking' ); ?>

		<?php get_template_part( 'page-templates/home/testimonials' ); ?>

		<?php get_template_part( 'page-templates/home/newsletter' ); ?>

	</main>

<?php get_footer();
