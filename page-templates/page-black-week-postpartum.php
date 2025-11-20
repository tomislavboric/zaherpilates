<?php
/*
Template Name: Black Week Postpartum Landing
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
		a {
			color: #3b82f6;
		}
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
		border: 1px solid rgba(199, 146, 136, 0.18);
		position: relative;
		overflow: hidden;
	}

	.bf-offer__card::before {
		content: "";
		position: absolute;
		inset: 1rem;
		border-radius: 18px;
		border: 1px solid rgba(199, 146, 136, 0.15);
		pointer-events: none;
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
		font-size: 1.4rem;
		font-weight: 700;
		color: #1f2937;
		display: flex;
		flex-direction: column;
		gap: 0.15rem;
		text-transform: uppercase;
		letter-spacing: 0.08em;
	}

	.bf-offer__price del {
		display: none;
	}

	.bf-offer__savings {
		font-size: 1.05rem;
		color: #c79288;
		font-weight: 600;
		line-height: 1.5;
	}

	.bf-offer__list {
		margin: 0;
		padding: 0;
		color: #374151;
		display: grid;
		gap: 0.6rem;
		list-style: none;
	}

	.bf-offer__list li {
		position: relative;
		padding-left: 2rem;
		line-height: 1.6;
		font-weight: 500;
	}

	.bf-offer__list li::before {
		content: "\2713";
		position: absolute;
		left: 0;
		top: 0.21rem;
		width: 1.2rem;
		height: 1.2rem;
		border-radius: 50%;
		background: #22c55e;
		color: #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.65rem;
		font-weight: 700;
	}

	.bf-offer__list li + li {
		margin-top: 0.25rem;
	}

	.bf-offer__list li ul {
		margin-top: 0.6rem;
		list-style: decimal;
		padding-left: 1.5rem;
	}

	.bf-offer__list li ul li {
		font-size: 0.95rem;
		color: #475569;
		padding-left: 0;
	}

	.bf-offer__list li ul li::before {
		content: none;
	}

	.bf-offer__list li ol {
		margin-top: 0.6rem;
		list-style: decimal;
		padding-left: 1rem;
	}

	.bf-offer__list li ol li {
		font-size: 0.95rem;
		color: #475569;
		padding-left: 0;
	}

	.bf-offer__list li ol li::before {
		content: none;
	}

	.bf-offer__note {
		font-size: 0.85rem;
		color: #6b7280;
		margin-top: 0.5rem;
	}

	.bf-offer__cta {
		margin-top: auto;
	}

	.bf-note {
		margin-top: 2rem;
		text-align: center;
		color: #4b5563;
		font-weight: 500;
		line-height: 1.8;
	}

	.bf-note strong {
		color: #111827;
		display: block;
		margin-bottom: 0.4rem;
		font-size: 0.95rem;
		letter-spacing: 0.12em;
		text-transform: uppercase;
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
				<div class="hero__kicker">POSTPARTUM BF (24. &ndash; 28.11.)</div>
				<header class="hero__header">
					<h1 class="hero__title">BLACK FRIDAY ZA MAME - JEDINSTVENA POSTPARTUM PONUDA</h1>
					<div class="hero__desc">
						<p>Oporavak nikada nije bio podržaniji &ndash; ostvari ekskluzivne pogodnosti i još više podrške u postpartumu samo ovaj tjedan.</p>
					</div>
				</header>
				<div class="hero__cta">
					<a class="button button--large" href="#bf-options">Odaberi svoju ponudu</a>
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
				<p>Nakon poroda žena ne samo da donosi novi život, već otkriva i svoju novu snagu &ndash; tihu, postojanu i moćniju nego ikad prije. Ponudom ovog tjedna otključavam ti još više podrške i prilike za transformaciju iznutra na van.<br><br><strong>Postpartum Essentials</strong> je online program za oporavak tijela od trudnoće, zatvaranje dijastaze, rješavanje disfunkcije mišića zdjeličnoga dna i cijelog trupa. Essentials je do sada prošlo preko 1000 žena koje, osim fizičkih promjena, prijavljuju bolju kvalitetu života i povratak k sebi.</p>
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
				<div class="section__subtitle">BLACK FRIDAY PONUDE</div>
				<h2 class="section__title">Biraj između dvije ekskluzivne opcije</h2>
				<div class="section__desc">
					<p>Essentials ti donosi sve što trebaš za zatvaranje dijastaze, rehabilitaciju mišića zdjeličnog dna i povratak punoj snazi. Ovaj tjedan dobivaš dodatne bonuse i podršku kako bi se bez žurbe vratila sebi.</p>
				</div>
			</header>

			<div class="bf-offer__grid">
				<div class="bf-offer__card">
					<span class="bf-offer__badge">Opcija 1</span>
					<div class="bf-offer__price">
						<span>POSTPARTUM ESSENTIALS + 3 GRUPNA COACHING CALLA</span>
					</div>
					<p>Što ulazi u ponudu:</p>
					<ul class="bf-offer__list">
						<li>Pristup programu Postpartum Essentials u trajanju do godinu dana.</li>
						<li>Ivanina email podrška na tvom putu oporavka.</li>
						<li>3 bonus grupna coaching calla
							Teme coaching callova:
							<ol>
								<li>Detektiranje kočnica i samosabotera kako bi napustile savršenstvo</li>
								<li>Pronaći vrijeme za sebe kroz izbor višeg standarda</li>
								<li>Povratak božanskoj ženskoj energiji</li>
							</ol>
						</li>
					</ul>
					<p class="bf-offer__note">** Callovi će biti dostupni postojećim članicama Postpartum Essentialsa i Essentials Plus programa, snimani i uploadani na platformu</p>
					<div class="bf-offer__cta">
						<a class="button" href="https://postpartum.zaherpilates.ch/">Kupi Postpartum Essentials</a>
					</div>
				</div>

				<div class="bf-offer__card">
					<span class="bf-offer__badge">Opcija 2</span>
					<div class="bf-offer__price">
						<span>POSTPARTUM ESSENTIALS PLUS na 6 umjesto na 3 rate</span>
					</div>
					<p>Što ulazi u ponudu:</p>
					<ul class="bf-offer__list">
						<li>Sve iz Postpartum Essentials programa.</li>
						<li>Inicijalni konzultacijski 1:1 video poziv na kojem ću pogledati tvoje disanje, podatke sa mjerenja dijastaze i samopregleda mišića zdjeličnoga dna i s obzirom na sve te uputiti u nastavak.</li>
						<li>neograničena email podrška kroz godinu dana te mogućnost slanja videa na pregled.</li>
					</ul>
					<div class="bf-offer__cta">
						<a class="button" href="https://postpartum.zaherpilates.ch/essentials-plus">Kupi Postpartum Essentials Plus</a>
					</div>
				</div>
			</div>

				<p class="bf-note"><strong>KAKO OSTVARITI OVU JEDNOKRATNU POGODNOST?</strong><br>Sve što trebaš je kliknuti na odabranu opciju i pratiti daljnje korake. Pristup sadržaju programa počinje odmah, a za coaching callove ti se javim na mail po završetku Crnog tjedna. Ako zapneš, tipkaj nam na <a href="mailto:info@zaherpilates.com">info@zaherpilates.com</a>.</p>
		</div>
	</section>

	<section class="testimonials section bf-testimonials">
		<div class="grid-container full">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Testimonijali</div>
				<h2 class="section__title">Evo što kažu neke od preko 1000 žena koje su odabrale oporavak sa mnom</h2>
			</header>

			<main class="testimonials__main">
				<div class="testimonials__grid">
					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>Suočilo me sa samom sobom i natjeralo da se probudim, posvetim sebi i naučim ono što ne znam i prihvatim da ne znam, a to je <strong>biti nježna prema sebi</strong>. Ti to ponavljaš često u vježbama empatično i apsolutno potrebno i poželjno za svaku mamu/ženu.</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Danijela</div>
							</div>
						</div>
					</div>

					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>Postpartum košta puno kada gledaš samo cijenu i ako si mama. Jer mame su poznate po tome da im je kada kupuju sebi, sve skupo.  Ali ne košta puno kada uzmeš u obzir da imam podršku, <strong>možeš se uvijek nekome javiti, imaš vježbe koje na kraju rezultiraju boljim fizičkim zdravljem</strong>, a i imaš vježbe koje možeš reciklirati mjesecima. <strong>Kupuješ nešto što traje i ima učinak na tvoj život u budućnosti</strong>.</p>
							<p>Počela sam razmišljati o svojem tijelu, hraniti se bolje. Ovo nije program za mršavljenje, kod mene i dalje ima špekeca na trbuhu, ali sam čvrsta, <strong>tijelo mi je pokretljivije, nemam bolova u leđima, ali i skinula sam par kila</strong>.<br>
							Sretnija sam jer sam u protekla 4 mjeseca gotovo svakodnevno odvojila vrijeme za sebe. Uz ove vježbe promijenio mi se izgled trbuha, nije mi se povećala pupčana kila, što mi je bilo najbitnije. <strong>Dobila sam temelj za zdraviji i sretniji život</strong>. Ja sam ovaj program shvatila kao rehabilitaciju sebe kao osobe i to sam i dobila.</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Nikolina</div>
							</div>
						</div>
					</div>

					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>Vježbe su sjajne i osjećam se puno bolje, <strong>stomak je vidno zategnutiji i nije više napuhan kao prije</strong>. Dijastaza je prije vježbi bila do 2 prsta a sada se zatvorila jer ne stane niti prst.</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Blanka</div>
							</div>
						</div>
					</div>

					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>… gubitak u obitelji zbog kojeg sam potpuno zapostavila sebe, ali vratila sam se nazad i napravila sam puno, baš vidim i osjetim i zahvalna sam na programu jer mi je pomogao kao nijedan drugi prije, a rodila sam prije 4.5 godine i nikako nisam zatvorila dijastazu i bolovi u kičmi (radi krivog dizanja, čučanja, disanja uopće) i ibuprofen 600 su mi bili kao dobar dan. <strong>Sad se ne sjećam kad sam zadnje popila tabletu ili imala te bolove</strong>.</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Tihana</div>
							</div>
						</div>
					</div>

					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>Primjećujem da su mi se smanjili bolovi u kralježnici, lakše se krećem, kao da mi se malo i sama postura ispravila, i ono najvažnije, <strong>više mi ne pobjegne mokraće svaki put kad kihnem</strong>.</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Daria</div>
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
				<h2 class="section__title">Ovo je tvoj tjedan podržanog oporavka</h2>
				<div class="section__desc">
					<p>Postpartum se ne događa sam od sebe &ndash; treba mu prostor, vrijeme i plan. Uz ove Black Friday pogodnosti dobivaš sve troje, kao i mene uz sebe na svakom koraku.</p>
				</div>
			</header>

			<a class="button button--large" href="#bf-options">Iskoristi ponudu!</a>
			<p class="hero__note">Trebaš pomoć oko odabira? Piši mi na <a href="mailto:info@zaherpilates.com">info@zaherpilates.com</a>.</p>
		</div>
	</section>

</main>

<?php
get_footer();
