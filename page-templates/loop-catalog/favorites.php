<?php

// Favorites functionality
$user_id = get_current_user_id();
$user_favorites = get_user_favorites($user_id); // Get the user's favorite post IDs
$user_favorites_count = get_user_favorites_count($user_id);
$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg'; // Custom placeholder image URL

if ( $user_favorites ) : ?>

	<div class="grid-container full" style="margin-bottom: 60px !important">
		<h2>Moji favoriti</h2>

		<div class="cards">
			<?php // Re-run the loop to display the favorite posts ?>
			<?php while ( have_posts() ) : the_post(); ?>
					<?php if ( in_array( get_the_ID(), $user_favorites ) ) : ?>
							<div class="cards__item">
									<a href="<?php the_permalink(); ?>">
											<figure class="cards__figure">
													<?php
													$thumbnail_url = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'fp-small' ) : $placeholder_url;
													?>
													<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php the_title_attribute(); ?>">

													<?php if ( $video_length = get_field( 'video_length', get_the_ID() ) ) : ?>
															<div class="cards__length">
																	<?php echo esc_html( $video_length ); ?>
															</div>
													<?php endif; ?>
											</figure>
											<div class="cards__header">
													<h3 class="cards__title"><?php the_title(); ?></h3>
											</div>
									</a>
							</div>
					<?php endif; ?>
			<?php endwhile; ?>
		</div>
	</div>

<?php endif; ?>
