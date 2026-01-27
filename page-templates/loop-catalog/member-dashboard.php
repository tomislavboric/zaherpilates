<?php
/**
 * Member dashboard blocks for the Catalog page.
 */

if ( ! is_user_logged_in() ) {
	return;
}

$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg';

// Catalog page is a curated ACF layout; this dashboard adds "what to do next" above it.
$search_value = isset( $_GET['katalog_search'] ) ? (string) wp_unslash( $_GET['katalog_search'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$search_value = trim( $search_value );

$quick = isset( $_GET['katalog_quick'] ) ? (string) wp_unslash( $_GET['katalog_quick'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$quick = trim( $quick );

// Optional quick results row.
$quick_query = null;
if ( $search_value !== '' ) {
	$quick_query = new WP_Query(
		array(
			'post_type'           => 'programs',
			's'                   => $search_value,
			'posts_per_page'      => 12,
			'ignore_sticky_posts' => 1,
		)
	);
	$title = 'Rezultati pretrage';
} elseif ( $quick === 'popular' ) {
	$quick_query = new WP_Query(
		array(
			'post_type'           => 'programs',
			'posts_per_page'      => 12,
			'meta_key'            => 'zaher_views',
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'ignore_sticky_posts' => 1,
		)
	);
	$title = 'Najgledanije';
} elseif ( $quick === 'new' ) {
	$quick_query = new WP_Query(
		array(
			'post_type'           => 'programs',
			'posts_per_page'      => 12,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => 1,
		)
	);
	$title = 'Najnovije';
} elseif ( $quick === 'short' ) {
	$quick_query = new WP_Query(
		array(
			'post_type'           => 'programs',
			'posts_per_page'      => 12,
			'meta_key'            => 'zaher_video_length_minutes',
			'meta_type'           => 'NUMERIC',
			'meta_query'          => array(
				array(
					'key'     => 'zaher_video_length_minutes',
					'value'   => 20,
					'compare' => '<=',
					'type'    => 'NUMERIC',
				),
			),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => 1,
		)
	);
	$title = 'Brzi trening (≤ 20 min)';
}

if ( ! ( $quick_query instanceof WP_Query ) ) {
	return;
}

?>

<section class="member-dashboard">
	<div class="grid-container full">
		<div class="member-dashboard__row">
			<h3><?php echo esc_html( $title ); ?></h3>

			<?php if ( $quick_query->have_posts() ) : ?>
				<div class="cards">
					<?php while ( $quick_query->have_posts() ) : $quick_query->the_post(); ?>
						<div class="cards__item">
							<?php if ( function_exists( 'the_favorites_button' ) ) : ?>
								<div class="cards__favorite">
									<?php the_favorites_button( get_the_ID() ); ?>
								</div>
							<?php endif; ?>
							<a href="<?php the_permalink(); ?>">
								<figure class="cards__figure">
									<?php
									$thumb = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'fp-small' ) : $placeholder_url;
									?>
									<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php the_title_attribute(); ?>">
									<?php if ( function_exists( 'zaher_user_completed_program' ) && zaher_user_completed_program( get_the_ID() ) ) : ?>
										<div class="cards__badge" aria-label="Pogledano do kraja">
											<?php echo zaher_lineicon_svg( 'check' ); ?>
										</div>
									<?php endif; ?>
									<?php if ( function_exists( 'get_field' ) && ( $video_length = get_field( 'video_length' ) ) ) : ?>
										<div class="cards__length"><?php echo esc_html( $video_length ); ?></div>
									<?php endif; ?>
								</figure>
								<div class="cards__header">
									<h3 class="cards__title"><?php the_title(); ?></h3>
								</div>
							</a>
						</div>
					<?php endwhile; ?>
				</div>
			<?php else : ?>
				<p>Nema rezultata.</p>
			<?php endif; ?>

		</div>
	</div>
</section>

<?php
wp_reset_postdata();
