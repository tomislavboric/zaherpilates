<?php
/*
Template Name: Landing - LOOP Lead
*/

get_header();
the_post();

$loop_lead_mailerlite_form_id = '385859311';

?>

<style>
	.landing--loop-lead {
		background-color: #f7f5f3;
	}

	.hero--loop-lead {
		background: radial-gradient(circle at top left, #1f1f1f, #000000 65%);
		color: #fff;
		padding: 5rem 0 4rem;
		text-align: center;
		height: auto;
		position: relative;
		overflow: hidden;
	}

	.hero--loop-lead:before {
		content: "";
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: url('https://zaherpilates.com/wp-content/uploads/2025/08/Zaher-coveri-1.png') no-repeat center center;
		background-size: cover;
		opacity: 0.15;
		z-index: 0;
	}

	.hero--loop-lead .grid-container {
		position: relative;
		z-index: 1;
	}

	.hero--loop-lead .hero__title {
		color: #fff;
		max-width: 720px;
		margin: 0 auto 1rem;
	}

	.hero--loop-lead .hero__desc p {
		color: rgba(255, 255, 255, 0.85);
		font-size: 1.1rem;
		margin-bottom: 0.75rem;
	}

	.hero__kicker {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		padding: 0.4rem 0.9rem;
		margin-bottom: 1.25rem;
		border-radius: 999px;
		background: rgba(255, 255, 255, 0.12);
		color: #facc15;
		font-size: 0.85rem;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.08em;
	}

	.hero__note {
		margin-top: 1rem;
		color: rgba(255, 255, 255, 0.75);
		font-size: 0.95rem;
	}

	/* Intro section */
	.loop-intro {
		position: relative;
		padding: 5rem 0 4rem;
		background: linear-gradient(135deg, #fef9f5, #f5f2ff);
		overflow: hidden;
	}

	.loop-intro::after,
	.loop-intro::before {
		content: "";
		position: absolute;
		width: 420px;
		height: 420px;
		border-radius: 50%;
		filter: blur(80px);
		opacity: 0.45;
		z-index: 0;
	}

	.loop-intro::before {
		top: -220px;
		left: -60px;
		background: #facc15;
	}

	.loop-intro::after {
		bottom: -260px;
		right: -120px;
		background: #8b5cf6;
	}

	.loop-intro .grid-container {
		position: relative;
		z-index: 1;
	}

	.loop-intro__text {
		background: #ffffff;
		border-radius: 32px;
		padding: 3rem;
		max-width: 860px;
		margin: 0 auto;
		box-shadow: 0 45px 80px -50px rgba(15, 23, 42, 0.55);
		border: 1px solid rgba(99, 102, 241, 0.12);
		position: relative;
		overflow: hidden;
	}

	.loop-intro__text::before {
		content: "";
		position: absolute;
		top: 0;
		left: 0;
		width: 120px;
		height: 120px;
		background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(250, 204, 21, 0));
	}

	.loop-intro__text::after {
		content: "";
		position: absolute;
		right: 2rem;
		bottom: 2rem;
		width: 140px;
		height: 140px;
		background: radial-gradient(circle, rgba(236, 72, 153, 0.18) 0%, rgba(236, 72, 153, 0) 70%);
	}

	.loop-intro__text p {
		margin: 0;
		font-size: 1.15rem;
		line-height: 1.9;
		color: #111827;
		font-weight: 500;
		text-align: center;
	}

	.loop-intro__text em {
		font-style: normal;
		color: #8b5cf6;
		font-weight: 600;
	}

	.loop-intro__text strong {
		color: #ea580c;
	}

	/* What you get section */
	.loop-what {
		padding: 5rem 0;
		background: #fff;
	}

	.loop-what__grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
		gap: 1.5rem;
		margin-top: 2.5rem;
	}

	.loop-what__item {
		background: #f7f5f3;
		border-radius: 18px;
		padding: 1.75rem 1.5rem;
		box-shadow: 0 20px 40px -30px rgba(17, 24, 39, 0.3);
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.loop-what__icon {
		font-size: 1.75rem;
		line-height: 1;
	}

	.loop-what__text {
		font-size: 1rem;
		font-weight: 600;
		color: #1f2937;
		line-height: 1.6;
	}

	/* Optin / CTA section */
	.loop-optin {
		padding: 5rem 0;
		background: linear-gradient(135deg, #1a1a2e, #16213e);
		color: #fff;
		text-align: center;
		position: relative;
		overflow: hidden;
	}

	.loop-optin::before {
		content: "";
		position: absolute;
		top: -200px;
		right: -100px;
		width: 500px;
		height: 500px;
		border-radius: 50%;
		background: rgba(139, 92, 246, 0.15);
		filter: blur(80px);
		z-index: 0;
	}

	.loop-optin::after {
		content: "";
		position: absolute;
		bottom: -200px;
		left: -100px;
		width: 400px;
		height: 400px;
		border-radius: 50%;
		background: rgba(250, 204, 21, 0.1);
		filter: blur(80px);
		z-index: 0;
	}

	.loop-optin .grid-container {
		position: relative;
		z-index: 1;
	}

	.loop-optin .section__title {
		color: #fff;
		max-width: 600px;
		margin: 0 auto 1rem;
	}

	.loop-optin .section__desc {
		color: rgba(255, 255, 255, 0.8);
		max-width: 640px;
		margin: 0 auto 2.5rem;
		font-size: 1.05rem;
	}

	.loop-optin__form-wrap {
		max-width: 480px;
		margin: 0 auto;
	}

	/* Final CTA */
	.bf-final-cta {
		background: #000;
		color: #fff;
		padding: 4rem 0;
		text-align: center;
	}

	.bf-final-cta .section__title {
		color: #fff;
		max-width: 600px;
		margin: 0 auto 1.5rem;
	}

	.bf-final-cta .section__desc {
		color: rgba(255, 255, 255, 0.8);
		max-width: 800px;
		margin: 0.5rem auto 2rem;
	}

	@media (max-width: 640px) {
		.hero--loop-lead {
			padding: 4rem 0 3rem;
		}

		.hero--loop-lead .hero__title {
			font-size: 2rem;
		}

		.loop-intro {
			padding: 3.5rem 0;
		}

		.loop-intro__text {
			padding: 2rem 1.75rem;
			border-radius: 24px;
		}

		.loop-intro__text p {
			font-size: 1.05rem;
		}

		.loop-what {
			padding: 3.5rem 0;
		}

		.loop-optin {
			padding: 3.5rem 0;
		}
	}
</style>

<main class="main landing landing--loop-lead">

	<section class="hero hero--loop-lead">
		<div class="grid-container">
			<div class="hero__content">
				<div class="hero__kicker">Besplatni LOOP treninzi</div>
				<header class="hero__header">
					<h1 class="hero__title">Isprobaj treninge na LOOPu</h1>
					<div class="hero__desc">
						<p>Kratki i učinkoviti treninzi koje možeš raditi od doma</p>
					</div>
				</header>
				<div class="hero__cta">
					<a class="button button--large" href="#loop-optin">Isprobaj LOOP besplatno</a>
					<p class="hero__note">4 besplatna treninga, nema kreditne kartice.</p>
				</div>
			</div>
		</div>
	</section>

	<section class="loop-intro">
		<div class="grid-container">
			<div class="loop-intro__text">
				<p><em>LOOP nije još jedan program koji te gura.</em> LOOP je membership platforma koja te sluša. Ovi treninzi te vraćaju u kontakt s tijelom i uče te <strong>kada usporiti, a kada koristiti snagu</strong>.</p>
			</div>
		</div>
	</section>

	<section class="loop-what section">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Što dobivaš</div>
				<h2 class="section__title">4 besplatna LOOP treninga koja rade za tvoje tijelo</h2>
				<div class="section__desc">
					<p>Za sve one dane kada poželiš odustati, LOOP će te podsjetiti da samo trebaš odabrati pravi trening za sebe u ovom trenutku. LOOP je platforma koja susreće tebe — a ne ona kojoj se ti trebaš prilagođavati.</p>
				</div>
			</header>

			<div class="loop-what__grid">
				<div class="loop-what__item">
					<div class="loop-what__icon">🎯</div>
					<div class="loop-what__text">4 treninga do 25 minuta — trebaš samo prostirku</div>
				</div>
				<div class="loop-what__item">
					<div class="loop-what__icon">💪</div>
					<div class="loop-what__text">Osjećaj zadovoljstva i ponosa jer si nešto napravila za sebe</div>
				</div>
				<div class="loop-what__item">
					<div class="loop-what__icon">🏠</div>
					<div class="loop-what__text">Dokaz da učinkovit trening ne mora biti dug i može biti iz tvog dnevnog boravka</div>
				</div>
			</div>
		</div>
	</section>

	<section class="loop-optin section" id="loop-optin">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Isprobaj LOOP besplatno</div>
				<h2 class="section__title">Upiši svoju email adresu</h2>
				<div class="section__desc">
					<p>Šaljem ti presjek nekoliko treninga sa LOOPa da se uvjeriš da i ti možeš trenirati od doma.</p>
					<div class="ml-embedded" data-form="9xERND"></div>

				</div>
			</header>
		</div>
	</section>

</main>

<?php
get_footer();
