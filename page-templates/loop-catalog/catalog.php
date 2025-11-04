<?php
$page_id = get_queried_object_id() ?: get_the_ID();

if (!$page_id) {
	return;
}

if (have_rows('catalog_sections', $page_id)):
	$placeholder = 'https://via.placeholder.com/400x300?text=No+Image';
	?>
	<div class="grid-container full">
		<div class="cards">
			<?php
			while (have_rows('catalog_sections', $page_id)) : the_row();

				$title = '';
				$image_url = '';
				$link = '';
				$count = 0;

				if (get_row_layout() === 'video_category_block') {
					$term_field = get_sub_field('category');
					$term = null;

					if ($term_field instanceof WP_Term) {
						$term = $term_field;
					} elseif (is_array($term_field) && isset($term_field['term_id'])) {
						$term = get_term((int) $term_field['term_id']);
					} elseif (!empty($term_field)) {
						$term = get_term((int) $term_field);
					}

					if ($term && !is_wp_error($term)) {
						// Skip hidden categories
						if (get_field('hide_category', $term)) {
							continue;
						}

						$title = $term->name;
						$image = get_field('image', $term);
						$image_url = $image['sizes']['fp-small'] ?? $placeholder;
						$link = get_term_link($term);
						$count = $term->count;
					}

				} elseif (get_row_layout() === 'collection_block') {
					$collection_field = get_sub_field('collection');
					$collection = null;

					if ($collection_field instanceof WP_Post) {
						$collection = $collection_field;
					} elseif (!empty($collection_field)) {
						$collection = get_post((int) $collection_field);
					}

					if ($collection instanceof WP_Post) {
						// Skip hidden collections
						if (get_field('hide_collection', $collection->ID)) {
							continue;
						}

						$title = get_the_title($collection);
						$image = get_field('image', $collection->ID); // Optional ACF image field

						if (!empty($image) && isset($image['sizes']['fp-small'])) {
							$image_url = $image['sizes']['fp-small'];
						} elseif (has_post_thumbnail($collection)) {
							$image_url = get_the_post_thumbnail_url($collection, 'fp-small');
						} else {
							$image_url = $placeholder;
						}

						$link = get_permalink($collection->ID);
						$videos = get_field('videos', $collection->ID);
						$count = is_array($videos) ? count($videos) : 0;
					}
				}

				if (!empty($title) && !empty($link)) : ?>
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
				<?php endif;

			endwhile;
			?>
		</div>
	</div>
<?php endif; ?>
