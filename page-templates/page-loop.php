<?php
/*
Template Name: Loop
*/
get_header(); ?>

	<main class="main">

		<?php get_template_part( 'page-templates/loop/loop-hero' ); ?>

		<?php get_template_part( 'page-templates/loop/loop-intro' ); ?>

		<?php get_template_part( 'page-templates/loop/loop-about' ); ?>

		<?php get_template_part( 'page-templates/loop/loop-testimonials' ); ?>

		<?php get_template_part( 'page-templates/loop/loop-programs' ); ?>

		<?php get_template_part( 'page-templates/loop/loop-pricing-plans' ); ?>

		<?php get_template_part( 'page-templates/loop/loop-video' ); ?>

		<?php get_template_part( 'page-templates/loop/loop-faq' ); ?>

	</main>

<?php get_footer();
