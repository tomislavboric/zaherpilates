<?php
/*
Template Name: Landing - LOOP Lead Thank You
*/

get_header();
the_post();

?>

<style>
	.landing--loop-lead-thankyou {
		background-color: #f7f5f3;
	}

	/* Hero */
	.hero--loop-lead-ty {
		background: radial-gradient(circle at top left, #1f1f1f, #000000 65%);
		color: #fff;
		padding: 5rem 0 4rem;
		text-align: center;
		position: relative;
		overflow: hidden;
		height: auto;
	}

	.hero--loop-lead-ty:before {
		content: "";
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: url('https://zaherpilates.com/wp-content/uploads/2025/08/Zaher-coveri-1.png') no-repeat center center;
		background-size: cover;
		opacity: 0.1;
		z-index: 0;
	}

	.hero--loop-lead-ty .grid-container {
		position: relative;
		z-index: 1;
	}

	.hero--loop-lead-ty .hero__kicker {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		padding: 0.4rem 0.9rem;
		margin-bottom: 1.25rem;
		border-radius: 999px;
		background: rgba(134, 239, 172, 0.15);
		color: #86efac;
		font-size: 0.85rem;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.08em;
	}

	.hero--loop-lead-ty .hero__title {
		color: #fff;
		max-width: 640px;
		margin: 0 auto 1rem;
	}

	.hero--loop-lead-ty .hero__desc p {
		color: rgba(255, 255, 255, 0.8);
		font-size: 1.1rem;
		margin-bottom: 0.5rem;
	}

	.hero--loop-lead-ty .hero__note {
		margin-top: 1.25rem;
		color: rgba(255, 255, 255, 0.55);
		font-size: 0.9rem;
	}

	/* Koraci */
	.loop-steps {
		padding: 5rem 0;
		background: #fff;
	}

	.loop-steps__grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
		gap: 1.5rem;
		margin-top: 2.5rem;
		counter-reset: step;
	}

	.loop-steps__item {
		background: #f7f5f3;
		border-radius: 18px;
		padding: 1.75rem 1.5rem;
		box-shadow: 0 20px 40px -30px rgba(17, 24, 39, 0.3);
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.loop-steps__icon {
		font-size: 1.75rem;
		line-height: 1;
	}

	.loop-steps__text {
		font-size: 1rem;
		font-weight: 600;
		color: #1f2937;
		line-height: 1.6;
	}

	/* Upsell */
	.loop-upsell {
		padding: 5rem 0;
		background: linear-gradient(135deg, #1a1a2e, #16213e);
		color: #fff;
		text-align: center;
		position: relative;
		overflow: hidden;
	}

	.loop-upsell::before {
		content: "";
		position: absolute;
		top: -200px;
		right: -100px;
		width: 500px;
		height: 500px;
		border-radius: 50%;
		background: rgba(139, 92, 246, 0.2);
		filter: blur(80px);
		z-index: 0;
	}

	.loop-upsell::after {
		content: "";
		position: absolute;
		bottom: -200px;
		left: -100px;
		width: 400px;
		height: 400px;
		border-radius: 50%;
		background: rgba(250, 204, 21, 0.08);
		filter: blur(80px);
		z-index: 0;
	}

	.loop-upsell .grid-container {
		position: relative;
		z-index: 1;
	}

	.loop-upsell .section__subtitle {
		color: #facc15;
	}

	.loop-upsell .section__title {
		color: #fff;
		max-width: 600px;
		margin: 0 auto 1rem;
	}

	.loop-upsell .section__desc {
		color: rgba(255, 255, 255, 0.75);
		max-width: 640px;
		margin: 0 auto 3rem;
		font-size: 1.05rem;
	}

	/* FAQ */
	.loop-faq {
		padding: 4rem 0;
		background: #f7f5f3;
	}

	.loop-faq__list {
		max-width: 680px;
		margin: 2rem auto 0;
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.loop-faq__item {
		background: #fff;
		border-radius: 14px;
		padding: 1.25rem 1.5rem;
	}

	.loop-faq__q {
		font-weight: 700;
		color: #1f2937;
		font-size: 1rem;
		margin-bottom: 0.5rem;
	}

	.loop-faq__a {
		color: #4b5563;
		font-size: 0.95rem;
		line-height: 1.7;
		margin: 0;
	}

	@media (max-width: 640px) {
		.hero--loop-lead-ty {
			padding: 4rem 0 3rem;
		}

		.hero--loop-lead-ty .hero__title {
			font-size: 2rem;
		}

		.loop-steps {
			padding: 3.5rem 0;
		}

		.loop-upsell {
			padding: 3.5rem 0;
		}

		.loop-faq {
			padding: 3rem 0;
		}
	}
</style>

<main class="main landing landing--loop-lead-thankyou">

	<section class="hero hero--loop-lead-ty">
		<div class="grid-container">
			<div class="hero__content">
				<div class="hero__kicker">Prijava uspješna ✓</div>
				<header class="hero__header">
					<h1 class="hero__title">Treninzi su na putu!</h1>
					<div class="hero__desc">
						<p>Provjeri email inbox — upravo ti šaljem 4 LOOP treninga<br>koje možeš odraditi od doma, bez opreme.</p>
					</div>
				</header>
				<p class="hero__note">Nema kreditne kartice. Nema obaveza. Samo treninzi.</p>
			</div>
		</div>
	</section>

	<section class="loop-steps section">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Što sada</div>
				<h2 class="section__title">3 koraka do prvog treninga</h2>
			</header>

			<div class="loop-steps__grid">
				<div class="loop-steps__item">
					<div class="loop-steps__icon">📬</div>
					<div class="loop-steps__text">Otvori email koji ti dolazi — provjeri i Spam / Promotions mapu</div>
				</div>
				<div class="loop-steps__item">
					<div class="loop-steps__icon">🎯</div>
					<div class="loop-steps__text">Klikni na link u emailu i pristupaj prvom besplatnom treningu</div>
				</div>
				<div class="loop-steps__item">
					<div class="loop-steps__icon">💪</div>
					<div class="loop-steps__text">Odradi trening u 25 minuta — trebaju ti samo prostirka i ti sama</div>
				</div>
			</div>
		</div>
	</section>

	<section class="loop-upsell section">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Želi još?</div>
				<h2 class="section__title">Otključaj cijeli LOOP membership</h2>
				<div class="section__desc">
					<p>Ovih 4 treninga su samo mali presjek. Na LOOPu te čeka preko 200 kratkih i učinkovitih treninga iz svih kategorija — pilates, snaga, HIIT, yoga i još mnogo više. Treniraš kad ti odgovara, od doma, bez opreme.</p>
				</div>
			</header>

			<?php echo do_shortcode( '[mepr-group-price-boxes group_id="147"]' ); ?>
		</div>
	</section>

	<section class="loop-faq section">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Pitanja</div>
				<h2 class="section__title">Najčešća pitanja</h2>
			</header>

			<div class="loop-faq__list">
				<div class="loop-faq__item">
					<p class="loop-faq__q">Kada ću dobiti email s treninzima?</p>
					<p class="loop-faq__a">Email stižeako par minuta. Ako ne vidiš ništa u inboxu, provjeri Spam i Promotions mapu.</p>
				</div>
				<div class="loop-faq__item">
					<p class="loop-faq__q">Nisam dobila email — što sad?</p>
					<p class="loop-faq__a">Dodaj nas u kontakte (zaherpilates.com) kako bi idući emaili sigurno stigli u inbox. Ako i dalje ništa, javi nam se.</p>
				</div>
				<div class="loop-faq__item">
					<p class="loop-faq__q">Trebam li opremu za treninge?</p>
					<p class="loop-faq__a">Ne — dovoljne su prostirka i ti. Treninzi su osmišljeni da ih možeš raditi iz dnevnog boravka.</p>
				</div>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();
