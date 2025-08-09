<?php
// Detect context: taxonomy vs. post/page
if (is_tax() || is_category() || is_tag()) {
	$object = get_queried_object(); // WP_Term
	$title = single_term_title('', false);
	$description = get_field('description', $object);
	$image = get_field('image', $object);
	$image_url = $image['sizes']['fp-small'] ?? null;
} else {
	$object = get_post(); // WP_Post
	$title = get_the_title($object);
	$description = get_field('description', $object->ID);
	$image = get_field('image', $object->ID);

	if (!empty($image) && isset($image['sizes']['fp-small'])) {
		$image_url = $image['sizes']['fp-small'];
	} elseif (has_post_thumbnail($object->ID)) {
		$image_url = get_the_post_thumbnail_url($object->ID, 'fp-small');
	} else {
		$image_url = null;
	}
}

// Fallback
$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg';
?>

<header class="page__header">
	<div class="grid-container full">

		<div class="page__header-grid">

			<div class="page__header-content">
				<?php if (function_exists('rank_math_the_breadcrumbs')) : ?>
					<div class="category__breadcrumbs">
						<div class="breadcrumbs">
							<?php rank_math_the_breadcrumbs(); ?>
						</div>
					</div>
				<?php endif; ?>

				<h1 class="page__title"><?php echo esc_html($title); ?></h1>

				<?php if ($description) : ?>
					<div class="page__desc">
						<?php echo $description; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ($description && $image_url) : ?>
				<figure class="page__header-figure">
					<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">
				</figure>
			<?php endif; ?>

		</div>

	</div>
</header>
