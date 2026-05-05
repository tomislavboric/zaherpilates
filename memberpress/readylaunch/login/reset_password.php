<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$login_url        = function_exists( 'zaher_auth_page_url' ) ? zaher_auth_page_url() : home_url( '/prijava/' );
$mepr_screenname  = isset( $mepr_screenname ) ? $mepr_screenname : '';
$mepr_key         = isset( $mepr_key ) ? $mepr_key : '';
?>

<section class="mepr-auth mepr-auth--compact" aria-labelledby="mepr-auth-title">
	<div class="mepr-auth__card">
		<p class="mepr-auth__eyebrow"><?php esc_html_e( 'Nova lozinka', 'zaherpilates' ); ?></p>
		<h1 id="mepr-auth-title"><?php esc_html_e( 'Postavi novu lozinku', 'zaherpilates' ); ?></h1>
		<p class="mepr-auth__intro"><?php esc_html_e( 'Odaberi lozinku s najmanje 8 znakova.', 'zaherpilates' ); ?></p>

		<?php if ( ! empty( $errors ) ) : ?>
			<div class="mepr-auth__message mepr-auth__message--error" id="mepr_jump">
				<ul>
					<?php foreach ( $errors as $error ) : ?>
						<li><?php echo MeprAppHelper::wp_kses( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form name="mepr_reset_password_form" id="mepr_reset_password_form" class="mepr-auth__form mepr-form" action="" method="post">
			<div class="mp-form-row mepr_password mepr-auth__field">
				<label for="mepr_user_password"><?php esc_html_e( 'Nova lozinka', 'zaherpilates' ); ?></label>
				<div class="mepr-auth__input-group">
					<input type="password" name="mepr_user_password" id="mepr_user_password" class="mepr-form-input mepr-forgot-password" autocomplete="new-password" minlength="8" required>
					<button type="button" class="mepr-auth__password-toggle" data-password-toggle aria-label="<?php esc_attr_e( 'Prikaži lozinku', 'zaherpilates' ); ?>" aria-pressed="false">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
							<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
							<circle cx="12" cy="12" r="3"></circle>
						</svg>
					</button>
				</div>
			</div>

			<div class="mp-form-row mepr_password_confirm mepr-auth__field">
				<label for="mepr_user_password_confirm"><?php esc_html_e( 'Potvrda lozinke', 'zaherpilates' ); ?></label>
				<div class="mepr-auth__input-group">
					<input type="password" name="mepr_user_password_confirm" id="mepr_user_password_confirm" class="mepr-form-input mepr-forgot-password-confirm" autocomplete="new-password" minlength="8" required>
					<button type="button" class="mepr-auth__password-toggle" data-password-toggle aria-label="<?php esc_attr_e( 'Prikaži lozinku', 'zaherpilates' ); ?>" aria-pressed="false">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
							<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
							<circle cx="12" cy="12" r="3"></circle>
						</svg>
					</button>
				</div>
			</div>

			<?php MeprHooks::do_action( 'mepr-reset-password-after-password-fields' ); ?>

			<div class="submit mepr-auth__submit">
				<input type="submit" name="wp-submit" id="wp-submit" class="mepr-auth__button mepr-share-button" value="<?php esc_attr_e( 'Spremi lozinku', 'zaherpilates' ); ?>">
				<input type="hidden" name="action" value="mepr_process_reset_password_form">
				<input type="hidden" name="mepr_screenname" value="<?php echo esc_attr( $mepr_screenname ); ?>">
				<input type="hidden" name="mepr_key" value="<?php echo esc_attr( $mepr_key ); ?>">
				<input type="hidden" name="mepr_is_login_page" value="true">
			</div>
		</form>

		<div class="mepr-auth__footer">
			<a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Natrag na prijavu', 'zaherpilates' ); ?></a>
		</div>
	</div>
</section>
