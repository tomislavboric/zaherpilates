<?php
/*
Template Name: category
*/
get_header();

// vars
$term = get_queried_object(); // This gets the current taxonomy term object
$image = get_field('image', $term);
$description = get_field('description', $term);

?>

<main class="main">

	<header class="category__header">
		<div class="grid-container full">
			<h1 class="category__title"><?php single_term_title(); ?></h1>
			<div class="category__description">
				<?php echo $description; ?>
			</div>
		</div>
	</header>

	<?php if ( have_posts() ) : ?>

		<div class="grid-container full">

			<div class="cards">

				<?php while ( have_posts() ) : the_post();

				// vars
				$vimeoUrl = get_field('video', get_the_ID());
				$video_length = get_field('video_length', get_the_ID());
				$videoId = getVimeoVideoId($vimeoUrl);
				?>

					<div class="cards__item">
						<a href="<?php the_permalink(); ?>">
							<figure class="cards__figure">
								<?php if ( has_post_thumbnail( $post->ID ) ) : ?>
									<img src="<?php the_post_thumbnail_url('fp-small'); ?>" alt="<?php the_title(); ?>">
								<?php else : ?>
									<img
										srcset="
										https://vumbnail.com/<?php echo $videoId; ?>.jpg 640w,
										https://vumbnail.com/<?php echo $videoId; ?>_large.jpg 640w,
										https://vumbnail.com/<?php echo $videoId; ?>_medium.jpg 200w,
										https://vumbnail.com/<?php echo $videoId; ?>_small.jpg 100w
										"
										src="https://vumbnail.com/<?php echo $videoId; ?>.jpg"
										alt="<?php the_title(); ?>"
										width="640"
										height="360"
									/>
								<?php endif; ?>

								<?php if ($video_length) : ?>
									<div class="cards__length">
										<?php echo $video_length; ?>
									</div>
								<?php endif; ?>
							</figure>
							<div class="cards__header">
								<h3 class="cards__title"><?php the_title(); ?></h3>
							</div>
						</a>
					</div>

				<?php endwhile; ?>

			</div>

		</div>

	<?php endif; // End have_posts() check. ?>

</main>

<?php get_footer();
