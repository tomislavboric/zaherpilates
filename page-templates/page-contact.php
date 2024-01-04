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
					<p class="lead" style="margin-bottom: 60px">"U svakodnevnom životu često zaboravljamo biti zaista prisutni u trenutku. Premještamo se s jednog zadatka na drugi bez da posvećujemo puno pažnje našem disanju i načinu kako se naše tijelo kreće. No, tijelo je uvijek prisutno. Poklonite sebi sat vremena svjesnog kretanja."</p>
					<p><strong>Bleicherweg 45, 8002 -  the entrance is from Dreikönigstrasse<br>
						The studio is on -1.</strong></p>
					<figure class="contact__figure">
						<img src="<?php echo get_stylesheet_directory_uri(); ?>/src/assets/images/office.jpg" alt="Ivana Zaher ured">
					</figure>
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
