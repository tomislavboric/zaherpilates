<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$admin_view          = isset( $atts['admin_view'] ) ? $atts['admin_view'] : false;
$login_url           = isset( $login_url ) ? $login_url : zaher_auth_page_url();
$forgot_password_url = function_exists( 'zaher_auth_action_url' ) ? zaher_auth_action_url( 'forgot_password' ) : $forgot_password_url;
$catalog_url         = function_exists( 'zaher_catalog_page_url' ) ? zaher_catalog_page_url() : home_url( '/katalog/' );
$pricing_url         = function_exists( 'zaher_pricing_page_url' ) ? zaher_pricing_page_url() : home_url( '/cjenik/' );
$account_url         = function_exists( 'zaher_account_page_url' ) ? zaher_account_page_url() : home_url( '/moj-racun/' );
$redirect_to         = isset( $redirect_to ) ? $redirect_to : $catalog_url;
$old_programs_url    = home_url( '/programs/' );

if ( empty( $redirect_to ) || trailingslashit( $redirect_to ) === trailingslashit( $old_programs_url ) ) {
	$redirect_to = $catalog_url;
}

if ( ! empty( $_REQUEST['mepr_process_login_form'] ) && ! empty( $_REQUEST['errors'] ) ) {
	$errors = array_map( 'wp_kses_post', wp_unslash( $_REQUEST['errors'] ) );
}
?>

