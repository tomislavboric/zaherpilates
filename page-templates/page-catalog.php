<?php
/*
Template Name: Loop Catalog
*/
get_header(); ?>

	<main class="main">

		<?php get_template_part( 'template-parts/modules/page-header' ); ?>

		<?php get_template_part( 'page-templates/loop-catalog/favorites' ); ?>

		<?php get_template_part( 'page-templates/loop-catalog/catalog' ); ?>

	</main>

<?php get_footer();
