<?php
/**
 * Template Name: Landing - Aktivni moment za mene
 * Template Post Type: page
 *
 * Static first pass; editable ACF content will follow after template approval.
 * Source: https://postpartum.zaherpilates.ch/aktivni-moment-za-mene
 * Landing page publication is planned for 2026-11-01, not the challenge start.
 * Public copy describes the offer without a launch announcement. Checkout uses
 * MemberPress membership 1581 locally / 1659 online, with coupon AMEarlyBird.
 * Photos in dist/assets/images/aktivni-moment come from the same source page.
 *
 * @package FoundationPress
 */

get_header();

$moment_images = get_template_directory_uri() . '/dist/assets/images/aktivni-moment/';

// Read the actual membership price and coupon rules so an expired or disabled
// offer cannot leave a hard-coded promotional price on the landing page.
$moment_membership_id     = theme_get_aktivni_moment_membership_id();
$moment_coupon_code       = 'AMEarlyBird';
$moment_checkout_url      = '';
$moment_price_text        = '';
$moment_regular_price_text = '';
$moment_billing_text      = '';
$moment_has_discount      = false;

if ( class_exists( 'MeprProduct' ) && 'memberpressproduct' === get_post_type( $moment_membership_id ) && 'publish' === get_post_status( $moment_membership_id ) ) {
	$moment_product      = new MeprProduct( $moment_membership_id );
	$moment_checkout_url = $moment_product->url();
	$moment_price        = (float) $moment_product->price;

	if ( class_exists( 'MeprCoupon' ) && MeprCoupon::is_valid_coupon_code( $moment_coupon_code, $moment_membership_id ) ) {
		$moment_price        = (float) $moment_product->adjusted_price( $moment_coupon_code, false );
		$moment_checkout_url = add_query_arg( 'coupon', $moment_coupon_code, $moment_checkout_url );
		$moment_has_discount = $moment_price < (float) $moment_product->price;
	}

	$moment_price_text         = MeprAppHelper::format_currency( $moment_price, true, false, true );
	$moment_regular_price_text = MeprAppHelper::format_currency( $moment_product->price, true, false, true );
	$moment_billing_text       = $moment_product->is_one_time_payment()
		? 'Jednokratno plaćanje'
		: theme_get_billing_period_text( $moment_product );
}

$moment_workouts = array(
	array(
		'title'    => 'Holy abs!',
		'duration' => '6 minuta',
		'image'    => 'holy-abs.webp',
		'alt'      => 'Ivana demonstrira vježbu za trbušne mišiće na prostirci.',
	),
	array(
		'title'    => 'Booty burn',
		'duration' => '10 minuta',
		'image'    => 'booty-burn.webp',
		'alt'      => 'Ivana demonstrira vježbu za gluteuse na prostirci.',
	),
	array(
		'title'    => 'Trening visokog intenziteta',
		'duration' => '11 minuta',
		'image'    => 'high-intensity.webp',
		'alt'      => 'Ivana demonstrira vježbu iz treninga visokog intenziteta.',
	),
);

$moment_testimonials = array(
	array(
		'name' => 'Lucija',
		'text' => 'Meni koja imam troje djece i puno radno vrijeme, ovakvi treninzi puno znače.',
	),
	array(
		'name' => 'Josipa',
		'text' => 'Imam osjećaj da sam se posvetila sebi a nije mi oduzelo puno vremena.',
	),
	array(
		'name' => 'Marija',
		'text' => 'Ovo je super. Konačno sam nešto napravila za sebe nakon poroda.',
	),
);

$moment_faqs = array(
	array(
		'question' => 'Što mi treba od opreme?',
		'answer'   => 'Dovoljna ti je prostirka za vježbanje. Ako želiš podići intenzitet, možeš koristiti utege oko ručnog ili skočnog zgloba, ali za izvedbu vježbi nisu potrebni rekviziti.',
	),
	array(
		'question' => 'Jesu li vježbe prilagođene početnicama?',
		'answer'   => 'Da. Vježbe su uglavnom početničkog nivoa. Ako neku vježbu još ne možeš izvesti, smanji broj ponavljanja, napravi pauzu i nastavi u svom ritmu. Ne mora biti sve ili ništa.',
	),
	array(
		'question' => 'Koliko traju treninzi i moram li vježbati u određeno vrijeme?',
		'answer'   => 'Počinjemo sa 6 minuta i kroz 14 dana dolazimo do najviše 15 minuta. Treninzi su snimljeni unaprijed pa možeš vježbati u skladu sa svojim rasporedom. Iznimka je zvučna kupka, koja traje 30 minuta.',
	),
	array(
		'question' => 'Jesu li vježbe za trudnice ili za postpartum oporavak?',
		'answer'   => 'Trudnicama ne predlažem ovaj izazov jer vježbe nisu osmišljene za trudnoću. Ovo također nije program oporavka nakon poroda. Ako već vježbaš po jednom od mojih postpartum programa, u izazovu možeš primijeniti alate koje si naučila i pratiti na što trebaš paziti pri nastavku jačanja.',
	),
);
?>

