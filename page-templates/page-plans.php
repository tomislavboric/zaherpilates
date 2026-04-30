<?php
/*
Template Name: Loop Plans
*/
get_header(); ?>

	<main class="main">

		<?php
		$zaher_pricing_sub_data = function_exists( 'zaher_get_user_active_subscription_data' )
			? zaher_get_user_active_subscription_data()
			: array( 'active_ids' => array() );
		$zaher_is_active_subscriber = ! empty( $zaher_pricing_sub_data['active_ids'] );
		?>
		<section class="pricing-plans pricing-plans--cjenik<?php echo $zaher_is_active_subscriber ? ' pricing-plans--subscriber' : ''; ?>">
			<div class="grid-container">
				<?php if ( ! $zaher_is_active_subscriber ) : ?>
					<header class="section__header section__header--center pricing-plans__header">
						<h1 class="section__title pricing-plans__title">Članstvo po tvojoj mjeri</h1>
						<div class="section__desc">
							<p>S LOOPom nema ograničenja u količini treniranja, nema radnog vremena — tvoj trening je na jedan klik dalje.</p>
						</div>
						<div class="pricing-plans__assurance">Otkaži u bilo kojem trenutku</div>
					</header>
				<?php endif; ?>
				<div class="pricing-plans__table">
					<?php echo do_shortcode( '[mepr-group-price-boxes group_id="147"]' ); ?>
				</div>
			</div>
		</section>

	</main>

<?php get_footer();
