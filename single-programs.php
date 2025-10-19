<?php
/**
* The template for displaying all single posts and attachments
*
* @package FoundationPress
* @since FoundationPress 1.0.0
*/

get_header();

// Placeholder image URL for posts without a thumbnail
$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg';

?>
<main class="main">

    <div class="grid-container">
        <?php while ( have_posts() ) : the_post();

        // vars
        $vimeo_url = get_field('video');
        $vimeo_length = get_field('video_length');
        $videoId = getVimeoVideoId($vimeo_url);
				$subscription_type = get_field('subscription_type');
        ?>

        <article class="video">
            <div class="video__section">

                <?php if( current_user_can('administrator')) : ?>
									<div style="padding:56.25% 0 0 0;position:relative;">
											<iframe src="https://player.vimeo.com/video/<?php echo esc_attr($videoId); ?>?h=0aaecdaa4d" width="640" height="360" frameborder="0" allow="autoplay; fullscreen;" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;"></iframe>
									</div>

									<?php elseif ($subscription_type) :
											// Map ACF values to MemberPress Membership IDs
											$membership_map = [
													'mjesecna' => 387,
													'tromjesecna' => 111,
													'polugodisnja' => 148,
											];

											// Check if user has any of the required memberships
											$has_access = false;
											foreach ($subscription_type as $type) {
													if (isset($membership_map[$type]) && current_user_can('mepr-active', 'membership:' . $membership_map[$type])) {
															$has_access = true;
															break;
													}
											}

											if ($has_access): ?>
													<div style="padding:56.25% 0 0 0;position:relative;">
															<iframe src="https://player.vimeo.com/video/<?php echo esc_attr($videoId); ?>?h=0aaecdaa4d" width="640" height="360" frameborder="0" allow="autoplay; fullscreen;" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;"></iframe>
													</div>
											<?php else: ?>
													<figure class="locked__figure">
															<?php
															$thumbnail_url = has_post_thumbnail() ? get_the_post_thumbnail_url() : $placeholder_url;
															?>
															<img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title_attribute(); ?>">
															<figcaption class="locked__figcaption">
																	<a class="button button--small" href="<?php echo esc_url(home_url('/cjenik/')); ?>"><span class="material-icons-outlined">lock</span> Pretplati se</a>
																	<div>ili <a href="<?php echo esc_url(home_url('/prijava')); ?>">prijavi se</a>.</div>
															</figcaption>
													</figure>
											<?php endif;
									endif;
									?>
            </div>

            <div class="video__content">

                <header class="video__header">
                    <h1 class="video__title"><?php the_title(); ?></h1>
                    <?php the_favorites_button(); ?>
                </header>

                <div class="video__category">
                    <?php
                    $terms = get_the_terms( get_the_ID(), 'catalog' );

                    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                        foreach ( $terms as $term ) {
                            $term_link = esc_url( get_term_link( $term->term_id, 'catalog' ) );
                            echo '<a href="' . $term_link . '">' . esc_html( $term->name ) . '</a>';
                        }
                    } else {
                        echo 'No category assigned.';
                    } ?>
                </div>

                <?php if ( get_the_content() ) : ?>
                    <div class="video__desc">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>

            </div>

            <?php
            $instructor = get_field('instructor');

            if ( $instructor ) :
                // override $post
                $post = $instructor;
                setup_postdata( $post );

                $about = get_field('about');
								if ( has_post_thumbnail() ) {
										$thumb = wp_get_attachment_image_src( get_post_thumbnail_id(), 'thumbnail' );
										$instructor_thumbnail_url = $thumb[0];
										$instructor_thumbnail_width = $thumb[1];
										$instructor_thumbnail_height = $thumb[2];
								} else {
										$instructor_thumbnail_url = $placeholder_url;
										$instructor_thumbnail_width = 150; // fallback width
										$instructor_thumbnail_height = 150; // fallback height
								}
                ?>
                <div class="instructor">
                    <div class="instructor__about">

                        <figure class="instructor__figure">
												<img
													src="<?php echo esc_url($instructor_thumbnail_url); ?>"
													width="<?php echo esc_attr($instructor_thumbnail_width); ?>"
													height="<?php echo esc_attr($instructor_thumbnail_height); ?>"
													alt="<?php the_title_attribute(); ?>">
                        </figure>

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
                <?php wp_reset_postdata(); // Reset the $post object ?>
            <?php endif; ?>

						<div class="video__nav">
                <?php
                $prev_post = get_previous_post();
                $next_post = get_next_post();

                if( !empty($prev_post) ) :
                    $prev_title = get_the_title($prev_post->ID);
                    ?>
                    <div class="video__prev">
                        <a href="<?php echo esc_url( get_permalink($prev_post->ID) ); ?>">
                            <div><span class="material-symbols-outlined">arrow_left_alt</span> Prethodna vježba</div>
                            <strong><?php echo esc_html( $prev_title ); ?></strong>
                        </a>
                    </div>
                <?php endif;

                if( !empty($next_post) ) :
                    $next_title = get_the_title($next_post->ID);
                    ?>
                    <div class="video__next">
                        <a href="<?php echo esc_url( get_permalink($next_post->ID) ); ?>">
                            <div>Sljedeća vježba <span class="material-symbols-outlined">arrow_right_alt</span></div>
                            <strong><?php echo esc_html( $next_title ); ?></strong>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

						<div class="video__comments">
							<?php video_comments(); ?>
						</div>

        </article>

        <?php endwhile; ?>
    </div>

</main>

<?php get_footer(); ?>
