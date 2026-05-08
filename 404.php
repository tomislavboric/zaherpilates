<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

get_header(); ?>

<section class="error-404">
	<div class="grid-container full">
		<div class="error-404__inner">
			<span class="error-404__code" aria-hidden="true">404</span>
			<h1 class="error-404__title">Stranica nije pronađena</h1>
			<p class="error-404__text">
				Stranica koju tražite možda je premještena, preimenovana ili privremeno nedostupna.
			</p>
			<div class="error-404__actions">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button">
					Povratak na naslovnicu
				</a>
				<a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>" class="button button--hollow">
					Kontaktiraj nas
				</a>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
