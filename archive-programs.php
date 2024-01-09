<?php get_header(); ?>


<main class="main">

		<?php get_template_part( 'template-parts/modules/page-header' ); ?>

		<div class="grid-container full" style="margin-bottom: 40px !important">

		<?php $user_favorites = get_user_favorites(); // Gets current user's favorite post IDs ?>

			<?php if ( $user_favorites ) : ?>
				<h2>Tvoji favoriti</h2>

				<?php while ( have_posts() ) : the_post(); ?>

					<div class="cards">

						<?php if (in_array(get_the_ID(), $user_favorites)) :

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

						<?php endif; ?>

					</div>

				<?php endwhile; ?>

			<?php endif; ?>

		</div>

		<div class="grid-container full">

			<?php if ( $user_favorites ) : ?>
				<h2>Sve kategorije</h2>
			<?php endif; ?>

			<?php
			$terms = get_terms( array(
				'taxonomy' => 'catalog',
				'hide_empty' => true,
			) );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
				<div class="cards">
					<?php foreach ( $terms as $term ) :

					// vars
					$image = get_field('image', $term);
					?>
						<div class="cards__item">
							<a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
								<figure class="cards__figure">
									<img src="<?php echo $image['url']; ?>" alt="<?php echo $term->name; ?>">
									<div class="cards__count">
										<span class="material-icons">video_library</span>
										<?php echo $term->count; ?>
									</div>
								</figure>
								<div class="cards__header">
									<h3 class="cards__title"><?php echo $term->name; ?></h3>
								</div>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				No categories found.
			<?php endif; ?>

		</div>

</main>

<?php get_footer();
