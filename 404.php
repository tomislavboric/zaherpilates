<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

get_header(); ?>

<section class="error-404">
	<?php
	$catalog_page = get_page_by_path( 'katalog' );
	$catalog_url  = $catalog_page instanceof WP_Post ? get_permalink( $catalog_page ) : home_url( '/katalog/' );
	$account_url  = home_url( '/moj-racun/' );
	$contact_url  = home_url( '/kontakt/' );
	?>

	<div class="error-404__inner">
		<section class="error-404__hero" aria-labelledby="error-404-title">
			<span class="error-404__badge" aria-hidden="true">404</span>
			<p class="error-404__eyebrow"><?php esc_html_e( 'Stranica nije pronađena', 'zaherpilates' ); ?></p>
			<h1 id="error-404-title" class="error-404__title">
				<?php esc_html_e( 'Skrenula si s prostirke.', 'zaherpilates' ); ?>
			</h1>
			<p class="error-404__text">
				<?php esc_html_e( 'Nema brige, i najbolji trening ponekad treba malu korekciju smjera. Vrati se u ritam i odaberi sljedeći korak.', 'zaherpilates' ); ?>
			</p>

			<a class="button error-404__cta" href="<?php echo esc_url( $catalog_url ); ?>">
				<span><?php esc_html_e( 'Natrag na treninge', 'zaherpilates' ); ?></span>
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<path d="M5 12h14M13 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"></path>
				</svg>
			</a>
		</section>

		<section class="error-404__next" aria-label="<?php esc_attr_e( 'Pomoćni linkovi', 'zaherpilates' ); ?>">
			<a class="error-404-card" href="<?php echo esc_url( $catalog_url ); ?>">
				<span class="error-404-card__icon" aria-hidden="true">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
						<polygon points="5 3 19 12 5 21 5 3"></polygon>
					</svg>
				</span>
				<span class="error-404-card__title"><?php esc_html_e( 'Katalog treninga', 'zaherpilates' ); ?></span>
				<span class="error-404-card__text"><?php esc_html_e( 'Pronađi trening koji ti danas najviše odgovara.', 'zaherpilates' ); ?></span>
			</a>

			<a class="error-404-card" href="<?php echo esc_url( $account_url ); ?>">
				<span class="error-404-card__icon" aria-hidden="true">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
						<circle cx="12" cy="7" r="4"></circle>
					</svg>
				</span>
				<span class="error-404-card__title"><?php esc_html_e( 'Moj račun', 'zaherpilates' ); ?></span>
				<span class="error-404-card__text"><?php esc_html_e( 'Pregledaj pretplatu, podatke i postavke profila.', 'zaherpilates' ); ?></span>
			</a>

			<a class="error-404-card" href="<?php echo esc_url( $contact_url ); ?>">
				<span class="error-404-card__icon" aria-hidden="true">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
					</svg>
				</span>
				<span class="error-404-card__title"><?php esc_html_e( 'Pitanje za Ivanu?', 'zaherpilates' ); ?></span>
				<span class="error-404-card__text"><?php esc_html_e( 'Javi se ako trebaš pomoć oko treninga ili pristupa.', 'zaherpilates' ); ?></span>
			</a>
		</section>
	</div>
</section>

<?php
get_footer();
