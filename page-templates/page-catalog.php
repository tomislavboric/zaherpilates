<?php
/*
Template Name: Loop Catalog
*/
get_header(); ?>

	<main class="main">

		<?php get_template_part( 'template-parts/modules/page-header' ); ?>

		    <?php get_template_part( 'template-parts/modules/page-header' ); ?>

		<section class="favorite-videos">
			<?php get_template_part( 'page-templates/loop-catalog/favorites' ); ?>
		</section>

		<section class="all-videos">

			<div class="grid-container full">

				<?php
					// Does the user have any favorite Programs?
					$fav_ids = get_user_favorites(
						get_current_user_id(),
						get_current_blog_id(),
						array(
							'post_type' => 'programs',   // your CPT
							'status'    => 'publish',
						)
					);
				?>

				<?php if ( ! empty( $fav_ids ) ) : ?>
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

										// Check if the "Hide Category" field is set to true
										$hide_category = get_field( 'hide_category', $term );
										if ( $hide_category ) {
												continue; // Skip this category if "Hide Category" is checked
										}

										$image = get_field( 'image', $term );
										$term_image_url = $image ? $image['sizes']['fp-small'] : $placeholder_url; // Use custom placeholder
										?>
										<div class="cards__item">
												<a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
														<figure class="cards__figure">
																<img src="<?php echo esc_url( $term_image_url ); ?>" alt="<?php echo esc_attr( $term->name ); ?>">
																<div class="cards__count">
																		<span class="material-icons">video_library</span>
																		<?php echo esc_html( $term->count ); ?>
																</div>
														</figure>
														<div class="cards__header">
																<h3 class="cards__title"><?php echo esc_html( $term->name ); ?></h3>
														</div>
												</a>
										</div>
								<?php endforeach; ?>
						</div>
				<?php else : ?>
						<p>No categories found.</p>
				<?php endif; ?>


			</div>

		</section>

	</main>

<?php get_footer();
