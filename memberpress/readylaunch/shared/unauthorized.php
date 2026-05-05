<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$login_url   = function_exists( 'zaher_auth_page_url' ) ? zaher_auth_page_url() : $mepr_options->login_page_url();
$pricing_url = function_exists( 'zaher_pricing_page_url' ) ? zaher_pricing_page_url() : home_url( '/cjenik/' );
?>

<section class="mepr-auth mepr-auth--compact" aria-labelledby="mepr-auth-title">
	<div class="mepr-auth__card">
		<div class="mepr-auth__icon mepr-auth__icon--error" aria-hidden="true">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
				<rect x="5" y="11" width="14" height="10" rx="2"></rect>
				<path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
			</svg>
		</div>
		<p class="mepr-auth__eyebrow"><?php esc_html_e( 'Privatan sadržaj', 'zaherpilates' ); ?></p>
		<h1 id="mepr-auth-title"><?php esc_html_e( 'Prijavi se za pristup', 'zaherpilates' ); ?></h1>
		<p class="mepr-auth__intro"><?php esc_html_e( 'Ovaj sadržaj je dostupan aktivnim članicama LOOP-a.', 'zaherpilates' ); ?></p>
		<div class="mepr-auth__actions">
			<a class="mepr-auth__button" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Prijavi se', 'zaherpilates' ); ?></a>
			<a class="mepr-auth__link" href="<?php echo esc_url( $pricing_url ); ?>"><?php esc_html_e( 'Pogledaj planove', 'zaherpilates' ); ?></a>
		</div>
	</div>
</section>
