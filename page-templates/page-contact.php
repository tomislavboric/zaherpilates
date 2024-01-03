<?php
/*
Template Name: Contact
*/
get_header(); ?>

	<main class="main contact">
		<div class="grid-container full">

			<?php get_template_part( 'template-parts/modules/page-header' ); ?>

			<div class="contact__grid">

				<div class="contact__content">
					<p class="lead" style="margin-bottom: 60px">" In everyday life, we often forget to really be in the moment. We are moving from one task to another without putting much attention to our breath and how our bodies move. But body is always present. Gift yourself with an hour of mindful movement."</p>
					<p><strong>Bleicherweg 45, 8002 -  the entrance is from Dreikönigstrasse<br>
						The studio is on -1.</strong></p>
					<figure class="contact__figure">
						<img src="<?php echo get_stylesheet_directory_uri(); ?>/src/assets/images/office.jpg" alt="Ivana Zaher ured">
					</figure>
				</div>

				<div class="contact__form">
					<div class="contact__form-item">
						<h3>Javi mi se!</h3>
						<?php echo do_shortcode('[wpforms id="268" title="false"]'); ?>
					</div>
				</div>

			</div>

		</div>
	</main>

<?php get_footer();
