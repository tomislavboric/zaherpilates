<?php
/**
 * The layout for unauthenticated MemberPress pages.
 *
 * @package memberpress-pro-template
 */

$is_auth_page = function_exists( 'zaher_is_memberpress_auth_context' ) && zaher_is_memberpress_auth_context();
$body_classes = 'mepr-pro-template mepr-guest-layout';
$header_class = '';

if ( $is_auth_page ) {
	$body_classes .= ' mepr-auth-surface';
	$header_class  = 'mepr-auth-header';
}

$pricing_url = function_exists( 'zaher_pricing_page_url' ) ? zaher_pricing_page_url() : home_url( '/cjenik/' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class( $body_classes ); ?>>
<?php wp_body_open(); ?>
	<div id="page" class="site guest-layout">
		<header id="masthead" class="site-header <?php echo esc_attr( $header_class ); ?>">
			<?php if ( $is_auth_page ) : ?>
				<a class="mepr-auth-header__back" href="<?php echo esc_url( $pricing_url ); ?>">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<polyline points="15 18 9 12 15 6"></polyline>
					</svg>
					<span><?php esc_html_e( 'Natrag na cjenik', 'zaherpilates' ); ?></span>
				</a>
			<?php endif; ?>

			<div class="site-branding">
				<a href="<?php echo esc_url( home_url() ); ?>">
					<img class="site-branding__logo site-logo" src="<?php echo esc_url_raw( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				</a>
			</div>

			<?php if ( $is_auth_page ) : ?>
				<div class="mepr-auth-header__secure">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
						<rect x="5" y="11" width="14" height="10" rx="2"></rect>
						<path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
					</svg>
					<span><?php esc_html_e( 'Siguran pristup', 'zaherpilates' ); ?></span>
				</div>
			<?php endif; ?>
		</header>

		<main id="primary" class="site-main <?php echo $is_auth_page ? 'mepr-auth-main' : ''; ?>">
			<?php the_content(); ?>

			<?php
			if (
				! $is_auth_page &&
				$mepr_options->login_page_id &&
				( is_active_sidebar( 'mepr_rl_login_footer' ) || is_active_sidebar( 'mepr_rl_global_footer' ) )
			) :
				?>
				<div class="mepro-login-widget">
					<div class="mepro-login-widget-box mepro-boxed">
						<?php if ( is_active_sidebar( 'mepr_rl_login_footer' ) ) : ?>
							<div id="mepr-rl-login-footer-widget" class="mepr-rl-login-footer-widget widget-area" role="complementary">
								<?php dynamic_sidebar( 'mepr_rl_login_footer' ); ?>
							</div>
						<?php endif; ?>

						<?php if ( is_active_sidebar( 'mepr_rl_global_footer' ) ) : ?>
							<div id="mepr-rl-global-footer-widget" class="mepr-rl-global-footer-widget widget-area" role="complementary">
								<?php dynamic_sidebar( 'mepr_rl_global_footer' ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</main>

		<?php wp_footer(); ?>
	</div>
</body>
</html>
