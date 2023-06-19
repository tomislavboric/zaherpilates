<?php
if (is_front_page()) :
	$sticky = 'header--fixed header--hero';
else :
	$sticky = 'header--sticky';
endif;
?>

<header class="header <?php echo $sticky; ?>">
	<div class="grid-container">
		<div class="header__logo">
			<a href="<?php echo home_url(); ?>">
				<img src="<?php echo get_stylesheet_directory_uri(); ?>/dist/assets/images/logo-zaherpilates.png" alt="Zaher Pilates">
			</a>
		</div>
		<nav class="header__nav">
			<?php foundationpress_top_bar_r(); ?>
		</nav>
		<div class="header__cta">
			<a href="<?php echo home_url(); ?>/programi/" class="button button--small">Isprobaj LOOP</a>
		</div>
	</div>
</header>
