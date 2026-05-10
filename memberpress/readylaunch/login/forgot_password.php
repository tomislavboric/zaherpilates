<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$login_url          = function_exists( 'theme_auth_page_url' ) ? theme_auth_page_url() : home_url( '/prijava/' );
$mepr_user_or_email = isset( $mepr_user_or_email ) ? $mepr_user_or_email : '';
?>

<section class="mepr-auth mepr-auth--compact" aria-labelledby="mepr-auth-title">
	<div class="mepr-auth__card">
		<p class="mepr-auth__eyebrow"><?php esc_html_e( 'Reset lozinke', 'foundationpress' ); ?></p>
		<h1 id="mepr-auth-title"><?php esc_html_e( 'Zaboravljena lozinka', 'foundationpress' ); ?></h1>
		<p class="mepr-auth__intro"><?php esc_html_e( 'Upiši e-mail adresu računa i poslat ćemo ti link za postavljanje nove lozinke.', 'foundationpress' ); ?></p>

		<?php if ( ! empty( $errors ) ) : ?>
			<div class="mepr-auth__message mepr-auth__message--error" id="mepr_jump">
				<ul>
					<?php foreach ( $errors as $error ) : ?>
						<li><?php echo MeprAppHelper::wp_kses( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form name="mepr_forgot_password_form" id="mepr_forgot_password_form" class="mepr-auth__form mepro-form" action="" method="post">
			<div class="mp-form-row mepr_forgot_password_input mepr-auth__field">
				<label for="mepr_user_or_email"><?php esc_html_e( 'E-mail adresa', 'foundationpress' ); ?></label>
				<input type="text" name="mepr_user_or_email" id="mepr_user_or_email" value="<?php echo esc_attr( $mepr_user_or_email ); ?>" placeholder="<?php esc_attr_e( 'ana@primjer.hr', 'foundationpress' ); ?>" autocomplete="username" required>
			</div>

			<?php MeprHooks::do_action( 'mepr-forgot-password-form' ); ?>

			<div class="submit mepr-auth__submit">
				<input type="submit" name="wp-submit" id="wp-submit" class="mepr-auth__button mepr-share-button" value="<?php esc_attr_e( 'Pošalji link', 'foundationpress' ); ?>">
				<input type="hidden" name="action" value="forgot_password">
				<input type="hidden" name="mepr_process_forgot_password_form" value="true">
			</div>
		</form>

		<div class="mepr-auth__footer">
			<a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Natrag na prijavu', 'foundationpress' ); ?></a>
		</div>
	</div>

	<aside class="mepr-auth__side" aria-label="<?php esc_attr_e( 'LOOP', 'foundationpress' ); ?>">
		<div class="mepr-auth__quote">
			<?php esc_html_e( 'Jedan klik je dovoljan da ponovno otvoriš svoj prostor za trening.', 'foundationpress' ); ?>
		</div>
	</aside>
</section>
