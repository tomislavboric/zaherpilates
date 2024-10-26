<?php get_header(); ?>

<main class="main">

    <?php get_template_part( 'template-parts/modules/page-header' ); ?>

    <div class="grid-container full" style="margin-bottom: 60px !important">

        <?php $user_favorites = get_user_favorites(); // Gets current user's favorite post IDs ?>
        <?php $placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg'; ?>

        <?php if ( $user_favorites ) : ?>
            <h2>Tvoji favoriti</h2>

            <?php while ( have_posts() ) : the_post(); ?>

                <?php if ( in_array( get_the_ID(), $user_favorites ) ) : ?>

                    <div class="cards">
                        <div class="cards__item">
                            <a href="<?php the_permalink(); ?>">
                                <figure class="cards__figure">
                                    <?php $thumbnail_url = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'fp-small' ) : $placeholder_url;
                                    ?>
                                    <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php the_title_attribute(); ?>">

                                    <?php if ( $video_length = get_field( 'video_length', get_the_ID() ) ) : ?>
                                        <div class="cards__length">
                                            <?php echo esc_html( $video_length ); ?>
                                        </div>
                                    <?php endif; ?>
                                </figure>
                                <div class="cards__header">
                                    <h3 class="cards__title"><?php the_title(); ?></h3>
                                </div>
                            </a>
                        </div>
                    </div>

                <?php endif; ?>

            <?php endwhile; ?>

        <?php endif; ?>
    </div>

    <div class="grid-container full">

        <?php if ( $user_favorites ) : ?>
            <h2>Sve kategorije</h2>
        <?php endif; ?>

        <?php
        $terms = get_terms( array(
            'taxonomy' => 'catalog',
            'hide_empty' => true,
        ) );

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
            <div class="cards">
                <?php foreach ( $terms as $term ) :

                    $image = get_field( 'image', $term );
                    $term_image_url = $image ? $image['sizes']['fp-small'] : 'https://via.placeholder.com/1920x1080';
                    ?>
                    <div class="cards__item">
                        <a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
                            <figure class="cards__figure">
                                <img src="<?php echo esc_url( $term_image_url ); ?>" alt="<?php echo esc_attr( $term->name ); ?>">
                                <div class="cards__count">
                                    <span class="material-icons">video_library</span>
                                    <?php echo esc_html( $term->count ); ?>
                                </div>
                            </figure>
                            <div class="cards__header">
                                <h3 class="cards__title"><?php echo esc_html( $term->name ); ?></h3>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p>No categories found.</p>
        <?php endif; ?>

    </div>

</main>

<?php get_footer(); ?>