<section class="mepr-auth" aria-labelledby="mepr-auth-title">
	<div class="mepr-auth__card">
		<?php if ( MeprUtils::is_user_logged_in() && ! $admin_view ) : ?>
			<?php if ( ! isset( $_GET['mepr-unauth-page'] ) && ( ! isset( $_GET['action'] ) || 'mepr_unauthorized' !== $_GET['action'] ) && ! empty( $redirect_to ) ) : ?>
				<script>
					window.location.href = "<?php echo esc_url_raw( urldecode( $redirect_to ) ); ?>";
				</script>
			<?php else : ?>
				<div class="mepr-auth__icon" aria-hidden="true">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
						<path d="M20 6L9 17l-5-5"></path>
					</svg>
				</div>
				<p class="mepr-auth__eyebrow"><?php esc_html_e( 'Aktivan račun', 'zaherpilates' ); ?></p>
				<h1 id="mepr-auth-title"><?php esc_html_e( 'Već si prijavljena', 'zaherpilates' ); ?></h1>
				<p class="mepr-auth__intro"><?php esc_html_e( 'Možeš nastaviti na katalog treninga ili urediti podatke računa.', 'zaherpilates' ); ?></p>
				<div class="mepr-auth__actions">
					<a class="mepr-auth__button" href="<?php echo esc_url( $catalog_url ); ?>"><?php esc_html_e( 'Idi na katalog', 'zaherpilates' ); ?></a>
					<a class="mepr-auth__link" href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Moj račun', 'zaherpilates' ); ?></a>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<p class="mepr-auth__eyebrow"><?php esc_html_e( 'LOOP račun', 'zaherpilates' ); ?></p>
			<h1 id="mepr-auth-title"><?php esc_html_e( 'Prijava', 'zaherpilates' ); ?></h1>
			<p class="mepr-auth__intro"><?php esc_html_e( 'Nastavi tamo gdje si stala i otvori svoj katalog treninga.', 'zaherpilates' ); ?></p>

			<?php if ( ! empty( $errors ) ) : ?>
				<div class="mepr-auth__message mepr-auth__message--error" id="mepr_jump">
					<ul>
						<?php foreach ( $errors as $error ) : ?>
							<li><?php echo MeprAppHelper::wp_kses( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( isset( $unauth->excerpt ) && ! empty( $unauth->excerpt ) ) : ?>
				<div class="mepr-auth__notice">
					<?php echo wp_kses_post( $unauth->excerpt ); ?>
				</div>
			<?php endif; ?>

			<?php if ( isset( $unauth->message ) && ! empty( $unauth->message ) ) : ?>
				<div class="mepr-auth__notice">
					<?php echo wp_kses_post( $unauth->message ); ?>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['action'] ) && 'mepr_unauthorized' === $_GET['action'] && ! empty( $message ) ) : ?>
				<div class="mepr-auth__notice">
					<?php echo wp_kses_post( $message ); ?>
				</div>
			<?php elseif ( ! empty( $message ) ) : ?>
				<?php echo wp_kses_post( $message ); ?>
				<!-- mp-login-form-start -->
			<?php endif; ?>

			<?php MeprHooks::do_action( 'mepr-before-login-form', $atts ?? array() ); ?>

			<form name="mepr_loginform" id="mepr_loginform" class="mepr-auth__form mepro-form" action="<?php echo esc_url( $login_url ); ?>" method="post">
				<div class="mp-form-row mepr_username mepr-auth__field">
					<label for="user_login"><?php esc_html_e( 'E-mail adresa', 'zaherpilates' ); ?></label>
					<input type="text" name="log" placeholder="<?php esc_attr_e( 'ana@primjer.hr', 'zaherpilates' ); ?>" id="user_login" value="<?php echo isset( $_REQUEST['log'] ) ? esc_attr( stripcslashes( wp_unslash( $_REQUEST['log'] ) ) ) : ''; ?>" autocomplete="username" required>
				</div>

				<div class="mp-form-row mepr_password mepr-auth__field">
					<label for="user_pass"><?php esc_html_e( 'Lozinka', 'zaherpilates' ); ?></label>
					<div class="mepr-auth__input-group">
						<input type="password" name="pwd" placeholder="<?php esc_attr_e( 'Unesi lozinku', 'zaherpilates' ); ?>" id="user_pass" value="" autocomplete="current-password" required>
						<button type="button" class="mepr-auth__password-toggle" data-password-toggle aria-label="<?php esc_attr_e( 'Prikaži lozinku', 'zaherpilates' ); ?>" aria-pressed="false">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
								<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
								<circle cx="12" cy="12" r="3"></circle>
							</svg>
						</button>
					</div>
				</div>

				<?php MeprHooks::do_action( 'mepr-login-form-before-submit' ); ?>

				<div class="mp-form-row mepr_remember_me mepr-auth__checkbox">
					<input name="rememberme" type="checkbox" id="rememberme" value="forever" <?php checked( isset( $_REQUEST['rememberme'] ) ); ?>>
					<label for="rememberme"><?php esc_html_e( 'Zapamti me', 'zaherpilates' ); ?></label>
				</div>

				<div class="submit mepr-auth__submit">
					<input type="submit" name="wp-submit" id="wp-submit" class="mepr-auth__button mepr-share-button" value="<?php esc_attr_e( 'Prijavi se', 'zaherpilates' ); ?>">
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
					<input type="hidden" name="mepr_process_login_form" value="true">
					<input type="hidden" name="mepr_is_login_page" value="<?php echo ! empty( $is_login_page ) ? 'true' : 'false'; ?>">
				</div>
			</form>

			<div class="mepr-auth__footer">
				<a href="<?php echo esc_url( $forgot_password_url ); ?>"><?php esc_html_e( 'Zaboravljena lozinka?', 'zaherpilates' ); ?></a>
				<span><?php esc_html_e( 'Nemaš račun?', 'zaherpilates' ); ?> <a href="<?php echo esc_url( $pricing_url ); ?>"><?php esc_html_e( 'Odaberi plan', 'zaherpilates' ); ?></a></span>
			</div>

			<?php MeprHooks::do_action( 'mepr-login-form-after-submit', $atts ?? array() ); ?>
			<!-- mp-login-form-end -->
		<?php endif; ?>
	</div>

	<aside class="mepr-auth__side" aria-label="<?php esc_attr_e( 'LOOP', 'zaherpilates' ); ?>">
		<div class="mepr-auth__quote">
			<?php esc_html_e( 'Žene koje napreduju ne čekaju savršen trenutak. Iskoriste trenutak koji imaju.', 'zaherpilates' ); ?>
		</div>
		<ul class="mepr-auth__benefits">
			<li><?php esc_html_e( '200+ treninga po fazama ciklusa', 'zaherpilates' ); ?></li>
			<li><?php esc_html_e( 'Live događanja i sezonske kategorije', 'zaherpilates' ); ?></li>
			<li><?php esc_html_e( 'Pretplatu možeš otkazati u bilo kojem trenutku', 'zaherpilates' ); ?></li>
		</ul>
	</aside>
</section>
