<?php
/*
Template Name: Black Week LOOP 2024
*/

get_header();
the_post();

$offers_anchor = '#bf-ponude';
$checkout_url = home_url('/cjenik/');
$assets_url   = trailingslashit(get_stylesheet_directory_uri()) . 'dist/assets/images';

$testimonials = [
    [
        'name'   => 'Maja',
        'avatar' => $assets_url . '/testimonials/ana.webp',
        'quote'  => 'Željela sam ti reći... <strong>tako se osjećam nježno i ženstveno, a opet snažno kad vježbam s tobom</strong>, jer ti imaš takvu energiju koju prosljeđuješ!',
    ],
    [
        'name'   => 'Manuela',
        'avatar' => $assets_url . '/testimonials/anamarija.webp',
        'quote'  => 'Vježbe su odlične, baš su mi sjele, posebno videa s vježbama koje ti vodiš, paše mi ta fluidnost i prisutnost. <strong>Uz posao, dvoje male djece, brigu o kućanskim obvezama, ja stignem i po 4-5 puta tjedno odvježbati</strong>, to je dio dana kad djeca zaspu, za mene i volim to što brinem o svom tijelu.',
    ],
    [
        'name'   => 'Vida',
        'avatar' => $assets_url . '/testimonials/selma.webp',
        'quote'  => 'Ajme Ivana, ove tvoje vježbe su predivne! Toliko opuštajuće, ovaj prvi trening mi nije bio zahtjevan, ali sam osjetila svaki djelić tijela i kako ono radi! <strong>Hvala ti na toliko predanosti i detaljnim opisima dok izvodiš vježbe</strong>. Prebrzo je prošlo, nisam ni skužila da je gotovo! Čista uživancija!',
    ],
    [
        'name'   => 'Zrinka',
        'avatar' => $assets_url . '/testimonials/sonja.webp',
        'quote'  => 'Draga Ivana, samo ću ti reći da si nikada nisam priuštila bolje treninge. Loop-u je toliko izbora da stvarno nema izgovora da se ne odradi bilo kakav trening u danu. <strong>Kada imam više vremena uzmem si neke zahtjevnije treninge, kada uopće nemam vremena uzmem one najkraće i osjećaj je odličan jer ipak i u takvom danu napravim nešto za svoje zdravlje</strong>. Vježbe su mi odlične, nije dosadno.',
    ],
    [
        'name'   => 'Iva',
        'avatar' => $assets_url . '/placeholder.jpg',
        'quote'  => 'Draga Ivana, htjela sam samo reći kako sam nastavila trenirati u Loopu, i i dalje sam presretna koliko <strong>stvarno s guštom treniram i osjećam se snažno tijekom i nakon treninga</strong>. Obožavam kako vodiš trening!',
    ],
];
?>

