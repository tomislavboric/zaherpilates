<?php
/*
Template Name: Loop Plans
*/
get_header(); ?>

	<main class="main">

		<section class="pricing-plans pricing-plans--cjenik">
			<div class="grid-container pricing-plans__container">
				<header class="section__header section__header--center pricing-plans__header">
					<h1 class="section__title pricing-plans__title">Članstvo po tvojoj mjeri</h1>
					<div class="section__desc">
						<p>S LOOPom nema ograničenja u količini treniranja, nema radnog vremena — tvoj trening je na jedan klik dalje.</p>
					</div>
					<div class="pricing-plans__assurance">Otkaži ili pauziraj u bilo trenutku</div>
				</header>
				<div class="pricing-plans__table">
					<?php echo do_shortcode( '[mepr-group-price-boxes group_id="147"]' ); ?>
				</div>
			</div>
		</section>

	</main>

<?php get_footer();
