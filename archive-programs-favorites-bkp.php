<?php get_header(); ?>

<main class="main-container full">

	<div class="tab-container">
		<div class="tab active" data-tab-target="#all-programs">Svi programi</div>
		<div class="tab" data-tab-target="#favorites">Favoriti</div>
	</div>

	<div id="all-programs" class="content-container active">
		<?php
		$terms = get_terms( array(
			'taxonomy' => 'kategorija',
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
								<img src="<?php echo $image['url']; ?>" alt="<?php echo $image['alt']; ?>">
								<div class="cards__figure-header">
									<h3 class="cards__figure-title"><?php echo esc_html( $term->name ); ?></h3>
								</div>
								<div class="cards__count">
									<span class="material-icons">video_library</span>
									<?php echo $term->count; ?>
								</div>
							</figure>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			No categories found.
		<?php endif; ?>
	</div>

	<div id="favorites" class="content-container">
				<?php
				// vars
				$image = get_field('image', $term);
				?>
					<div class="cards__item">
						<a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
							<figure class="cards__figure">
								<img src="<?php echo $image['url']; ?>" alt="<?php echo $image['alt']; ?>">
								<?php /* <div class="cards__figure-header">
									<h3 class="cards__figure-title"><?php echo esc_html( $term->name ); ?></h3>
								</div> */ ?>
								<div class="cards__count">
									<span class="material-icons">video_library</span>
									<?php echo $term->count; ?>
								</div>
							</figure>
							<div class="cards__header">
								<h3 class="cards__title"><?php the_title(); ?></h3>
								<div class="cards__length"><?php echo $formattedVideoDuration; ?></div>
							</div>
						</a>
					</div>
	</div>

</main>

<?php get_footer();
