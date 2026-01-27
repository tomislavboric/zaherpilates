<?php get_header();

// Get term information
$term = get_queried_object(); // This gets the current taxonomy term object
$image = get_field('image', $term);
$description = get_field('description', $term);

// Placeholder image URL for posts without a thumbnail
$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg';

$has_catalog = false;
if ( have_rows('catalog_builder', $term) ) {
	// provjeri ima li barem jedan red s popunjenim "videos"
	while ( have_rows('catalog_builder', $term) ) { the_row();
		$videos = get_sub_field('videos'); // Relationship (array ID-eva ili WP_Post objekata)
		if ( !empty($videos) ) { $has_catalog = true; break; }
	}
	// reset pointer na početak
	reset_rows('catalog_builder', $term);
}

?>

<main class="main">

		<?php get_template_part( 'template-parts/modules/page-header' ); ?>

		<div class="catalog-breadcrumbs">
			<div class="grid-container full">
				<nav class="breadcrumbs" aria-label="Breadcrumb">
					<?php if ( function_exists( 'rank_math_the_breadcrumbs' ) ) : ?>
						<?php rank_math_the_breadcrumbs(); ?>
					<?php else : ?>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Katalog</a>
						<span class="separator" aria-hidden="true">/</span>
						<span class="current-item"><?php echo esc_html( $term->name ); ?></span>
					<?php endif; ?>
				</nav>
			</div>
		</div>

    <?php if ( have_posts() && !$has_catalog ) : ?>

			<div class="catalog">
				<div class="grid-container full">

					<?php
					global $wp_query;

					// Keep existing taxonomy filters; just change order (and preserve pagination)
					$paged = max( 1, get_query_var('paged'), get_query_var('page') );
					$q = new WP_Query( array_merge( $wp_query->query_vars, [
						'orderby'             => ['menu_order' => 'ASC', 'date' => 'DESC'],
						'order'               => 'ASC',
						'paged'               => $paged,
						'ignore_sticky_posts' => 1,
					] ) );
					?>

					<div class="cards">
						<?php while ( $q->have_posts() ) : $q->the_post();

							// Variables for video information
							$vimeoUrl     = get_field('video', get_the_ID());
							$video_length = get_field('video_length', get_the_ID());
							$videoId      = getVimeoVideoId($vimeoUrl);
							$subscription_type = get_field('subscription_type', get_the_ID());

							// Thumbnail or placeholder URL
							$thumbnail_url = has_post_thumbnail()
								? get_the_post_thumbnail_url(get_the_ID(), 'fp-small')
								: $placeholder_url;

							// Check if user has access to this video
							$membership_map = [
								'mjesecna' => 387,
								'tromjesecna' => 111,
								'polugodisnja' => 148,
							];

							$has_access = false;
							$is_admin = current_user_can('administrator');

							if ($is_admin) {
								$has_access = true;
							} elseif ($subscription_type) {
								foreach ($subscription_type as $type) {
									if (isset($membership_map[$type]) && current_user_can('mepr-active', 'membership:' . $membership_map[$type])) {
										$has_access = true;
										break;
									}
								}
							} else {
								// If no subscription type set, video is accessible to all
								$has_access = true;
							}
							?>

							<div class="cards__item <?php echo !$has_access ? 'is-locked' : ''; ?>">
								<?php if ( is_user_logged_in() && function_exists( 'the_favorites_button' ) ) : ?>
									<div class="cards__favorite">
										<?php the_favorites_button( get_the_ID() ); ?>
									</div>
								<?php endif; ?>
								<a href="<?php the_permalink(); ?>">
									<figure class="cards__figure">
										<img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title_attribute(); ?>">
										<?php if ( function_exists( 'zaher_user_completed_program' ) && zaher_user_completed_program( get_the_ID() ) ) : ?>
											<div class="cards__badge" aria-label="Pogledano do kraja">
												<?php echo zaher_lineicon_svg( 'check-circle' ); ?>
												Pogledano
											</div>
										<?php endif; ?>
										<?php if ($video_length) : ?>
											<div class="cards__length"><?php echo esc_html($video_length); ?></div>
										<?php endif; ?>
										<?php if (!$has_access) : ?>
											<div class="cards__locked">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 20" data-area="lock-icon" height="18" width="20">
													<g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
														<path d="M13.927 18.872H3.634C2.18 18.872 1 17.727 1 16.312V10.35c0-1.413 1.18-2.56 2.634-2.56h10.293c1.455 0 2.634 1.147 2.634 2.56v5.964c0 1.414-1.179 2.56-2.634 2.56z"></path>
														<path d="M3.81 7.79V5.83C3.81 3.162 6.035 1 8.78 1c2.746 0 4.97 2.162 4.97 4.829V7.79"></path>
													</g>
												</svg>
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

					<?php
					// Optional: pagination (if you use it on this archive)
					// echo paginate_links([ 'total' => $q->max_num_pages, 'current' => $paged ]);

					wp_reset_postdata();
					?>

				</div>
			</div>

		<?php elseif ( $has_catalog ) : ?>

			<?php
			// prevent duplicates if the same post appears in multiple repeater rows
			$seen = [];
			$get_id = function( $p ) { return is_object($p) ? $p->ID : (int) $p; };
			?>

			<div class="catalog-grid">

					<?php while ( have_rows('catalog_builder', $term) ) : the_row();
						$section_title = trim( (string) get_sub_field('title') );
						$videos        = (array) get_sub_field('videos'); // Relationship (posts or IDs)
						$videos        = array_filter($videos); // drop empties
						if ( empty($videos) ) continue;
					?>
					<div class="catalog-grid__section">
						<div class="grid-container full">

							<?php if ( $section_title ) : ?>
								<h2><?php echo esc_html($section_title); ?></h2>
							<?php endif; ?>

							<div class="cards">
								<?php foreach ( $videos as $v ) :
									$pid = $get_id($v);
									if ( !$pid || isset($seen[$pid]) ) continue;
									$seen[$pid] = true;

									$vimeoUrl      = get_field('video', $pid);
									$video_length  = get_field('video_length', $pid);
									$subscription_type = get_field('subscription_type', $pid);
									$thumbnail_url = has_post_thumbnail($pid)
										? get_the_post_thumbnail_url($pid, 'fp-small')
										: $placeholder_url;

									// Check if user has access to this video
									$membership_map = [
										'mjesecna' => 387,
										'tromjesecna' => 111,
										'polugodisnja' => 148,
									];

									$has_access = false;
									$is_admin = current_user_can('administrator');

									if ($is_admin) {
										$has_access = true;
									} elseif ($subscription_type) {
										foreach ($subscription_type as $type) {
											if (isset($membership_map[$type]) && current_user_can('mepr-active', 'membership:' . $membership_map[$type])) {
												$has_access = true;
												break;
											}
										}
									} else {
										// If no subscription type set, video is accessible to all
										$has_access = true;
									}
								?>
									<div class="cards__item <?php echo !$has_access ? 'is-locked' : ''; ?>">
										<?php if ( is_user_logged_in() && function_exists( 'the_favorites_button' ) ) : ?>
											<div class="cards__favorite">
												<?php the_favorites_button( $pid ); ?>
											</div>
										<?php endif; ?>
										<a href="<?php echo esc_url( get_permalink($pid) ); ?>">
											<figure class="cards__figure">
												<img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr( get_the_title($pid) ); ?>">
												<?php if ( function_exists( 'zaher_user_completed_program' ) && zaher_user_completed_program( $pid ) ) : ?>
													<div class="cards__badge" aria-label="Pogledano do kraja">
														<?php echo zaher_lineicon_svg( 'check-circle' ); ?>
														Pogledano
													</div>
												<?php endif; ?>
												<?php if ( $video_length ) : ?>
													<div class="cards__length"><?php echo esc_html($video_length); ?></div>
												<?php endif; ?>
												<?php if (!$has_access) : ?>
													<div class="cards__locked">
														<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 20" data-area="lock-icon" height="18" width="20">
															<g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
																<path d="M13.927 18.872H3.634C2.18 18.872 1 17.727 1 16.312V10.35c0-1.413 1.18-2.56 2.634-2.56h10.293c1.455 0 2.634 1.147 2.634 2.56v5.964c0 1.414-1.179 2.56-2.634 2.56z"></path>
																<path d="M3.81 7.79V5.83C3.81 3.162 6.035 1 8.78 1c2.746 0 4.97 2.162 4.97 4.829V7.79"></path>
															</g>
														</svg>
													</div>
												<?php endif; ?>
											</figure>
											<div class="cards__header">
												<h3 class="cards__title"><?php echo esc_html( get_the_title($pid) ); ?></h3>
											</div>
										</a>
									</div>
								<?php endforeach; ?>
							</div>

						</div>
					</div>

					<?php endwhile; ?>

			</div>

    <?php endif; ?>

</main>

<?php get_footer(); ?>
