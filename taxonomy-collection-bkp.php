<?php get_header(); ?>

<main class="main">
  <div class="grid-container">
    <h1><?php single_term_title(); ?></h1>
    <?php the_archive_description(); ?>

    <?php
    $current_collection = get_queried_object();

    // Get all catalog terms that are linked to this collection
    $catalog_terms = get_terms([
      'taxonomy' => 'catalog',
      'hide_empty' => true,
    ]);

    $related_catalog_terms = [];

    foreach ($catalog_terms as $cat_term) {
      $linked_collection = get_field('collection_parent', $cat_term);
      if ($linked_collection && $linked_collection->term_id === $current_collection->term_id) {
        $related_catalog_terms[] = $cat_term;
      }
    }

    if ($related_catalog_terms): ?>
      <div class="cards">
        <?php foreach ($related_catalog_terms as $term):

          $hide = get_field('hide_category', $term);
          if ($hide) continue;

          $image = get_field('image', $term);
          $image_url = $image['sizes']['fp-small'] ?? 'https://via.placeholder.com/400x300?text=No+Image';
          ?>
          <div class="cards__item">
            <a href="<?php echo esc_url(get_term_link($term)); ?>">
              <figure class="cards__figure">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
                <div class="cards__count">
                  <span class="material-icons">video_library</span>
                  <?php echo esc_html($term->count); ?>
                </div>
              </figure>
              <div class="cards__header">
                <h3 class="cards__title"><?php echo esc_html($term->name); ?></h3>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No categories linked to this collection.</p>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
