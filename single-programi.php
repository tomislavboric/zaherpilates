<?php
/**
 * The template for displaying all single posts and attachments
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

get_header(); ?>

<main class="main">

	<div class="grid-container">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php // get_template_part( 'template-parts/content', '' ); ?>

			<article>
				<div class="section__video">
					<?php get_template_part( 'template-parts/components/single-video' ); ?>
				</div>

				<header class="section__header">
					<h1 class="section__title"><?php the_title(); ?></h1>
					<div class="section__desc">Lorem ipsum dolor sit amet</div>
				</header>
			</article>


			<?php the_post_navigation(); ?>
			<?php comments_template(); ?>
	</div>

	<?php endwhile; ?>

</main>
<?php get_footer();
