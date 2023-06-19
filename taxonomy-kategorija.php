<?php
/*
Template Name: Programi
*/
get_header(); ?>

<main class="main-container full">

	<?php if ( have_posts() ) : ?>

		<div class="programi__grid">

			<?php while ( have_posts() ) : the_post();

			// vars
			$vimeoUrl = get_field('video', get_the_ID());
			$videoId = getVimeoVideoId($vimeoUrl);
			?>

				<div class="programi__item">
					<a class="programi__link" href="<?php the_permalink(); ?>">
						<figure>
							<img
								srcset="
								https://vumbnail.com/<?php echo $videoId; ?>.jpg 640w,
								https://vumbnail.com/<?php echo $videoId; ?>_large.jpg 640w,
								https://vumbnail.com/<?php echo $videoId; ?>_medium.jpg 200w,
								https://vumbnail.com/<?php echo $videoId; ?>_small.jpg 100w
								"
								sizes="(max-width: 640px) 100vw, 640px"
								src="https://vumbnail.com/<?php echo $videoId; ?>.jpg"
								alt="Vimeo Thumbnail"
								width="640"
								height="360"
							/>
						</figure>
						<div class="programi__content">
							<h3 class="programi__title"><?php the_title(); ?></h3>
						</div>
					</a>
				</div>

			<?php endwhile; ?>

		</div>

	<?php endif; // End have_posts() check. ?>

</main>

<?php get_footer();
