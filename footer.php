<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the "off-canvas-wrap" div and all content after.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */
?>

			<?php if (!is_singular('programs')) : ?>
				<?php get_template_part( 'template-parts/modules/footer' ); ?>
			<?php endif; ?>

			<?php get_template_part( 'template-parts/modules/bottom-nav-members' ); ?>

		</div><?php // end .site ?>

		<?php wp_footer(); ?>

		<script src="https://vjs.zencdn.net/8.3.0/video.min.js"></script>

	</body>
</html>
