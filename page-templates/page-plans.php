<?php
/*
Template Name: Loop Plans
*/
get_header(); ?>

	<main class="main">

		<section class="pricing-plans">
			<div class="grid-container full">
				<header class="section__header section__header--center">
					<h2 class="section__title">Članstvo po tvojoj mjeri!</h2>
					<div class="section__desc">
						<p>S LOOPom nema ograničenja u količini treniranja, nema radnoga vremena - tvoj trening je na jedan klik dalje!</p>
					</div>
				</header>
				<?php echo do_shortcode( '[mepr-group-price-boxes group_id="147"]' ); ?>
			</div>
		</section>

	</main>

<?php get_footer();
