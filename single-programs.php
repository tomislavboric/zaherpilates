<?php
/**
 * The template for displaying all single posts and attachments
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

get_header(); ?>

<main class="main">

	<div class="grid-container">
		<?php while ( have_posts() ) : the_post();

		// vars
		$vimeoUrl = get_field('video', get_the_ID());
		$videoId = getVimeoVideoId($vimeoUrl);

		?>

			<article class="video">
				<div class="video__section">

					<?php if(current_user_can('mepr-active','memberships:148')): ?>
						<div style="padding:56.25% 0 0 0;position:relative;">
							<iframe src="https://player.vimeo.com/video/<?php echo $videoId; ?>?h=0aaecdaa4d" width="640" height="360" frameborder="0" allow="autoplay; fullscreen;" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;"></iframe>

						</div>
					<?php else : ?>
						<figure class="locked__figure">
							<?php if ( has_post_thumbnail( $post->ID ) ) : ?>
								<img src="<?php the_post_thumbnail_url(); ?>" alt="<?php the_title(); ?>">
							<?php else : ?>
								<img
								srcset="
								https://vumbnail.com/<?php echo $videoId; ?>.jpg 1170w,
								https://vumbnail.com/<?php echo $videoId; ?>_large.jpg 1170w,
								https://vumbnail.com/<?php echo $videoId; ?>_medium.jpg 200w,
								https://vumbnail.com/<?php echo $videoId; ?>_small.jpg 100w
								"
								sizes="(max-width: 1170px) 100vw, 1170px"
								src="https://vumbnail.com/<?php echo $videoId; ?>.jpg"
								alt="<?php the_title(); ?>"
								width="1170"
								height="658"
								/>
							<?php endif; ?>
							<figcaption class="locked__figcaption">
								<a class="button button--small" href="<?php echo home_url(); ?>/plans/"><span class="material-icons-outlined">lock</span> Pretplati se</a>
								<div>ili <a href="/login">prijavi se</a>.</div>
							</figcaption>
						</figure>
					<?php endif; ?>

				</div>

				<div class="video__content">

					<header class="video__header">
						<h1 class="video__title"><?php the_title(); ?></h1>
						<?php the_favorites_button(); ?>
					</header>

					<div class="video__category">
							<?php
							$terms = get_the_terms( $post->ID, 'catalog' );

							if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
									foreach ( $terms as $term ) {
											$term_link = esc_url( get_term_link( $term->term_id, 'catalog' ) );
											echo '<a href="' . $term_link . '">' . esc_html( $term->name ) . '</a>';
									}
							} else {
									echo 'No Catalog Assigned.';
							}
							?>
					</div>

					<div class="video__desc">Lorem ipsum dolor sit amet</div>

					<div class="video__instructor">

					</div>


				</div>

				<div class="video__nav">
					<?php
					$prev_post = get_previous_post();
					$next_post = get_next_post();

					if(!empty($prev_post)) :

					// vars
					$vimeoUrl = get_field('video', get_the_ID());
					$videoId = getVimeoVideoId($vimeoUrl);
					?>
							<div class="video__prev">
									<a href="<?php echo get_permalink($prev_post->ID); ?>">
										<figure>
											<img src="https://vumbnail.com/<?php echo $videoId; ?>_small.jpg" alt="<?php echo $prev_post->post_title; ?>" width="100" height="75">
										</figure>
											<span>Prethodni: <?php the_title(); ?></span>
									</a>
							</div>
					<?php
					endif;

					if(!empty($next_post)) :

					// vars
					$vimeoUrl = get_field('video', get_the_ID());
					$videoId = getVimeoVideoId($vimeoUrl);
					?>
							<div class="video__next">
									<a href="<?php echo get_permalink($next_post->ID); ?>">
										<figure>
											<img src="https://vumbnail.com/<?php echo $videoId; ?>_small.jpg" alt="<?php echo $prev_post->post_title; ?>" width="100" height="75">
										</figure>
										<span>Sljedeći: <?php the_title(); ?></span>
									</a>
							</div>
					<?php
					endif;
					?>
				</div>

			</article>


			<?php // the_post_navigation(); ?>
	</div>

	<?php endwhile; ?>

</main>
<?php get_footer();
