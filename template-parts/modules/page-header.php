<?php
$description = get_field('description');
?>

<header class="page__header">
	<div class="grid-container full">
		<?php /* <div class="category__breadcrumbs">
			<div class="breadcrumbs">
				<?php if (function_exists('rank_math_the_breadcrumbs')) rank_math_the_breadcrumbs(); ?>
			</div>
		</div> */ ?>
		<h1 class="page__title">
				<?php
						if (is_tax() || is_category() || is_tag()) {
								// This is a taxonomy archive page (category, tag, or custom taxonomy)
								single_term_title();
						} elseif (is_post_type_archive('programs')) {
								// This is a post type archive page
								echo 'LOOP kategorije';
						} elseif (is_post_type_archive()) {
							// This is a post type archive page
							post_type_archive_title();
						} else {
								// This is not a taxonomy or post type archive page (e.g., a regular post or page)
								the_title();
						}
				?>
		</h1>

		<?php if ($description) : ?>
			<div class="page__desc">
				<?php echo $description; ?>
			</div>
		<?php endif; ?>

	</div>
</header>
