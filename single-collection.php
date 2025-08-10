<?php
get_header();

$collection = get_post();
$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg';

// Get selected terms from ACF field (set Return Value: Term Object, or IDs)
$terms = get_field('categories', $collection->ID);

// If you also ticked “Save Terms”, you can fallback to saved terms:
if (empty($terms)) {
  $terms = wp_get_post_terms($collection->ID, 'catalog'); // <-- your taxonomy slug
}

// Normalize to term objects
if (!empty($terms) && is_numeric($terms[0])) {
  $terms = get_terms([
    'taxonomy' => 'catalog',
    'include'  => array_map('intval', $terms),
    'hide_empty' => false,
  ]);
}
?>

<main class="main">
  <?php get_template_part('template-parts/modules/page-header'); ?>

  <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
    <div class="grid-container full">
      <div class="cards">
        <?php foreach ($terms as $term) :
          // ACF image on term (adjust field name if different)
          $image = get_field('image', $term);
          $image_url = $image['sizes']['fp-small'] ?? $placeholder_url;

          // Term link and title
          $link  = get_term_link($term);
          $title = $term->name;

          // Count of posts in this term (usually Programs if taxonomy is attached only to that CPT)
          $count = (int) $term->count;
          ?>
          <div class="cards__item">
            <a href="<?php echo esc_url($link); ?>">
              <figure class="cards__figure">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">
                <div class="cards__count">
                  <span class="material-icons">video_library</span>
                  <?php echo esc_html($count); ?>
                </div>
              </figure>
              <div class="cards__header">
                <h3 class="cards__title"><?php echo esc_html($title); ?></h3>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="grid-container">
      <p>No categories selected for this collection.</p>
    </div>
  <?php endif; ?>
</main>

<?php get_footer(); ?>
