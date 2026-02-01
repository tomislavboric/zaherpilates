<?php
/**
 * Admin setting: Member redirect URL.
 * Lets you override where logged-in users are redirected when they visit the homepage.
 */
add_action( 'admin_init', 'zaher_register_member_redirect_url_setting' );
function zaher_register_member_redirect_url_setting() {
	register_setting(
		'general',
		'zaher_member_katalog_url',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		)
	);

	add_settings_field(
		'zaher_member_katalog_url',
		'Member redirect URL',
		'zaher_render_member_redirect_url_setting_field',
		'general'
	);
}

function zaher_render_member_redirect_url_setting_field() {
	$value = (string) get_option( 'zaher_member_katalog_url', '' );
	echo '<input type="url" class="regular-text ltr" id="zaher_member_katalog_url" name="zaher_member_katalog_url" value="' . esc_attr( $value ) . '" placeholder="https://localhost:3000/katalog/" />';
	echo '<p class="description">If set, logged-in users visiting the homepage will be redirected to this URL (e.g. <code>https://localhost:3000/katalog/</code>). Leave empty to use the normal Katalog permalink.</p>';
}
