<?php
$user_id = get_current_user_id();

// Get the user's favorite IDs for this post type
$fav_ids = get_user_favorites(
  $user_id,
  get_current_blog_id(),
  array(
    'post_type' => 'programs', // your CPT
    'status'    => 'publish'
  )
);

$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg';

if ( ! empty( $fav_ids ) ) :

  $fav_query = new WP_Query(array(
    'post_type'      => 'programs',
    'post__in'       => $fav_ids,
    'orderby'        => 'post__in', // keep same order as Favorites
    'posts_per_page' => -1,
    'no_found_rows'  => true,
    'ignore_sticky_posts' => true,
  ));
  ?>

  <div class="grid-container full" style="margin-bottom:60px!important">
    <h2>Moji favoriti</h2>

    <div class="cards">
      <?php if ( $fav_query->have_posts() ) : while ( $fav_query->have_posts() ) : $fav_query->the_post(); ?>
        <div class="cards__item">
          <a href="<?php the_permalink(); ?>">
            <figure class="cards__figure">
              <?php
              $thumb = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'fp-small') : $placeholder_url;
              ?>
              <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
              <?php if ( $video_length = get_field('video_length') ) : ?>
                <div class="cards__length"><?php echo esc_html($video_length); ?></div>
              <?php endif; ?>
            </figure>
            <div class="cards__header">
              <h3 class="cards__title"><?php the_title(); ?></h3>
            </div>
          </a>
        </div>
      <?php endwhile; endif; ?>
    </div>
  </div>

  <?php
  wp_reset_postdata();
endif;