<style>
	.landing--bf-brief {
		background: #f7f5f3;
		color: #111;
	}

	.hero--bf-brief {
		background: #0a0a0a;
		color: #fff;
		padding: 4.5rem 0 3.5rem;
		text-align: center;
	}

	.hero--bf-brief .hero__meta {
		font-size: 0.95rem;
		letter-spacing: 0.08em;
		text-transform: uppercase;
		color: rgba(255, 255, 255, 0.7);
		margin-bottom: 1rem;
	}

	.hero--bf-brief .hero__title {
		max-width: 720px;
		margin: 0 auto 1rem;
		color: #fff;
	}

	.hero--bf-brief .hero__subtitle {
		font-size: 1.2rem;
		color: rgba(255, 255, 255, 0.9);
		margin-bottom: 1.25rem;
	}

	.hero--bf-brief .hero__desc {
		max-width: 640px;
		margin: 0 auto 1.5rem;
		color: rgba(255, 255, 255, 0.85);
	}

	.hero--bf-brief .hero__note {
		margin-top: 1rem;
		font-size: 0.95rem;
		color: rgba(255, 255, 255, 0.78);
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

	.bf-story {
		padding: 4rem 0;
	}

	.bf-story__wrapper {
		row-gap: 2rem;
	}

	.bf-story__image {
		background: #fff;
		border-radius: 18px;
		padding: 0.75rem;
		box-shadow: 0 30px 60px -35px rgba(15, 23, 42, 0.5);
	}

	.bf-story__image img {
		width: 100%;
		display: block;
		border-radius: 14px;
	}

	.bf-story__image figcaption {
		font-size: 0.8rem;
		color: #6b7280;
		margin-top: 0.5rem;
	}

	.bf-story__note {
		margin-top: 1.25rem;
		font-weight: 600;
		color: #7c3aed;
	}

	.bf-testimonials {
		padding: 4rem 0;
	}

	.bf-testimonials__grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
		gap: 1.5rem;
		margin-top: 2rem;
	}

	.bf-testimonial {
		background: #fff;
		border-radius: 18px;
		padding: 1.75rem;
		box-shadow: 0 24px 48px -30px rgba(15, 23, 42, 0.45);
		height: 100%;
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.bf-testimonial__header {
		display: flex;
		align-items: center;
		gap: 0.75rem;
	}

	.bf-testimonial__avatar {
		width: 52px;
		height: 52px;
		border-radius: 999px;
		object-fit: cover;
		border: 2px solid #facc15;
	}

	.bf-testimonial__name {
		margin: 0;
		font-size: 1rem;
		font-weight: 700;
	}

	.bf-testimonial__tag {
		margin: 0;
		font-size: 0.85rem;
		color: #6b7280;
	}

	.bf-testimonial__quote {
		margin: 0;
		color: #374151;
		font-size: 0.95rem;
		line-height: 1.6;
	}

	.bf-offers {
		padding: 4rem 0;
	}

	.bf-offers__table {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
		gap: 1.5rem;
		margin-top: 2rem;
	}

	.bf-offers__col {
		background: #fff;
		border-radius: 22px;
		padding: 2.25rem 2rem;
		border: 1px solid #e8dfd3;
		box-shadow: 0 30px 60px -35px rgba(15, 23, 42, 0.35);
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.bf-offers__badge {
		align-self: flex-start;
		background: #facc15;
		color: #111;
		font-weight: 600;
		border-radius: 999px;
		padding: 0.3rem 0.85rem;
		letter-spacing: 0.08em;
		text-transform: uppercase;
		font-size: 0.8rem;
	}

	.bf-offers__title {
		margin: 0;
		font-size: 1.35rem;
		color: #1f2937;
	}

	.bf-offers__tag {
		font-size: 0.9rem;
		color: #b45309;
		font-weight: 600;
	}

	.bf-offers__price {
		display: flex;
		align-items: baseline;
		gap: 0.8rem;
		font-size: 2rem;
		font-weight: 700;
	}

	.bf-offers__price del {
		font-size: 1rem;
		color: #9ca3af;
	}

	.bf-offers__list {
		margin: 0;
		padding-left: 1.2rem;
		color: #374151;
		display: flex;
		flex-direction: column;
		gap: 0.55rem;
	}

	.bf-offers__cta {
		margin-top: auto;
	}

	.bf-note {
		margin-top: 1.5rem;
		font-size: 0.95rem;
		color: #4b5563;
	}

	.bf-question {
		margin-top: 1.5rem;
		padding: 1rem 1.25rem;
		background: rgba(124, 58, 237, 0.08);
		border-radius: 14px;
		display: flex;
		flex-wrap: wrap;
		gap: 0.75rem;
		align-items: center;
		color: #4c1d95;
		font-weight: 600;
	}

	.bf-question a {
		color: #4c1d95;
		text-decoration: underline;
	}

	.bf-how {
		padding: 3rem 0 2rem;
	}

	.bf-final {
		background: #000;
		color: #fff;
		text-align: center;
		padding: 4rem 0;
		margin-top: 2rem;
	}

	.bf-final .section__title {
		color: #fff;
		margin-bottom: 1.25rem;
	}

	.bf-final__kicker {
		font-size: 0.95rem;
		letter-spacing: 0.08em;
		text-transform: uppercase;
		color: rgba(255, 255, 255, 0.65);
	}

	.bf-final__cta {
		display: flex;
		gap: 1rem;
		flex-wrap: wrap;
		justify-content: center;
	}

	@media (max-width: 640px) {
		.hero--bf-brief {
			padding: 3.5rem 0 3rem;
		}

		.bf-offers__price {
			flex-direction: column;
			align-items: flex-start;
		}

		.bf-final__cta {
			flex-direction: column;
		}
	}
</style>

<main class="main landing landing--bf-brief">
	<section class="hero hero--bf-brief">
		<div class="grid-container">
			<p class="hero__meta">LOOP BF 24.11. – 1.12. (završava 30.11. u ponoć)</p>
			<h1 class="hero__title">Crni tjedan na LOOPU</h1>
			<p class="hero__subtitle">Ostvari ekskluzivan popust i dodatne pogodnosti samo ovaj tjedan.</p>
			<div class="hero__desc">
				<p>Osiguraj si neograničen pristup svim LOOP treninzima, programima po fazama ciklusa i ekskluzivnim bonusima koji ti pomažu da održiš kontinuitet bez obzira na raspored.</p>
				<p>Crni tjedan donosi dvije različite ponude zahvalnosti isključivo za postojeće članice LOOP-a – klik te vodi ravno do odabira koji najviše nagrađuje tvoju odanost.</p>
			</div>
			<div class="hero__cta">
				<a class="button button--large" href="<?php echo esc_url($offers_anchor); ?>">Pogledaj ponudu</a>
				<p class="hero__note">Ponuda vrijedi do subote, 30. studenog u 23:59.</p>
			</div>
		</div>
	</section>

	<section class="bf-countdown" data-deadline="2024-11-30T23:59:00+01:00">
		<div class="grid-container">
			<div class="bf-countdown__inner">
				<div class="bf-countdown__label">Do isteka ponude ostalo je</div>
				<div class="bf-countdown__time">
					<span data-unit="days">00<small>dana</small></span>
					<span data-unit="hours">00<small>sati</small></span>
					<span data-unit="minutes">00<small>minuta</small></span>
					<span data-unit="seconds">00<small>sekundi</small></span>
				</div>
				<div class="bf-countdown__hint">Odbrojavanje se zaustavlja 30. studenog u ponoć.</div>
			</div>
		</div>
	</section>

	<section class="section bf-story">
		<div class="grid-container">
			<div class="grid-x grid-margin-x align-middle bf-story__wrapper">
				<div class="cell small-12 medium-6">
					<header class="section__header">
						<div class="section__subtitle">Zašto LOOP?</div>
						<h2 class="section__title">Pametan trening usklađen s tvojim ciklusom</h2>
						<div class="section__desc">
							<p>LOOP je online fitness platforma na kojoj treniraju žene svjesne važnosti tjelovježbe za emocionalno i fizičko zdravlje.</p>
							<p>Za fit tijelo ne trebaš trenirati puno, nego pametno i u skladu s menstrualnim ciklusom. Upravo zato svaki program i plan na LOOPU prati tvoju energiju i realan raspored.</p>
						</div>
						<p class="bf-story__note">Klikom na „Pogledaj ponudu” odmah se spuštaš do opcija.</p>
					</header>
				</div>
				<div class="cell small-12 medium-6">
					<figure class="bf-story__image">
						<img src="<?php echo esc_url($assets_url . '/hero-loop.webp'); ?>" alt="LOOP članica u pokretu">
						<figcaption>Slika se može izrezati po visini kako bi naglasila pokret.</figcaption>
					</figure>
				</div>
			</div>
		</div>
	</section>

	<section class="section bf-testimonials">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Testimonijali</div>
				<h2 class="section__title">Evo zašto stotina žena bira LOOP</h2>
			</header>
			<div class="bf-testimonials__grid">
				<?php foreach ($testimonials as $testimonial) : ?>
					<article class="bf-testimonial">
						<div class="bf-testimonial__header">
							<img class="bf-testimonial__avatar" src="<?php echo esc_url($testimonial['avatar']); ?>" alt="<?php echo esc_attr($testimonial['name']); ?>">
							<div>
								<p class="bf-testimonial__name"><?php echo esc_html($testimonial['name']); ?></p>
								<p class="bf-testimonial__tag">Članica LOOP-a</p>
							</div>
						</div>
						<p class="bf-testimonial__quote"><?php echo wp_kses_post($testimonial['quote']); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section bf-offers" id="bf-ponude">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<div class="section__subtitle">Iznenađenja za crni tjedan</div>
				<h2 class="section__title">Dvije odanosti-ponude osmišljene su samo za postojeće članice LOOP-a kao zahvalnica za vašu podršku.</h2>
			</header>

			<div class="bf-offers__table">
				<article class="bf-offers__col">
					<div class="bf-offers__badge">Opcija 1</div>
					<h3 class="bf-offers__title">25€ OFF na tromjesečnu pretplatu</h3>
					<div class="bf-offers__price">
						<del>79,99 €</del>
						<span>54,99 €</span>
					</div>
					<ul class="bf-offers__list">
						<li>Pristup cijeloj LOOP platformi na 3 mjeseca.</li>
						<li>Mogućnost otkazivanja pretplate u bilo kojem trenutku unutar korisničkog računa.</li>
					</ul>
					<div class="bf-offers__cta">
						<a class="button button--ghost" href="<?php echo esc_url($checkout_url); ?>">Ugrabi ponudu</a>
					</div>
				</article>

				<article class="bf-offers__col">
					<div class="bf-offers__badge">Opcija 2</div>
					<h3 class="bf-offers__title">Polugodišnja pretplata + 1:1 coaching call sa Ivanom</h3>
					<p class="bf-offers__tag">Ograničeno na 7 mjesta</p>
					<div class="bf-offers__price">
						<span>149,99 €</span>
					</div>
					<ul class="bf-offers__list">
						<li>6 mjeseci pristupa LOOP platformi – jedan mjesec dobivaš besplatno.</li>
						<li>Individualni coaching call (60 min) s Ivanom na temu po tvom izboru: jasnoća, smjernice za dalje ili poboljšanje kvalitete života kroz jednostavne alate.</li>
						<li>Kupnjom ulaziš u izbor jedne osobe koja će dobiti coaching u trajanju od 12 susreta potpuno besplatno.</li>
					</ul>
					<div class="bf-offers__cta">
						<a class="button" href="<?php echo esc_url($checkout_url); ?>">Ugrabi ponudu</a>
					</div>
				</article>
			</div>

			<p class="bf-note"><strong>Obje opcije vrijede za postojeće članice LOOP-a prilikom nadogradnje na višu pretplatu.</strong></p>
			<div class="bf-question">
				<span>Pitanje za Tomislava?</span>
				<a href="mailto:info@zaherpilates.com?subject=Pitanje%20za%20Tomislava">Piši mi</a>
			</div>
		</div>
	</section>

	<section class="section bf-how">
		<div class="grid-container">
			<header class="section__header">
				<div class="section__subtitle">Kako ostvariti ovu jednokratnu pogodnost?</div>
				<h2 class="section__title">Sve što trebaš je kliknuti na odabranu opciju i pratiti daljnje korake.</h2>
			</header>
			<div class="section__desc">
				<p>Postojeće članice svoju pretplatu mogu unaprijediti unutar korisničkog računa. Kada odabereš paket, vodič te provodi kroz cijeli proces. Ako ti bude trebao kupon kod za popust, ubacit ćemo ga u korake nakon odabira ponude.</p>
				<p>Za dodatna pitanja tipkaj nam na <a href="mailto:info@zaherpilates.com">info@zaherpilates.com</a> i rado ćemo pomoći.</p>
			</div>
		</div>
	</section>

	<section class="bf-final">
		<div class="grid-container">
			<p class="bf-final__kicker">Savršeni trenutak je sada</p>
			<h2 class="section__title">Shvati ovo kao znak i iskoristi ponudu.</h2>
			<div class="bf-final__cta">
				<a class="button button--large" href="<?php echo esc_url($checkout_url); ?>">Osiguraj mjesto</a>
				<a class="button button--ghost" href="<?php echo esc_url($offers_anchor); ?>">Pogledaj opcije</a>
			</div>
		</div>
	</section>
</main>

<?php get_footer(); ?>
