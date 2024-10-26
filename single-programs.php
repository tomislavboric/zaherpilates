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
        $user_id = get_current_user_id();
        $mepr_user = new MeprUser( $user_id );
        ?>

        <article class="video">
            <div class="video__section">

                <?php if($mepr_user->is_active() || current_user_can('administrator')) : ?>
                    <div style="padding:56.25% 0 0 0;position:relative;">
                        <iframe src="https://player.vimeo.com/video/<?php echo esc_attr($videoId); ?>?h=0aaecdaa4d" width="640" height="360" frameborder="0" allow="autoplay; fullscreen;" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;"></iframe>
                    </div>
                <?php else : ?>
                    <figure class="locked__figure">
                        <?php
                        $thumbnail_url = has_post_thumbnail() ? get_the_post_thumbnail_url() : $placeholder_url;
                        ?>
                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title_attribute(); ?>">
                        <figcaption class="locked__figcaption">
                            <a class="button button--small" href="<?php echo esc_url(home_url('/pretplate/')); ?>"><span class="material-icons-outlined">lock</span> Pretplati se</a>
                            <div>ili <a href="<?php echo esc_url(home_url('/login')); ?>">prijavi se</a>.</div>
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
                    $terms = get_the_terms( get_the_ID(), 'catalog' );

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
            $instructor = get_field('instructor');

            if ( $instructor ) :
                // override $post
                $post = $instructor;
                setup_postdata( $post );

                $about = get_field('about');
                $instructor_thumbnail_url = has_post_thumbnail() ? get_the_post_thumbnail_url() : $placeholder_url;
                ?>
                <div class="instructor">
                    <div class="instructor__about">

                        <figure class="instructor__figure">
                            <img src="<?php echo esc_url($instructor_thumbnail_url); ?>" alt="<?php the_title_attribute(); ?>">
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

        </article>

        <?php endwhile; ?>
    </div>

</main>

<?php get_footer(); ?>
