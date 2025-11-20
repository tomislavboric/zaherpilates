<?php
/*
Template Name: Black Week LOOP Landing
*/

get_header();
the_post();

?>

<style>
	.landing--black-friday {
		background-color: #f7f5f3;
	}

	.hero--black-friday {
		background: radial-gradient(circle at top left, #1f1f1f, #000000 65%);
		color: #fff;
		padding: 5rem 0 4rem;
		text-align: center;
		height: auto;
	}
	.hero--black-friday:before {
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

	.hero--black-friday .hero__title {
		color: #fff;
		max-width: 720px;
		margin: 0 auto 1rem;
	}

	.hero--black-friday .hero__desc p {
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

	.bf-countdown {
		background: #111;
		color: #fff;
		padding: 1.75rem 0;
		text-align: center;
	}

	.bf-countdown--last {
		margin-top: var(--section-padding);
	}

	.bf-countdown__inner {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.4rem;
	}

	.bf-countdown__label {
		font-size: 0.9rem;
		letter-spacing: 0.08em;
		text-transform: uppercase;
		color: rgba(255, 255, 255, 0.7);
	}

	.bf-countdown__time {
		font-size: 1.8rem;
		font-weight: 700;
		letter-spacing: 0.08em;
		display: flex;
		gap: 0.75rem;
		flex-wrap: wrap;
		justify-content: center;
	}

	.bf-countdown__time span {
		min-width: 70px;
		background: rgba(255, 255, 255, 0.08);
		border-radius: 12px;
		padding: 0.4rem 0.8rem;
	}

	.bf-countdown__time small {
		display: block;
		font-size: 0.65rem;
		font-weight: 400;
		letter-spacing: normal;
	}

	.bf-countdown__hint {
		font-size: 0.85rem;
		color: rgba(255, 255, 255, 0.55);
	}

	.section--bg {
		background: #fff;
	}

	.bf-story {
		padding-top: 3rem;
	}

	.bf-story__grid {
		gap: 2rem;
	}

	.bf-story__list {
		margin: 1.5rem 0 0;
		padding: 0;
		list-style: none;
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
		gap: 1rem;
	}

	.bf-story__item {
		background: #fff;
		border-radius: 18px;
		padding: 1.5rem;
		box-shadow: 0 20px 40px -30px rgba(17, 24, 39, 0.4);
		font-weight: 600;
		color: #1f2937;
	}

	.bf-story__image {
		margin: 3rem auto;
		max-width: 600px;
		border-radius: 18px;
		overflow: hidden;
		box-shadow: 0 20px 50px -20px rgba(0, 0, 0, 0.3);
	}

	.bf-story__image img {
		width: 100%;
		height: auto;
		display: block;
	}

	.bf-benefits {
		margin-top: 2.5rem;
	}

	.bf-benefit {
		background: #fff;
		border-radius: 18px;
		padding: 2rem 1.75rem;
		box-shadow: 0 20px 40px -30px rgba(17, 24, 39, 0.45);
		height: 100%;
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.bf-benefit__kicker {
		font-size: 0.8rem;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		color: #8b5cf6;
		font-weight: 600;
	}

	.bf-benefit__title {
		font-size: 1.25rem;
		font-weight: 700;
		color: #111827;
	}

	.bf-benefit__desc {
		color: #4b5563;
	}

	.bf-offer {
		margin-top: 3rem;
	}

	.bf-offer__grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
		gap: 1.8rem;
	}

	.bf-offer__card {
		background: #fff;
		border-radius: 22px;
		padding: 2.5rem 2rem;
		box-shadow: 0 24px 48px -32px rgba(30, 41, 59, 0.5);
		display: flex;
		flex-direction: column;
		gap: 1.25rem;
	}

	.bf-offer__badge {
		align-self: flex-start;
		background: #facc15;
		color: #1f2937;
		padding: 0.35rem 0.9rem;
		border-radius: 999px;
		font-weight: 600;
		font-size: 0.85rem;
		letter-spacing: 0.05em;
		text-transform: uppercase;
	}

	.bf-offer__price {
		font-size: 2.4rem;
		font-weight: 700;
		color: #1f2937;
		display: flex;
		align-items: baseline;
		gap: 0.75rem;
	}

	.bf-offer__price del {
		font-size: 1.1rem;
		color: #9ca3af;
		font-weight: 500;
	}

	.bf-offer__savings {
		font-size: 0.95rem;
		color: #059669;
		font-weight: 600;
	}

	.bf-offer__list {
		margin: 0;
		padding-left: 1.1rem;
		color: #374151;
		display: flex;
		flex-direction: column;
		gap: 0.65rem;
	}

	.bf-offer__list li {
		list-style: disc;
	}

	.bf-offer__cta {
		margin-top: auto;
	}

	.bf-note {
		margin-top: 2rem;
		text-align: center;
		color: #4b5563;
	}

	.bf-note strong {
		color: #111827;
	}

	.bf-note a {
		color: #3b82f6;
		text-decoration: underline;
	}

	.bf-testimonials {
		margin-top: 3rem;
	}

	.testimonials__grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
		gap: 1.5rem;
	}

	.testimonials__item {
		background: #fff;
		border-radius: 18px;
		padding: 1.75rem;
		box-shadow: 0 20px 40px -28px rgba(17, 24, 39, 0.35);
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.testimonials__blockquote p {
		font-size: 1rem;
		color: #1f2937;
		margin: 0;
	}

	.testimonials__person-name {
		font-weight: 700;
		color: #0f172a;
	}

	.bf-faq {
		margin-top: 3rem;
	}

	.bf-faq__list {
		display: grid;
		gap: 1.5rem;
		margin-top: 2rem;
	}

	.bf-faq__item {
		background: #fff;
		border-radius: 18px;
		padding: 1.75rem;
		box-shadow: 0 20px 40px -28px rgba(17, 24, 39, 0.35);
	}

	.bf-faq__question {
		font-weight: 700;
		margin-bottom: 0.75rem;
		color: #1f2937;
	}

	.bf-faq__answer {
		color: #4b5563;
	}

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
		.hero--black-friday {
			padding: 4rem 0 3rem;
		}

		.hero__title {
			font-size: 2rem;
		}

			.bf-offer__price {
				flex-direction: column;
				align-items: center;
				text-align: center;
			}

			.bf-offer__badge {
				align-self: center;
			}

			.loop-intro {
				padding: 3.5rem 0;
			}

			.loop-intro__text {
				padding: 2rem 1.75rem;
				border-radius: 24px;
			}

			.loop-intro__text p {
				font-size: 1.15rem;
			}
		}
	</style>

<main class="main landing landing--black-friday">

	<section class="hero hero--black-friday">
		<div class="grid-container">
			<div class="hero__content">
				<div class="hero__kicker">Black Week na LOOPu (24. &ndash; 28.11.)</div>
				<header class="hero__header">
					<h1 class="hero__title">Ostvari ekskluzivan popust i dodatne pogodnosti &ndash; samo ovaj tjedan</h1>
					<div class="hero__desc">
						<?php /* <p>LOOP je online fitness platforma na kojoj treniraju žene svjesne važnosti tjelovježbe za emocionalno i fizičko zdravlje. Za fit tijelo ne trebaš trenirati puno nego pametno i u skladu s menstrualnim ciklusom.</p> */ ?>
					</div>
				</header>
				<div class="hero__cta">
					<a class="button button--large" href="#bf-options">Pogledaj ponudu</a>
					<p class="hero__note">Ponuda traje cijeli tjedan, a završava 28.11. u 23:59.</p>
				</div>
			</div>
		</div>
	</section>

	<section class="bf-countdown" data-deadline="2025-11-28T23:59:00+01:00">
		<div class="grid-container">
			<div class="bf-countdown__inner">
				<div class="bf-countdown__label">Do isteka ponude ostalo je</div>
				<div class="bf-countdown__time">
					<span data-unit="days">00<small>dana</small></span>
					<span data-unit="hours">00<small>sati</small></span>
					<span data-unit="minutes">00<small>minuta</small></span>
					<span data-unit="seconds">00<small>sekundi</small></span>
				</div>
				<?php /* <div class="bf-countdown__hint">Ponuda nestaje 28. studenog u ponoć.</div> */ ?>
			</div>
		</div>
	</section>

	<section class="loop-intro">
		<div class="grid-container">
			<div class="loop-intro__text">
				<p>LOOP je online fitness platforma na kojoj treniraju žene svjesne važnosti tjelovježbe za emocionalno i fizičko zdravlje. Za fit tijelo ne trebaš trenirati puno nego pametno i u skladu s menstrualnim ciklusom.</p>
			</div>
		</div>
	</section>

	<?php /*
	<section class="section section--bg bf-story">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Pametan pristup</div>
				<h2 class="section__title">Treniraj u skladu sa svojim tijelom, ne protiv njega</h2>
				<div class="section__desc">
					<p>LOOP te vodi kroz faze ciklusa kako bi svaka žena mogla trenirati u skladu s energijom koju taj tjedan ima. Ulog je mali, a rezultat je kontinuitet i osjećaj lakoće u vlastitom tijelu.</p>
				</div>
			</header>

			<ul class="bf-story__list">
				<li class="bf-story__item">Treninzi od 10 do 45 minuta koji prate hormonalne promjene.</li>
				<li class="bf-story__item">Stručna podrška i zajednica koja razumije izazove modernog života.</li>
				<li class="bf-story__item">Programi i planovi koji balansiraju snagu, mobilnost i oporavak.</li>
			</ul>

			<div class="bf-story__image">
				<img src="<?php echo get_template_directory_uri(); ?>/dist/assets/images/loop-training.jpg" alt="LOOP trening" loading="lazy">
			</div>
		</div>
	</section> */ ?>


	<section class="pricing-plans section bf-offer" id="bf-options">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Iznenađenja za crni tjedan</div>
				<h2 class="section__title">Biraj između dvije ekskluzivne opcije</h2>
				<div class="section__desc">
					<p>Kako te jednom godišnje za Crni petak volim iznenaditi, ove godine pripremila sam sljedeće ponude.</p>
				</div>
			</header>

			<div class="bf-offer__grid">
				<div class="bf-offer__card">
					<span class="bf-offer__badge">Opcija 1</span>
					<div class="bf-offer__price">
						<del>€79.99</del>
						<span>€54.99</span>
					</div>
					<div class="bf-offer__savings">25&nbsp;€ OFF na tromjesečnu pretplatu.</div>
					<ul class="bf-offer__list">
						<li>Pristup cijeloj LOOP platformi na 3 mjeseca.</li>
						<li>Mogućnost otkazivanja pretplate u bilo kojem trenutku unutar korisničkog računa.</li>
						<li>Bonus kategorije samo za članove viših pretplata</li>
					</ul>
					<div class="bf-offer__cta">
						<a class="button" href="/registracija/tromjesecna-pretplata/?coupon=BF25">Ugrabi ponudu</a>
					</div>
				</div>

				<div class="bf-offer__card">
					<span class="bf-offer__badge">Opcija 2</span>
					<div class="bf-offer__price">
						<span>€149.99</span>
					</div>
					<div class="bf-offer__savings">Polugodišnja pretplata + 1:1 coaching call sa Ivanom<br> (ograničeno na 7 mjesta!)</div>
					<ul class="bf-offer__list">
						<li>6 mjeseci pristupa LOOP platformi &ndash; jedan mjesec dobivaš gratis.</li>
						<li>Individualni coaching call (60 min) na temu po tvom izboru.</li>
						<li>Ulaziš u izbor za coaching paket od 12 susreta potpuno besplatno.</li>
					</ul>
					<div class="bf-offer__cta">
						<a class="button" href="/registracija/polugodisnja-pretplata/?coupon=BF25">Ugrabi ponudu</a>
					</div>
				</div>
			</div>

				<p class="bf-note">Sve što trebaš je kliknuti na odabranu opciju i pratiti daljnje korake.<br> <strong>Obje opcije</strong> vrijede za postojeće članice LOOP-a prilikom nadogradnje na višu pretplatu. Ako zapneš, tipkaj nam preko <a href="mailto:info@zaherpilates.com">info@zaherpilates.com</a>.</p>
		</div>
	</section>

	<section class="testimonials section bf-testimonials">
		<div class="grid-container full">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Evo zašto stotinu žena bira LOOP</div>
				<h2 class="section__title">Iskustva iz prve ruke</h2>
			</header>

			<main class="testimonials__main">
				<div class="testimonials__grid">
					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>Željela sam ti reći... tako se <strong>osjećam nježno i ženstveno, a opet snažno</strong> kad vježbam s tobom, jer ti imaš takvu energiju koju prosljeđuješ!</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Maja</div>
							</div>
						</div>
					</div>

					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>Vježbe su odlične, baš su mi sjele, posebno videa s vježbama koje ti vodiš, paše mi ta fluidnost i prisutnost. <strong>Uz posao, dvoje male djece, brigu o kućanskim obvezama, ja stignem i po 4-5 puta tjedno odvježbati</strong>, to je dio dana kad djeca zaspu, za mene i volim to što brinem o svom tijelu</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Manuela</div>
							</div>
						</div>
					</div>

					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>Ajme Ivana, ove tvoje vježbe su predivne!! Toliko opuštajuće, ovaj prvi trening mi nije bio zahtjevan, ali sam osjetila svaki djelić tijela i kako ono radi! Hvala ti na tolikoj predanosti i detaljnim opisima dok izvodiš vježbe.<br> Prebrzo je prošlo, nisam ni skužila da je gotovo! Čista uživancija!🥰🩷🌸</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Vida</div>
							</div>
						</div>
					</div>

					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>Draga Ivana, samo ću ti reći da si nikada nisam priuštila bolje treninge...Na Loop-u je toliko izbora da stvarno nema izgovora da se ne odradi bilo kakav trening u danu. Kada imam više vremena uzmem si neke zahtjevnije treninge, kada uopće nemam vremena uzmem one najkraće i <strong>osjećaj je odličan jer ipak i u takvom danu napravim nešto za svoje zdravlje</strong>.<br>
							Vježbe su mi odlične, nije dosadno.</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Zrinka</div>
							</div>
						</div>
					</div>

					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>Draga Ivana, htjela sam samo reći kako sam nastavila trenirati u Loopu. I dalje sam presretna koliko <strong>stvarno s guštom treniram i osjećam se snažno tijekom i nakon treninga</strong>. 💪❤️<br>
							Obožavam kako vodiš trening!</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Iva</div>
							</div>
						</div>
					</div>
				</div>
			</main>
		</div>
	</section>

	<?php /* FAQ Section */ /*?>
	<section class="bf-faq section">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Česta pitanja</div>
				<h2 class="section__title">Sve što trebaš znati prije nego uskoro istekne ponuda</h2>
			</header>

			<div class="bf-faq__list">
				<div class="bf-faq__item">
					<div class="bf-faq__question">Kada počinje moje članstvo?</div>
					<div class="bf-faq__answer">Pristup dobivaš odmah nakon kupnje i vrijedi punih 12 mjeseci. Sve nadogradnje i novi programi automatski se dodaju u tvoj račun.</div>
				</div>
				<div class="bf-faq__item">
					<div class="bf-faq__question">Što ako tek počinjem s tjelovježbom?</div>
					<div class="bf-faq__answer">U LOOP biblioteci postoje početnički, srednji i napredni treninzi. Uz Black Friday paket dobivaš i vodič kako odabrati pravi program za svoju razinu.</div>
				</div>
				<div class="bf-faq__item">
					<div class="bf-faq__question">Mogu li pokloniti pristup nekome?</div>
					<div class="bf-faq__answer">Da! Nakon kupnje nam se javi na support i prebacit ćemo članstvo na osobu koju želiš razveseliti.</div>
				</div>
			</div>
		</div>
	</section>
	<?php /* FAQ Section */?>

	<section class="bf-countdown bf-countdown--last" data-deadline="2025-11-28T23:59:00+01:00">
		<div class="grid-container">
			<div class="bf-countdown__inner">
				<div class="bf-countdown__label">Do isteka ponude ostalo je</div>
				<div class="bf-countdown__time">
					<span data-unit="days">00<small>dana</small></span>
					<span data-unit="hours">00<small>sati</small></span>
					<span data-unit="minutes">00<small>minuta</small></span>
					<span data-unit="seconds">00<small>sekundi</small></span>
				</div>
				<div class="bf-countdown__hint">Ponuda nestaje 28. studenog u ponoć.</div>
			</div>
		</div>
	</section>

	<section class="bf-final-cta">
		<div class="grid-container">
			<header class="section__header section__header--center no-padding">
				<h2 class="section__title">Savršeni trenutak je sada</h2>
				<div class="section__desc">
					<p>Shvati ovo kao znak i iskoristi ponudu.<br> Nadogradi članstvo, pokloni sebi vrijeme i podršku te uđi u kraj godine osjećajući se snažno i samopouzdano.</p>
				</div>
			</header>

			<a class="button button--large" href="#bf-options">Iskoristi ponudu!</a>
			<p class="hero__note">Postojeće članice mogu nadograditi pretplatu unutar svog korisničkog računa.</p>
		</div>
	</section>

</main>

<?php
get_footer();
