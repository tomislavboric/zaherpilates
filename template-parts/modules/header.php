<?php
if (is_front_page() /* || is_page_template( 'page-templates/page-loop.php' ) */ ) :
	$hero = 'header--hero';
else :
	$hero = '';
endif;

$current_user = wp_get_current_user();
?>

<header class="header <?php echo $hero; ?>">
	<div class="grid-container">

		<div class="header__logo">
			<a href="<?php echo home_url(); ?>">
				<img class="header__logo-zaherpilates" src="<?php echo get_stylesheet_directory_uri(); ?>/dist/assets/images/zaherpilates-logo.png" alt="Zaher Pilates">
				<?php if ( is_page_template('page-templates/page-loop.php') || is_archive('page-programs.php') || is_page_template('page-plans.php') || zaher_is_catalog_page() ) : ?>
					<img class="header__logo-loop" src="<?php echo get_stylesheet_directory_uri(); ?>/dist/assets/images/loop-logo.svg" alt="LOOP by Zaher Pilates">
				<?php endif; ?>
			</a>
		</div>

		<nav class="header__nav">

			<?php if ( is_page_template( 'page-templates/page-loop.php' ) || is_page_template( 'page-templates/page-plans.php' ) || is_archive( 'programs' ) || is_singular( 'programs' ) ) : ?>
				<?php foundationpress_top_bar_loop(); ?>
			<?php else : ?>
				<?php foundationpress_top_bar_r(); ?>
			<?php endif; ?>

		</nav>

		<div class="header__account">
			<?php
			$current_user = wp_get_current_user();
			if ( is_user_logged_in() ) :
				$display_name = $current_user->user_firstname;
				if ( '' === $display_name ) {
					$display_name = $current_user->display_name;
				}

				// Use custom account page (separate from MemberPress)
				$account_url = home_url( '/moj-racun/' );
				$subscription_url = add_query_arg( 'tab', 'subscription', $account_url );
				$payments_url     = add_query_arg( 'tab', 'payments', $account_url );
				$logout_url  = wp_logout_url( home_url() );

				// Only use MeprUtils for logout URL (account page is custom)
				if ( class_exists( 'MeprUtils' ) ) {
					$logout_url = MeprUtils::logout_url();
				}
				?>
				<a class="header__help-link" href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">
					<?php echo zaher_lineicon_svg( 'info' ); ?>
					<span class="show-for-sr">Kontakt</span>
				</a>
				<div class="header__account-user" data-account-menu>
					<button class="header__account-trigger" type="button" aria-expanded="false" aria-haspopup="true">
						<span class="header__account-trigger-name"><?php echo esc_html( $display_name ); ?></span>
						<span class="header__account-avatar">
							<?php echo get_avatar( $current_user->user_email, 36 ); ?>
						</span>
					</button>
					<div class="header__account-dropdown" role="menu" aria-label="<?php esc_attr_e( 'Account menu', 'foundationpress' ); ?>">
						<div class="header__account-header">
							<span class="header__account-name"><?php echo esc_html( $display_name ); ?></span>
							<span class="header__account-email"><?php echo esc_html( $current_user->user_email ); ?></span>
						</div>
						<div class="header__account-links">
							<a class="header__account-link" href="<?php echo esc_url( $account_url ); ?>" role="menuitem">
								<?php echo zaher_lineicon_svg( 'user' ); ?>
								Profil
							</a>
							<a class="header__account-link" href="<?php echo esc_url( $subscription_url ); ?>" role="menuitem">
								<?php echo zaher_lineicon_svg( 'id-card' ); ?>
								Pretplata
							</a>
							<a class="header__account-link" href="<?php echo esc_url( $payments_url ); ?>" role="menuitem">
								<?php echo zaher_lineicon_svg( 'file' ); ?>
								Plaćanja
							</a>
							<a class="header__account-link" href="<?php echo esc_url( $logout_url ); ?>" role="menuitem">
								<?php echo zaher_lineicon_svg( 'exit' ); ?>
								Odjava
							</a>
						</div>
					</div>
				</div>
			<?php else : ?>
				<a class="button button--small" href="<?php echo home_url(); ?>/prijava/">Prijavi se</a>
			<?php endif; ?>
		</div>

		<div class="burger">
			<div class="burger-box">
				<div class="burger-inner">
					<div class="top-bun"></div>
					<div class="bottom-bun"></div>
				</div>
			</div>
    </div>

	</div>

	<div class="header-menu">
			<?php if ( is_page_template( 'page-templates/page-loop.php' ) || is_archive( 'programs' ) || is_singular( 'programs' ) ) : ?>
				<?php foundationpress_top_bar_loop(); ?>
			<?php else : ?>
				<?php foundationpress_top_bar_r(); ?>
			<?php endif; ?>
			<div class="header-menu__divider"></div>
			<div class="header-menu__account">
				<?php if ( is_user_logged_in() ) :
					$menu_account_url = home_url( '/moj-racun/' );
					$menu_logout_url  = wp_logout_url( home_url() );
					if ( class_exists( 'MeprUtils' ) ) {
						$menu_logout_url = MeprUtils::logout_url();
					}
				?>
					<a href="<?php echo esc_url( $menu_account_url ); ?>" class="button button--hollow">Profil</a>
					<a href="<?php echo esc_url( $menu_logout_url ); ?>" class="button button--hollow">Odjava</a>
				<?php else : ?>
					<a href="<?php echo home_url(); ?>/prijava/" class="button button--hollow">Prijavi se</a>
				<?php endif; ?>
			</div>
	</div>

</header>
