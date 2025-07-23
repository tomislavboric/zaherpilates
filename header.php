<?php
/**
 * The template for displaying the header
 *
 * Displays all of the head element and everything up until the "container" div.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

?>

<!doctype html>
<html class="no-js" <?php language_attributes(); ?> >
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />

		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
		<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined">
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

		<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

		<?php wp_head(); ?>

		<?php // MailerLite Universal ?>
		<script>
				(function(w,d,e,u,f,l,n){w[f]=w[f]||function(){(w[f].q=w[f].q||[])
				.push(arguments);},l=d.createElement(e),l.async=1,l.src=u,
				n=d.getElementsByTagName(e)[0],n.parentNode.insertBefore(l,n);})
				(window,document,'script','https://assets.mailerlite.com/js/universal.js','ml');
				ml('account', '1866');
		</script>
		<?php // End MailerLite Universal ?>

	</head>
	<body <?php body_class(); ?>>

		<div class="site">
			<?php get_template_part( 'template-parts/modules/header' ); ?>

			<?php /* if (!is_singular('programs')) : ?>
				<?php get_template_part( 'template-parts/modules/header' ); ?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/modules/header-programs' ); ?>
			// <?php endif; */ ?>
