<?php
/**
 * Mobile bottom nav for logged-in users (app-like).
 */

if ( ! is_user_logged_in() ) {
	return;
}

$katalog_page = get_page_by_path( 'katalog' );
$katalog_url  = home_url( '/' );

$loop_page = get_page_by_path( 'loop' );
$loop_url  = $loop_page ? get_permalink( $loop_page ) : home_url( '/loop/' );

$account_url = home_url( '/moj-racun/' );

$is_katalog = zaher_is_catalog_page()
	|| is_page_template( 'page-templates/page-catalog.php' )
	|| is_tax( 'catalog' )
	|| is_singular( array( 'programs', 'collection' ) )
	|| is_post_type_archive( 'programs' );

$is_loop = is_page_template( 'page-templates/page-loop.php' ) || is_page( 'loop' );

$is_account = is_page( 'profil' ) || is_page( 'moj-racun' ) || is_page( 'prijava' );

$is_search = is_page_template( 'page-templates/page-catalog.php' ) && isset( $_GET['katalog_search'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<nav class="bottom-nav bottom-nav--members" aria-label="Member navigation" style="display: none;">
	<a class="bottom-nav__item <?php echo $is_katalog ? 'is-active' : ''; ?>" href="<?php echo esc_url( $katalog_url ); ?>">
		<?php echo zaher_lineicon_svg( 'layout', 'bottom-nav__icon' ); ?>
		<span class="bottom-nav__label">Katalog</span>
	</a>

	<?php /*
	<a class="bottom-nav__item <?php echo $is_search ? 'is-active' : ''; ?>" href="<?php echo esc_url( $katalog_url . '#katalog-search' ); ?>">
		<?php echo zaher_lineicon_svg( 'search', 'bottom-nav__icon' ); ?>
		<span class="bottom-nav__label">Search</span>
	</a>
	*/ ?>

	<a class="bottom-nav__item" href="<?php echo esc_url( $katalog_url . '#moji-favoriti' ); ?>">
		<?php echo zaher_lineicon_svg( 'heart', 'bottom-nav__icon' ); ?>
		<span class="bottom-nav__label">Favoriti</span>
	</a>

	<a class="bottom-nav__item <?php echo $is_loop ? 'is-active' : ''; ?>" href="<?php echo esc_url( $loop_url ); ?>">
		<?php echo zaher_lineicon_svg( 'refresh', 'bottom-nav__icon' ); ?>
		<span class="bottom-nav__label">LOOP</span>
	</a>

	<a class="bottom-nav__item <?php echo $is_account ? 'is-active' : ''; ?>" href="<?php echo esc_url( $account_url ); ?>">
		<?php echo zaher_lineicon_svg( 'user', 'bottom-nav__icon' ); ?>
		<span class="bottom-nav__label">Račun</span>
	</a>

	<button class="bottom-nav__item bottom-nav__menu-toggle" type="button" aria-expanded="false" aria-controls="header-menu" aria-label="Otvori izbornik">
		<?php echo zaher_lineicon_svg( 'menu', 'bottom-nav__icon' ); ?>
		<span class="bottom-nav__label">Izbornik</span>
	</button>
</nav>
