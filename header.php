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

		<!-- <link href="https://vjs.zencdn.net/8.3.0/video-js.css" rel="stylesheet" /> -->

		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>

		<div class="site">
			<?php get_template_part( 'template-parts/modules/header' ); ?>

			<?php /* if (!is_singular('programs')) : ?>
				<?php get_template_part( 'template-parts/modules/header' ); ?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/modules/header-programs' ); ?>
			// <?php endif; */ ?>
