<?php
/*
Template Name: Loop Catalog
*/

get_header();

$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg'; // Custom placeholder image URL

the_post();

?>

	<main class="main">

		<?php get_template_part( 'template-parts/modules/page-header' ); ?>

		<section class="favorite-videos">
			<?php get_template_part( 'page-templates/loop-catalog/favorites' ); ?>
		</section>

		<section class="all-videos">
			xxx
			<?php get_template_part( 'page-templates/loop-catalog/catalog' ); ?>

		</section>

	</main>

<?php get_footer();
