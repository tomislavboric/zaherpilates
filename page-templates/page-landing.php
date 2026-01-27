<?php
/*
Template Name: Landing page
Template Post Type: page
*/

get_header();
?>

	<main class="main landing landing--black-friday">

		<?php while ( have_posts() ) : the_post(); ?>
			<?php the_content(); ?>
		<?php endwhile; ?>

	</main>

<?php
get_footer();
