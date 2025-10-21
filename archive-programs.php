<?php get_header();

$user_id = get_current_user_id();
$user_favorites = get_user_favorites($user_id); // Get the user's favorite post IDs
$user_favorites_count = get_user_favorites_count($user_id);
$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg'; // Custom placeholder image URL

?>

<main class="main">

    <?php get_template_part( 'template-parts/modules/page-header' ); ?>

		<section class="favorite-videos">
			<?php get_template_part( 'page-templates/loop-catalog/favorites' ); ?>
		</section>

		<section class="all-videos">

			<div class="grid-container full">

				<?php
				// 1) Get ALL favorite IDs for this user (works both single/multisite)
				$fav_ids = get_user_favorites( get_current_user_id() ); // returns array of post IDs

				// 2) Are there any published 'programs' among those IDs?
				$has_favorited_programs = false;

				if ( ! empty( $fav_ids ) ) {
					$q = new WP_Query( array(
						'post_type'      => 'programs',   // make sure this slug matches on live
						'post_status'    => 'publish',
						'post__in'       => $fav_ids,
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'no_found_rows'  => true,
					) );
					$has_favorited_programs = $q->have_posts();
					wp_reset_postdata();
				}

				// 3) Print the heading only if the user truly has favorited programs
				if ( $has_favorited_programs ) : ?>
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

<?php get_footer(); ?>
