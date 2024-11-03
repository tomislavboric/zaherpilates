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
				<?php if ( is_page_template('page-templates/page-loop.php') || is_page_template('page-programs.php') || is_page_template('page-plans.php') ) : ?>
					<img class="header__logo-loop" src="<?php echo get_stylesheet_directory_uri(); ?>/dist/assets/images/loop-logo.svg" alt="LOOP by Zaher Pilates">
				<?php endif; ?>
			</a>
		</div>

		<nav class="header__nav">

			<?php if ( is_page_template( 'page-templates/page-loop.php' ) || is_archive( 'programs' ) || is_singular( 'programs' ) ) : ?>
				<?php foundationpress_top_bar_loop(); ?>
			<?php else : ?>
				<?php foundationpress_top_bar_r(); ?>
			<?php endif; ?>


			<div class="header__account">
				<?php if ( !is_user_logged_in() ) : ?>
					<a class="button button--small" href="<?php echo home_url(); ?>/prijava/">Prijavi se</a>
					<?php /* if ( is_page_template( 'page-templates/page-loop.php' ) || is_archive( 'programs' ) || is_singular( 'programs' ) ) : ?>
					<?php else : ?>
						<?php <a class="button button--small" href="<?php echo home_url(); ?>/pretplate/">Isprobaj besplatno</a> ?>
					<?php endif; */ ?>
				<?php else : ?>
					<div class="header__account-user">
						<?php // echo 'Bok, ' . $current_user->user_login . '!'; ?>
						<a href="<?php echo home_url(); ?>/moj-racun/">
							<?php echo get_avatar( $current_user->user_email, 40 ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</nav>

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
	</div>

</header>
