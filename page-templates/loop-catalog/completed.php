<?php
$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg';

if ( ! is_user_logged_in() ) {
	return;
}

$completed_ids = get_query_var( 'zaher_completed_ids', array() );
if ( empty( $completed_ids ) && function_exists( 'zaher_get_completed_program_ids' ) ) {
	$completed_ids = zaher_get_completed_program_ids( get_current_user_id() );
}
if ( $completed_ids ) {
	$completed_ids = array_values(
		array_filter(
			array_map( 'absint', $completed_ids ),
			function ( $program_id ) {
				return $program_id
					&& get_post_status( $program_id ) === 'publish'
					&& get_post_type( $program_id ) === 'programs';
			}
		)
	);
}

if ( empty( $completed_ids ) ) :
	?>
	<div class="grid-container full" id="pogledano" style="margin-bottom:60px!important">
		<div class="empty-state">
			<div class="empty-state__icon">
				<?php echo zaher_lineicon_svg( 'check-circle' ); ?>
			</div>
			<h3 class="empty-state__title">Još nema pogledanih videa</h3>
			<p class="empty-state__text">Kad završiš trening do kraja, pojavit će se ovdje.</p>
		</div>
	</div>
	<?php
	return;
endif;

$completed_query = new WP_Query(
	array(
		'post_type'           => 'programs',
		'post__in'            => $completed_ids,
		'orderby'             => 'post__in',
		'posts_per_page'      => -1,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	)
);
?>

<div class="grid-container full" id="pogledano" style="margin-bottom:60px!important">
	<?php if ( $completed_query->have_posts() ) : ?>
		<div class="cards">
			<?php while ( $completed_query->have_posts() ) : $completed_query->the_post(); ?>
				<div class="cards__item">
					<a href="<?php the_permalink(); ?>">
						<figure class="cards__figure">
							<?php
							$thumb = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'fp-small' ) : $placeholder_url;
							?>
							<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php the_title_attribute(); ?>">
							<div class="cards__badge" aria-label="Pogledano do kraja">
								<?php echo zaher_lineicon_svg( 'check-circle' ); ?>
								Pogledano
							</div>
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
		<div class="empty-state">
			<div class="empty-state__icon">
				<?php echo zaher_lineicon_svg( 'check-circle' ); ?>
			</div>
			<h3 class="empty-state__title">Još nema pogledanih videa</h3>
			<p class="empty-state__text">Kad završiš trening do kraja, pojavit će se ovdje.</p>
		</div>
	<?php endif; ?>
</div>

<?php
wp_reset_postdata();
