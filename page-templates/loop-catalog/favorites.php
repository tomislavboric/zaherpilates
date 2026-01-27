<?php
$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg';

if ( ! is_user_logged_in() ) :
	?>
	<div class="grid-container full" id="moji-favoriti" style="margin-bottom:60px!important">
		<div class="empty-state">
			<div class="empty-state__icon">
				<?php echo zaher_lineicon_svg( 'lock' ); ?>
			</div>
			<h3 class="empty-state__title">Prijavi se za svoje favorite</h3>
			<p class="empty-state__text">Prijavi se kako bi mogla spremati treninge i brzo im se vraćati.</p>
			<div class="empty-state__action">
				<a class="button" href="<?php echo esc_url( home_url( '/prijava/' ) ); ?>">Prijavi se</a>
			</div>
		</div>
	</div>
	<?php
	return;
endif;

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

if ( empty( $fav_ids ) ) :
	?>
	<div class="grid-container full" id="moji-favoriti" style="margin-bottom:60px!important">
		<div class="empty-state">
			<div class="empty-state__icon">
				<?php echo zaher_lineicon_svg( 'heart' ); ?>
			</div>
			<h3 class="empty-state__title">Još nema favorita</h3>
			<p class="empty-state__text">Dodaj srce na treninge koje želiš spremiti na jedno mjesto.</p>
			<div class="empty-state__action">
				<a class="button" href="<?php echo esc_url( get_permalink() . '#catalog-panel-programs' ); ?>">Pregledaj programe</a>
			</div>
		</div>
	</div>
	<?php
	return;
endif;

$show_all = isset( $_GET['show_all_favorites'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$fav_query = new WP_Query(array(
  'post_type'      => 'programs',
  'post__in'       => $fav_ids,
  'orderby'        => 'post__in', // keep same order as Favorites
  'posts_per_page' => $show_all ? -1 : 8,
  'no_found_rows'  => true,
  'ignore_sticky_posts' => true,
));
?>

<div class="grid-container full" id="moji-favoriti" style="margin-bottom:60px!important">
  <?php if ( ! $show_all && count( $fav_ids ) > 8 ) : ?>
    <div class="favorites-actions">
      <a class="button button--small button--hollow" href="<?php echo esc_url( add_query_arg( 'show_all_favorites', '1', get_permalink() ) ); ?>#moji-favoriti">Prikaži sve</a>
    </div>
  <?php endif; ?>

  <div class="cards">
    <?php if ( $fav_query->have_posts() ) : while ( $fav_query->have_posts() ) : $fav_query->the_post(); ?>
      <div class="cards__item" data-favorite-post-id="<?php echo esc_attr( get_the_ID() ); ?>">
        <div class="cards__favorite">
          <?php
          if ( function_exists( 'the_favorites_button' ) ) {
          	$GLOBALS['zaher_favorites_list_context'] = true;
          	the_favorites_button( get_the_ID() );
          	unset( $GLOBALS['zaher_favorites_list_context'] );
          }
          ?>
        </div>
        <a href="<?php the_permalink(); ?>">
          <figure class="cards__figure">
            <?php
            $thumb = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'fp-small') : $placeholder_url;
            ?>
            <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
            <?php if ( function_exists( 'zaher_user_completed_program' ) && zaher_user_completed_program( get_the_ID(), $user_id ) ) : ?>
              <div class="cards__badge" aria-label="Pogledano do kraja">
                <?php echo zaher_lineicon_svg( 'check' ); ?>
              </div>
            <?php endif; ?>
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

  <div class="empty-state favorites-empty--inline" style="display:none;">
    <div class="empty-state__icon">
      <?php echo zaher_lineicon_svg( 'heart' ); ?>
    </div>
    <h3 class="empty-state__title">Još nema favorita</h3>
    <p class="empty-state__text">Dodaj srce na treninge koje želiš spremiti na jedno mjesto.</p>
    <div class="empty-state__action">
      <a class="button" href="<?php echo esc_url( get_permalink() . '#catalog-panel-programs' ); ?>">Pregledaj programe</a>
    </div>
  </div>
</div>

<?php
wp_reset_postdata();
