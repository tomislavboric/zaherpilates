<?php
/*
Template Name: Programi
*/
get_header(); ?>

<main class="main-container full">

	<div class="programi__cate">

		<?php
		$terms = get_terms( array(
				'taxonomy' => 'kategorija',
				'hide_empty' => true,
		) );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) :
				foreach ( $terms as $term ) : ?>

					<section class="programi__cat">

						<header class="programi__cat-header">
							<h2 class="programi__cat-title"><a href="<?php echo get_term_link($term); ?>"><?php echo $term->name; ?></a></h2>
							<a href="<?php echo get_term_link($term); ?>">See All</a>
						</header>

						<?php
						$args = array(
								'post_type' => 'programi',
								'orderby'   => 'menu_order',
								'order'     => 'ASC', // Use 'DESC' for descending order
								'tax_query' => array(
										array(
												'taxonomy' => 'kategorija',
												'field'    => 'slug',
												'terms'    => $term->slug,
										),
								),
						);
						$query = new WP_Query( $args );

						if ( $query->have_posts() ) : ?>

						<div class="swiper-container">
							<div class="swiper">

								<div class="swiper-wrapper">

									<?php while ( $query->have_posts() ) : $query->the_post();

									// vars
									$vimeoUrl = get_field('video', get_the_ID());
									$videoId = getVimeoVideoId($vimeoUrl);
									?>
										<div class="swiper-slide">
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
										</div>

									<?php endwhile; ?>

								</div>

								<div class="swiper-pagination"></div>

								<div class="swiper-button-prev"></div>
								<div class="swiper-button-next"></div>

							</div>
						</div>

						<?php endif; ?>
					</section>
					<?php wp_reset_postdata();

				endforeach;
		endif; ?>

	</div>

</main>

<?php get_footer();
