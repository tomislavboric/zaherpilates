<?php
/*
Template Name: Contact
*/
get_header(); ?>

	<main class="main contact">

		<?php get_template_part( 'template-parts/modules/page-header' ); ?>

			<div class="grid-container">

				<div class="contact__grid">
					<div class="contact__content">
						<?php the_content(); ?>
					</div>

					<div class="contact__form">
						<div class="contact__form-item">
							<h3>Javi mi se!</h3>
							<?php echo do_shortcode('[wpforms id="423" title="false"]'); ?>
						</div>
					</div>
				</div>

			</div>

	</main>

<?php get_footer();
