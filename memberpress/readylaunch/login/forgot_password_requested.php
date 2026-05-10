<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$login_url   = function_exists( 'theme_auth_page_url' ) ? theme_auth_page_url() : home_url( '/prijava/' );
$reset_error = isset( $_REQUEST['error'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['error'] ) ) : '';
?>

<section class="mepr-auth mepr-auth--compact" aria-labelledby="mepr-auth-title">
	<div class="mepr-auth__card">
		<?php if ( ! empty( $reset_error ) ) : ?>
			<div class="mepr-auth__icon mepr-auth__icon--error" aria-hidden="true">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
					<path d="M12 9v4"></path>
					<path d="M12 17h.01"></path>
					<circle cx="12" cy="12" r="9"></circle>
				</svg>
			</div>
			<p class="mepr-auth__eyebrow"><?php esc_html_e( 'Reset lozinke', 'foundationpress' ); ?></p>
			<h1 id="mepr-auth-title"><?php esc_html_e( 'Lozinka nije resetirana', 'foundationpress' ); ?></h1>
			<div class="mepr-auth__message mepr-auth__message--error">
				<?php echo esc_html( $reset_error ); ?>
			</div>
			<p class="mepr-auth__intro"><?php esc_html_e( 'Pokušaj ponovno ili nam se javi ako se poruka ponavlja.', 'foundationpress' ); ?></p>
		<?php else : ?>
			<div class="mepr-auth__icon" aria-hidden="true">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
					<path d="M20 6L9 17l-5-5"></path>
				</svg>
			</div>
			<p class="mepr-auth__eyebrow"><?php esc_html_e( 'Provjeri e-mail', 'foundationpress' ); ?></p>
			<h1 id="mepr-auth-title"><?php esc_html_e( 'Link je poslan', 'foundationpress' ); ?></h1>
			<p class="mepr-auth__intro"><?php esc_html_e( 'Ako račun postoji, uskoro ćeš dobiti e-mail s linkom za postavljanje nove lozinke.', 'foundationpress' ); ?></p>
		<?php endif; ?>

		<div class="mepr-auth__actions">
			<a class="mepr-auth__button" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Natrag na prijavu', 'foundationpress' ); ?></a>
		</div>
	</div>
</section>
