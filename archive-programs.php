<?php get_header(); ?>


<main class="main-container full">

		<?php get_template_part( 'template-parts/modules/page-header' ); ?>

		<?php
		$terms = get_terms( array(
			'taxonomy' => 'catalog',
			'hide_empty' => false,
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

</main>

<?php get_footer();
