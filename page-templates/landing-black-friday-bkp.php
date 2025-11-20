<?php
/*
Template Name: Black Friday - LOOP
*/

get_header();

$cta_url = esc_url(home_url('/cjenik/'));
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

	.hero--black-friday .hero__title {
		color: #fff;
		max-width: 960px;
		margin: 0 auto 1.5rem;
	}

	.hero--black-friday .hero__desc p {
		color: rgba(255, 255, 255, 0.85);
		font-size: 1.1rem;
		margin-bottom: 1.5rem;
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

	.bf-countdown {
		background: #111;
		color: #fff;
		padding: 1.75rem 0;
		text-align: center;
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

	.bf-offer__table {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
		gap: 1.5rem;
		margin-top: 2rem;
	}

	.bf-offer__col {
		background: #fff;
		border-radius: 22px;
		border: 1px solid #e4dbcf;
		padding: 2.25rem;
		box-shadow: 0 24px 48px -32px rgba(30, 41, 59, 0.35);
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.bf-offer__title {
		font-size: 0.9rem;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		font-weight: 700;
		color: #7c3aed;
	}

	.bf-offer__headline {
		margin: 0;
		font-size: 1.35rem;
		color: #1f2937;
	}

	.bf-offer__tag {
		font-size: 0.9rem;
		font-weight: 600;
		color: #b45309;
	}

	.bf-offer__price {
		display: flex;
		align-items: baseline;
		gap: 0.6rem;
		font-size: 1.25rem;
		font-weight: 600;
		color: #111827;
	}

	.bf-offer__price del {
		font-size: 1rem;
		color: #9ca3af;
		font-weight: 500;
	}

	.bf-offer__list {
		margin: 0 0 30px;
		padding-left: 1.25rem;
		color: #374151;
		display: flex;
		flex-direction: column;
		gap: 0.6rem;
	}

	.bf-offer__cta {
		margin-top: auto;
	}

	.bf-offer__note {
		margin-top: 1.5rem;
		font-size: 0.95rem;
		color: #4b5563;
		text-align: center;
	}

	.bf-bonus {
		background: #111827;
		color: #fff;
		padding: 4rem 0;
		margin-top: 3rem;
	}

	.bf-bonus .section__title {
		color: #fff;
	}

	.bf-bonus__grid {
		display: grid;
		gap: 1.5rem;
		grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
		margin-top: 2rem;
	}

	.bf-bonus__item {
		background: rgba(255, 255, 255, 0.06);
		border-radius: 16px;
		padding: 1.75rem;
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.bf-bonus__title {
		font-weight: 600;
		font-size: 1.15rem;
	}

	.bf-bonus__desc {
		color: rgba(255, 255, 255, 0.75);
	}

	.bf-testimonials {
		margin-top: 3rem;
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
		margin-top: 3rem;
		text-align: center;
	}

	.bf-final-cta .section__title {
		color: #fff;
		max-width: 600px;
		margin: 0 auto 1.5rem;
	}

	.bf-final-cta .section__desc {
		color: rgba(255, 255, 255, 0.8);
		max-width: 520px;
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
			align-items: flex-start;
		}

		.bf-offer__col {
			padding: 1.75rem;
		}
	}
</style>

<main class="main landing landing--black-friday">

	<section class="hero hero--black-friday">
		<div class="grid-container">
			<div class="hero__content">
				<div class="hero__kicker">Black Friday na LOOPu ()</div>
				<header class="hero__header">
					<h1 class="hero__title">Ostvari ekskluzivan popust i dodatne pogodnosti samo ovaj tjedan</h1>
					<div class="hero__desc">
						<p>Osiguraj si neograničen pristup svim LOOP treninzima, programima po fazama ciklusa i ekskluzivnim bonusima koji ti pomažu da održiš kontinuitet bez obzira na raspored.</p>
					</div>
				</header>
				<div class="hero__cta">
					<a class="button button--large" href="<?php echo $cta_url; ?>">Iskoristi popust odmah</a>
					<p class="hero__note">Ponuda vrijedi do nedjelje, 1. prosinca u 23:59.</p>
				</div>
			</div>
		</div>
	</section>

	<section class="bf-countdown" data-deadline="2025-12-01T23:59:00+01:00">
		<div class="grid-container">
			<div class="bf-countdown__inner">
				<div class="bf-countdown__label">Preostalo vremena</div>
				<div class="bf-countdown__time">
					<span data-unit="days">00<small>dana</small></span>
					<span data-unit="hours">00<small>sati</small></span>
					<span data-unit="minutes">00<small>minuta</small></span>
					<span data-unit="seconds">00<small>sekundi</small></span>
				</div>
				<?php /* <div class="bf-countdown__hint">Ponuda vrijedi do nedjelje, 1. prosinca u 23:59.</div> */ ?>
			</div>
		</div>
	</section>

	<?php /*
	<section class="section section--bg">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Što dobivaš</div>
				<h2 class="section__title">Sve faze tvog ciklusa pokrivene u jednom planu</h2>
				<div class="section__desc">
					<p>Black Friday paket uključuje cijelu LOOP biblioteku treninga, periodizirane planove i podršku koja prati tvoje hormone i energiju kroz mjesec.</p>
				</div>
			</header>

			<div class="bf-benefits grid-x grid-margin-x small-up-1 medium-up-2 large-up-4">
				<div class="cell">
					<div class="bf-benefit">
						<div class="bf-benefit__kicker">Biblioteka</div>
						<h3 class="bf-benefit__title">200+ treninga dostupnih odmah</h3>
						<p class="bf-benefit__desc">Pilates, snaga, HIIT i mobilnost – filtrirani po fazama ciklusa i duljini od 10 do 45 minuta.</p>
					</div>
				</div>
				<div class="cell">
					<div class="bf-benefit">
						<div class="bf-benefit__kicker">Planiranje</div>
						<h3 class="bf-benefit__title">Godišnji kalendar treninga</h3>
						<p class="bf-benefit__desc">Strukturirani planovi koji te vode korak po korak i uklapaju se u tvoj raspored.</p>
					</div>
				</div>
				<div class="cell">
					<div class="bf-benefit">
						<div class="bf-benefit__kicker">Podrška</div>
						<h3 class="bf-benefit__title">Mjesečni live Q&amp;A i radionice</h3>
						<p class="bf-benefit__desc">Postavi pitanja uživo, dobivaj prilagodbe i ostani motivirana s ostatkom LOOP zajednice.</p>
					</div>
				</div>
				<div class="cell">
					<div class="bf-benefit">
						<div class="bf-benefit__kicker">Rezultati</div>
						<h3 class="bf-benefit__title">Praćenje napretka i dnevnik</h3>
						<p class="bf-benefit__desc">Preuzmi pdf dnevnike i checkliste koje ti pomažu održati ritam i slaviti male pobjede.</p>
					</div>
				</div>
			</div>

		</div>
	</section>
	*/ ?>

	<section class="pricing-plans section bf-offer">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Iznenađenja za crni tjedan</div>
				<h2 class="section__title">Biraj između dvije ekskluzivne opcije</h2>
				<div class="section__desc">
					<p>Kako te jednom godišnje za Black Friday volimo iznenaditi, ove godine možeš odabrati tromjesečni ili polugodišnji paket s posebnim pogodnostima.</p>
				</div>
			</header>

			<div class="bf-offer__table">
				<article class="bf-offer__col">
					<p class="bf-offer__title">Opcija 1</p>
					<h3 class="bf-offer__headline">25€ OFF na tromjesečnu pretplatu</h3>
					<p class="bf-offer__price">
						<span>Redovna cijena:</span>
						<del>79,99 €</del>
						<span>&rarr;</span>
						<span>54,99 €</span>
					</p>
					<ul class="bf-offer__list">
						<li>Pristup cijeloj LOOP platformi na 3 mjeseca.</li>
						<li>Mogućnost otkazivanja pretplate u bilo kojem trenutku unutar korisničkog računa.</li>
					</ul>
					<div class="bf-offer__cta">
						<a class="button" href="<?php echo $cta_url; ?>">Ugrabi ponudu</a>
					</div>
				</article>

				<article class="bf-offer__col">
					<p class="bf-offer__title">Opcija 2</p>
					<h3 class="bf-offer__headline">Polugodišnja pretplata + 1:1 coaching call sa Ivanom</h3>
					<p class="bf-offer__tag">Ograničeno na 7 mjesta!</p>
					<p class="bf-offer__price">
						<span>Cijena:</span>
						<span>149,99 €</span>
					</p>
					<ul class="bf-offer__list">
						<li>6 mjeseci pristupa LOOP platformi – jedan mjesec besplatno.</li>
						<li>Individualni coaching call (60 min) s Ivanom na temu po tvom izboru: jasnoća, smjernice za dalje ili poboljšanje kvalitete života kroz jednostavne alate.</li>
						<li>Kupnjom ulaziš u izbor jedne osobe koja će dobiti coaching u trajanju od <strong>12 susreta besplatno.</strong></li>
					</ul>
					<div class="bf-offer__cta">
						<a class="button" href="<?php echo $cta_url; ?>">Ugrabi ponudu</a>
					</div>
				</article>
			</div>

			<p class="bf-offer__note"><strong>Obje opcije vrijede za postojeće članice LOOP-a prilikom nadogradnje na višu pretplatu.</strong></p>
		</div>
	</section>

	<section class="bf-bonus">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Ekskluzivni bonusi</div>
				<h2 class="section__title">Želimo ti dati sve alate da uspiješ</h2>
				<div class="section__desc">
					<p>Uz popust dobivaš i set resursa koji ti pomažu izgraditi rutinu i ostati u pokretu kroz cijelu godinu.</p>
				</div>
			</header>

			<div class="bf-bonus__grid">
				<div class="bf-bonus__item">
					<div class="bf-bonus__title">Planeri treninga za cijeli mjesec</div>
					<div class="bf-bonus__desc">Printable planeri i digitalni kalendar koji te podsjećaju kad je vrijeme za snagu, a kad za oporavak.</div>
				</div>
				<div class="bf-bonus__item">
					<div class="bf-bonus__title">Video library s kratkim mobilnost rutinama</div>
					<div class="bf-bonus__desc">Savršen dodatak kad ti treba samo 5 minuta za reset leđa, kukova ili ramena.</div>
				</div>
				<div class="bf-bonus__item">
					<div class="bf-bonus__title">Bonus radionica: Strategije za dosljednost</div>
					<div class="bf-bonus__desc">Nauči kako uklopiti trening u svaki tjedan, čak i kad sve ostalo pada u vodu.</div>
				</div>
				<div class="bf-bonus__item">
					<div class="bf-bonus__title">Podrška tima Zaher Pilatesa</div>
					<div class="bf-bonus__desc">Q&amp;A, motivacijski check-inovi i odgovori na tvoja pitanja u community grupi.</div>
				</div>
			</div>
		</div>
	</section>

	<section class="testimonials section bf-testimonials">
		<div class="grid-container full">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Što kažu članice</div>
				<h2 class="section__title">Rezultati koji traju duže od Black Friday vikenda</h2>
			</header>

			<main class="testimonials__main">
				<div class="testimonials__grid">
					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>"Uz LOOP prvi put u životu imam konzistentnu rutinu. Ova ponuda je game changer jer znam da me čeka nova količina programa i motivacije za cijelu godinu."</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Maja</div>
								<div class="testimonials__person-position">LOOP članica od 2023.</div>
							</div>
						</div>
					</div>
					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>"Programi su prilagođeni ciklusu pa se više ne forsiram kad sam iscrpljena. Bonus planeri su me naučili kako da trening ipak stane u dan."</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Lucija</div>
								<div class="testimonials__person-position">Poduzetnica i mama dvoje klinaca</div>
							</div>
						</div>
					</div>
					<div class="testimonials__item">
						<div class="testimonials__blockquote">
							<p>"Live radionice i community su razlog zbog kojeg se vraćam. Popust je samo šlag na torti."</p>
						</div>
						<div class="testimonials__person">
							<div class="testimonials__person-wrap">
								<div class="testimonials__person-name">Andrea</div>
								<div class="testimonials__person-position">Članica od 2022.</div>
							</div>
						</div>
					</div>
				</div>
			</main>
		</div>
	</section>

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

	<section class="bf-final-cta">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<h2 class="section__title">Prigrli kraj godine jača nego ikad</h2>
				<div class="section__desc">
					<p>Uloži u godinu dana podrške, treninga i zajednice koja te drži odgovornom. Iskoristi Black Friday popust dok je aktivan.</p>
				</div>
			</header>

			<a class="button button--large" href="<?php echo $cta_url; ?>">Prijavi se danas</a>
			<p class="hero__note">Ne čekaj – link se gasi 1. prosinca u ponoć.</p>
		</div>
	</section>

</main>

<?php
get_footer();