<main id="main-content" class="main landing landing--aktivni-moment">
	<section class="moment-hero" aria-labelledby="moment-title">
		<div class="grid-container">
			<div class="moment-hero__grid">
				<div class="moment-hero__copy">
					<p class="section__subtitle">Online fitness izazov · 14 dana za tebe</p>
					<h1 id="moment-title">Aktivni moment<br><em>za mene.</em></h1>
					<p class="moment-hero__lead">Jedan mali trenutak u danu.<br>Za više snage, više energije i bolji osjećaj u svom tijelu.</p>
					<p>Odvoji 6 do 15 minuta za pokret i napravi prvi korak prema navici koja ostaje. U svom ritmu, iz svog doma.</p>
					<div class="moment-actions">
						<a class="button button--large button--dark" href="#moment-ponuda">Otkrij svoj aktivni moment <span aria-hidden="true">↗</span></a>
						<a class="moment-text-link" href="#moment-kako">Kako izgleda izazov <span aria-hidden="true">↓</span></a>
					</div>
				</div>
				<figure class="moment-hero__visual">
					<img src="<?php echo esc_url( $moment_images . 'hero.webp' ); ?>" alt="Ivana Zaher izvodi pilates vježbu na prostirci." width="1024" height="683" fetchpriority="high">
					<figcaption><span>Pokret je izbor za sebe.</span><span>Ivana Zaher</span></figcaption>
				</figure>
			</div>
			<dl class="moment-facts">
				<div><dt>14 dana</dt><dd>za stvaranje navike</dd></div>
				<div><dt>6–15 min</dt><dd>za tvoj dnevni trening</dd></div>
				<div><dt>12 treninga</dt><dd>snimljenih unaprijed</dd></div>
				<div><dt>Tvoj ritam</dt><dd>vježbaš kad tebi odgovara</dd></div>
			</dl>
		</div>
	</section>

	<nav class="moment-nav" aria-label="Sadržaj izazova">
		<div class="grid-container">
			<a href="#moment-pristup">Pristup</a>
			<a href="#moment-kako">Kako funkcionira</a>
			<a href="#moment-treninzi">Treninzi</a>
			<a href="#moment-iskustva">Iskustva</a>
			<a href="#moment-pitanja">Pitanja</a>
			<a href="#moment-ponuda">Ponuda <span aria-hidden="true">↗</span></a>
		</div>
	</nav>

	<section id="moment-pristup" class="section moment-intro" aria-labelledby="moment-intro-title">
		<div class="grid-container moment-split">
			<header>
				<p class="section__subtitle">Mali koraci. Održiva promjena.</p>
				<h2 id="moment-intro-title" class="section__title">Za bolji osjećaj.<br>Za snažnije tijelo.<br><em>Za sebe.</em></h2>
			</header>
			<div class="moment-prose">
				<p class="moment-lead">Koliko puta si krenula pa odustala? Možda ti za novi početak treba samo nekoliko minuta koje stvarno možeš odvojiti.</p>
				<p>Ovaj izazov poziva te da svaki dan odabereš jedan trenutak za sebe i u njemu budeš aktivna. Da se bolje osjećaš u svom tijelu i s više lakoće prolaziš kroz svakodnevicu.</p>
				<p><strong>U dosljednosti je ključ.</strong> Počinjemo sa šest minuta i postupno gradimo do petnaest. Uz pokret upoznaješ i važnost odmora i oporavka, kao ravnopravnog dijela tog putovanja.</p>
				<p class="moment-intro__note">Vježbanje nije kazna za ono što si pojela. Ono je trenutak koji biraš za sebe.</p>
			</div>
		</div>
	</section>

	<section id="moment-kako" class="section moment-how" aria-labelledby="moment-how-title">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<p class="section__subtitle">Kako funkcionira</p>
				<h2 id="moment-how-title" class="section__title">Manje planiranja.<br>Više vremena za pokret.</h2>
				<p class="section__desc">Snimljeni treninzi prate tvoj raspored, a trajanje raste korak po korak.</p>
			</header>
			<ol class="moment-steps">
				<li><span class="moment-steps__number" aria-hidden="true">01</span><h3>Odaberi svoj trenutak</h3><p>Ujutro, tijekom pauze ili navečer. Treninzi su snimljeni unaprijed, a ti biraš kad ćeš stati na prostirku.</p></li>
				<li><span class="moment-steps__number" aria-hidden="true">02</span><h3>Kreni sa 6 minuta</h3><p>Počni malim korakom. Kroz četrnaest dana postupno dolazimo do treninga koji traju najviše 15 minuta.</p></li>
				<li><span class="moment-steps__number" aria-hidden="true">03</span><h3>Ostavi mjesta za odmor</h3><p>Oporavak je dio procesa. Izazov zaokružujemo 30-minutnom zvučnom kupkom za trenutak usporavanja.</p></li>
			</ol>
			<div class="moment-extras">
				<p class="section__subtitle">Više od treninga</p>
				<div><h3>Seminar o cycle syncingu</h3><p>Upoznaj pristup vježbanju u skladu sa svojim ciklusom.</p></div>
				<div><h3>Zajedničko planiranje godine</h3><p>Postavljanje namjera za odnose, zdravlje, financije i posao, sa članicama LOOPa.</p></div>
			</div>
		</div>
	</section>

	<section id="moment-treninzi" class="section moment-workouts" aria-labelledby="moment-workouts-title">
		<div class="grid-container">
			<header class="section__header moment-heading-row">
				<div><p class="section__subtitle">Zaviri u izazov</p><h2 id="moment-workouts-title" class="section__title">Kratko traje.<br>Dobar osjećaj ostaje.</h2></div>
				<p class="section__desc">Ovo su neki od najpopularnijih treninga u izazovu. Za početak ti treba samo prostirka.</p>
			</header>
			<div class="moment-workouts__grid">
				<?php foreach ( $moment_workouts as $moment_workout ) : ?>
					<article class="moment-workout">
						<div class="moment-workout__image"><img src="<?php echo esc_url( $moment_images . $moment_workout['image'] ); ?>" alt="<?php echo esc_attr( $moment_workout['alt'] ); ?>" width="800" height="450" loading="lazy" decoding="async"><span><?php echo esc_html( $moment_workout['duration'] ); ?></span></div>
						<h3><?php echo esc_html( $moment_workout['title'] ); ?></h3>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section id="moment-iskustva" class="section moment-testimonials" aria-labelledby="moment-testimonials-title">
		<div class="grid-container">
			<header class="section__header section__header--center">
				<p class="section__subtitle">Njihov aktivni moment</p>
				<h2 id="moment-testimonials-title" class="section__title">Vrijeme za sebe<br>stane i u pun raspored.</h2>
			</header>
			<div class="moment-testimonials__grid">
				<?php foreach ( $moment_testimonials as $moment_testimonial ) : ?>
					<figure class="moment-quote">
						<span class="moment-quote__mark" aria-hidden="true">“</span>
						<blockquote><p><?php echo esc_html( $moment_testimonial['text'] ); ?></p></blockquote>
						<figcaption><?php echo esc_html( $moment_testimonial['name'] ); ?><span>Sudionica izazova</span></figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section moment-about" aria-labelledby="moment-about-title">
		<div class="grid-container moment-split moment-split--about">
			<div class="moment-about__image"><img src="<?php echo esc_url( get_template_directory_uri() . '/dist/assets/images/ivana-zaher-ver.webp' ); ?>" alt="Ivana Zaher, pilates trenerica i autorica izazova." width="600" height="800" loading="lazy" decoding="async"></div>
			<div class="moment-prose">
				<p class="section__subtitle">Tvoja trenerica</p>
				<h2 id="moment-about-title" class="section__title">Hej, ja sam Ivana.<br><em>Vježbamo zajedno.</em></h2>
				<p class="moment-lead">Pilates trenerica, poduzetnica i majka. Razumijem koliko je ponekad izazovno pronaći vrijeme za sebe.</p>
				<p>Radim sa ženama koje se žele osjećati i izgledati dobro u svom tijelu. Poštujemo žensku fiziologiju i gradimo održiv odnos s tjelovježbom, u skladu s tijelom.</p>
				<p>Do sada sam radila s više od <strong>1000 žena</strong> na oporavku nakon poroda, izgradnji samopouzdanja u tijelu i stvaranju navike redovitog vježbanja.</p>
				<p class="moment-about__signature">Veselim ti se,<br><em>Ivana</em></p>
			</div>
		</div>
	</section>

	<section id="moment-ponuda" class="section moment-offer" aria-labelledby="moment-offer-title">
		<div class="grid-container moment-split">
			<div class="moment-offer__copy">
				<p class="section__subtitle">Tvoj mali korak prema sebi</p>
				<h2 id="moment-offer-title" class="section__title">Kladi se na sebe.<br><em>Počni malim.</em></h2>
				<p class="moment-lead">Za tvoj aktivni moment ne treba ti savršen raspored. Treba ti trenutak koji ćeš odabrati za sebe.</p>
				<p>Pokret, dosljednost i odmor. Četrnaest dana u kojima istražuješ kako vježbanje može postati dio tvoje svakodnevice.</p>
			</div>
			<div class="moment-offer__card">
				<p class="moment-offer__status">Online fitness izazov · 14 dana za tebe</p>
				<h3>Aktivni moment za mene</h3>
				<?php if ( '' !== $moment_price_text ) : ?>
					<?php if ( $moment_has_discount ) : ?>
						<p class="moment-offer__price-label">Early bird cijena</p>
					<?php endif; ?>
					<p class="moment-offer__price">
						<?php if ( $moment_has_discount ) : ?>
							<span class="show-for-sr">Redovna cijena:</span><del><?php echo esc_html( $moment_regular_price_text ); ?></del>
							<span class="show-for-sr">Early bird cijena:</span>
						<?php endif; ?>
						<strong><?php echo esc_html( $moment_price_text ); ?></strong>
					</p>
					<p class="moment-offer__billing"><?php echo esc_html( $moment_billing_text ); ?></p>
				<?php endif; ?>
				<ul class="moment-checklist">
					<li>12 snimljenih treninga od 6 do 15 minuta</li>
					<li>14 dana za izgradnju navike kretanja</li>
					<li>Seminar na temu cycle syncinga</li>
					<li>Zajedničko planiranje godine s LOOP zajednicom</li>
					<li>30-minutna zvučna kupka za završetak</li>
				</ul>
				<?php if ( '' !== $moment_checkout_url ) : ?>
					<a class="button button--large button--dark" href="<?php echo esc_url( $moment_checkout_url ); ?>" aria-describedby="moment-enrollment-note">Pridruži se izazovu <span aria-hidden="true">↗</span></a>
				<?php else : ?>
					<button class="button button--large button--dark" type="button" disabled aria-describedby="moment-enrollment-note">Pridruži se izazovu</button>
				<?php endif; ?>
				<p id="moment-enrollment-note" class="moment-offer__note"><?php echo $moment_has_discount ? 'Early bird popust primjenjuje se automatski pri kupnji.' : 'Treninzi su snimljeni unaprijed. Vježbaš u skladu sa svojim rasporedom.'; ?></p>
			</div>
		</div>
	</section>

	<section id="moment-pitanja" class="section moment-faq" aria-labelledby="moment-faq-title">
		<div class="grid-container moment-split">
			<header>
				<p class="section__subtitle">Prije nego počneš</p>
				<h2 id="moment-faq-title" class="section__title">Možda se<br><em>pitaš…</em></h2>
				<p>Od opreme do rasporeda — sve što trebaš znati o izazovu.</p>
			</header>
			<div class="moment-faq__list">
				<?php foreach ( $moment_faqs as $moment_faq ) : ?>
					<details class="moment-faq__item">
						<summary><?php echo esc_html( $moment_faq['question'] ); ?></summary>
						<div><p><?php echo esc_html( $moment_faq['answer'] ); ?></p></div>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section moment-final" aria-labelledby="moment-final-title">
		<div class="grid-container">
			<p class="section__subtitle">Jedan trenutak. Svaki dan.</p>
			<h2 id="moment-final-title" class="section__title">Tvoj dan ima puno trenutaka.<br><em>Neka jedan bude za tebe.</em></h2>
			<p>Bez frustracije i bez svaljivanja krivnje na sebe ili svoje tijelo.</p>
			<a class="button button--large" href="#moment-ponuda">Pronađi svoj aktivni moment <span aria-hidden="true">↑</span></a>
		</div>
	</section>
</main>

<?php get_footer(); ?>
