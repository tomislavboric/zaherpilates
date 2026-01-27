<?php
$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg';

if ( ! is_user_logged_in() ) {
	return;
}

$user_id         = get_current_user_id();
$in_progress_ids = get_query_var( 'zaher_in_progress_ids', array() );
if ( empty( $in_progress_ids ) && function_exists( 'zaher_get_in_progress_program_ids' ) ) {
	$in_progress_ids = zaher_get_in_progress_program_ids( $user_id );
}
if ( $in_progress_ids ) {
	$in_progress_ids = array_values(
		array_filter(
			array_map( 'absint', $in_progress_ids ),
			function ( $program_id ) {
				return $program_id
					&& get_post_status( $program_id ) === 'publish'
					&& get_post_type( $program_id ) === 'programs';
			}
		)
	);
}

if ( empty( $in_progress_ids ) ) :
	?>
	<div class="grid-container full" id="nastavi" style="margin-bottom:60px!important">
		<div class="empty-state">
			<div class="empty-state__icon">
				<?php echo zaher_lineicon_svg( 'play-circle' ); ?>
			</div>
			<h3 class="empty-state__title">Nema započetih treninga</h3>
			<p class="empty-state__text">Kad započneš trening, moći ćeš ga nastaviti ovdje.</p>
		</div>
	</div>
	<?php
	return;
endif;

$in_progress_query = new WP_Query(
	array(
		'post_type'           => 'programs',
		'post__in'            => $in_progress_ids,
		'orderby'             => 'post__in',
		'posts_per_page'      => -1,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	)
);
?>

<div class="grid-container full" id="nastavi" style="margin-bottom:60px!important">
	<?php if ( $in_progress_query->have_posts() ) : ?>
		<div class="cards">
			<?php while ( $in_progress_query->have_posts() ) : $in_progress_query->the_post(); ?>
				<?php
				$progress_value   = function_exists( 'zaher_get_program_progress' ) ? zaher_get_program_progress( get_the_ID(), $user_id ) : 0;
				$progress_percent = round( $progress_value * 100 );
				?>
				<div class="cards__item">
					<a href="<?php the_permalink(); ?>">
						<figure class="cards__figure">
							<?php
							$thumb = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'fp-small' ) : $placeholder_url;
							?>
							<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php the_title_attribute(); ?>">
							<div class="cards__badge cards__badge--progress" aria-label="Započeto">
								<?php echo zaher_lineicon_svg( 'play-circle' ); ?>
								<?php echo esc_html( $progress_percent ); ?>%
							</div>
							<?php if ( function_exists( 'get_field' ) && ( $video_length = get_field( 'video_length' ) ) ) : ?>
								<div class="cards__length"><?php echo esc_html( $video_length ); ?></div>
							<?php endif; ?>
							<?php if ( $progress_value > 0 ) : ?>
								<div class="cards__progress-bar">
									<div class="cards__progress-fill" style="width: <?php echo esc_attr( $progress_percent ); ?>%;"></div>
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
	<?php else : ?>
		<div class="empty-state">
			<div class="empty-state__icon">
				<?php echo zaher_lineicon_svg( 'play-circle' ); ?>
			</div>
			<h3 class="empty-state__title">Nema započetih treninga</h3>
			<p class="empty-state__text">Kad započneš trening, moći ćeš ga nastaviti ovdje.</p>
		</div>
	<?php endif; ?>
</div>

<?php
wp_reset_postdata();
