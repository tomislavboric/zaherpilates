<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$forgot_url = function_exists( 'zaher_auth_action_url' ) ? zaher_auth_action_url( 'forgot_password' ) : MeprOptions::fetch()->forgot_password_url();
?>

<section class="mepr-auth mepr-auth--compact" aria-labelledby="mepr-auth-title">
	<div class="mepr-auth__card">
		<div class="mepr-auth__icon mepr-auth__icon--error" aria-hidden="true">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
				<path d="M12 9v4"></path>
				<path d="M12 17h.01"></path>
				<circle cx="12" cy="12" r="9"></circle>
			</svg>
		</div>
		<p class="mepr-auth__eyebrow"><?php esc_html_e( 'Reset lozinke', 'zaherpilates' ); ?></p>
		<h1 id="mepr-auth-title"><?php esc_html_e( 'Link je istekao', 'zaherpilates' ); ?></h1>
		<p class="mepr-auth__intro"><?php esc_html_e( 'Zatraži novi link za postavljanje lozinke i nastavi s prijavom.', 'zaherpilates' ); ?></p>
		<div class="mepr-auth__actions">
			<a class="mepr-auth__button" href="<?php echo esc_url( $forgot_url ); ?>"><?php esc_html_e( 'Zatraži novi link', 'zaherpilates' ); ?></a>
		</div>
	</div>
</section>
