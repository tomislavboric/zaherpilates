<?php
/**
 *
 * ACF Web Settings
 *
 */

if( function_exists('acf_add_options_page') ) {

	acf_add_options_page(array(
		'page_title' 	=> 'Web Settings',
		'menu_title'	=> 'Web Settings',
		'menu_slug' 	=> 'web-general-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> false
	));

	/*acf_add_options_sub_page(array(
		'page_title' 	=> 'Blog page settings',
		'menu_title'	=> 'Blog',
		'parent_slug'	=> 'theme-general-settings',
	));
	acf_add_options_sub_page(array(
		'page_title' 	=> 'Theme Footer Settings',
		'menu_title'	=> 'Footer',
		'parent_slug'	=> 'theme-general-settings',
	));*/

}
/*
function my_acf_google_map_api( $api ){

	$api['key'] = 'AIzaSyBtTGEaZUoKCRYg6fAaVy2_PdBoan2ytT4';

	return $api;

}

add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');
*/
