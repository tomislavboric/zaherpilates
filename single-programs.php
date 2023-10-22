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
		//$vimeo_url = get_field('video', get_the_ID());
		$vimeo_url = get_field('video');
		$vimeo_length = get_field('video_lenght');
		$videoId = getVimeoVideoId($vimeo_url);
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
					} ?>
				</div>

				<?php if ( get_the_content() ) : ?>
					<div class="video__desc">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

			</div>

			<?php
			$instructor = get_field('instructor'); ?>

			<?php if ($instructor) :
				// override $post
				$post = $instructor;
				setup_postdata( $post );

				$about = get_field('about');

				?>
				<div class="instructor">
					<header class="instructor__header">
						<h3 class="instructor__title">Instruktor:</h3>
					</header>
					<div class="instructor__about">

						<?php if ( the_post_thumbnail() ) : ?>
							<figure class="instructor__figure">
								<img src="<?php the_post_thumbnail_url(); ?>" alt="<?php the_title(); ?>">
							</figure>
						<?php endif; ?>

						<div class="instructor__content">
							<h3 class="instructor__name"><?php the_title(); ?></h3>

							<?php if ($about) : ?>
								<div class="instructor__desc">
									<?php echo $about; ?>
								</div>
							<?php endif; ?>

						</div>
					</div>
				</div>
				<?php wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>
			<?php endif; ?>

			<div class="video__nav">
				<?php
				$prev_post = get_previous_post();
				$next_post = get_next_post();

				if(!empty($prev_post)) :

					// vars
					$vimeo_url = get_field('video', get_the_ID());
					$videoId = getVimeoVideoId($vimeo_url);
					?>
					<div class="video__prev">
					<a href="<?php echo get_permalink($prev_post->ID); ?>">
						<?php /*
						<figure>
							<img src="https://vumbnail.com/<?php echo $videoId; ?>_medium.jpg" alt="<?php echo $prev_post->post_title; ?>" width="200" height="150">
							</figure>
							*/ ?>
							<div><span class="material-symbols-outlined">arrow_left_alt</span> Prethodna vježba</div>
							<strong><?php echo $prev_post->post_title; ?></strong>
					</a>
					</div>
					<?php
				endif;

				if(!empty($next_post)) :

					// vars
					$vimeo_url = get_field('video', get_the_ID());
					$videoId = getVimeoVideoId($vimeo_url);
					?>
					<div class="video__next">
						<a href="<?php echo get_permalink($next_post->ID); ?>">
							<?php /*
							<figure>
							<img src="https://vumbnail.com/<?php echo $videoId; ?>_medium.jpg" alt="<?php echo $next_post->post_title; ?>" width="200" height="150">
							</figure>
							*/ ?>
							<div>Sljedeća vježba <span class="material-symbols-outlined">arrow_right_alt</span></div>
							<strong><?php echo $next_post->post_title; ?></strong>
						</a>
					</div>
					<?php
				endif;
				?>
			</div>

		</article>

		<?php endwhile; ?>
	</div>

</main>

<?php get_footer();
