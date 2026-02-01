<?php
/**
 * Handle password change from custom account page.
 */
add_action( 'admin_post_zaher_change_password', 'zaher_handle_password_change' );
function zaher_handle_password_change() {
	if ( ! is_user_logged_in() ) {
		wp_redirect( home_url( '/prijava/' ) );
		exit;
	}

	if ( ! isset( $_POST['zaher_password_nonce'] ) || ! wp_verify_nonce( $_POST['zaher_password_nonce'], 'zaher_change_password' ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=password&password_error=nonce' ) );
		exit;
	}

	$current_password = isset( $_POST['current_password'] ) ? $_POST['current_password'] : '';
	$new_password     = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';
	$confirm_password = isset( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : '';

	$user = wp_get_current_user();

	// Verify current password
	if ( ! wp_check_password( $current_password, $user->user_pass, $user->ID ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=password&password_error=wrong' ) );
		exit;
	}

	// Check if new passwords match
	if ( $new_password !== $confirm_password ) {
		wp_redirect( home_url( '/moj-racun/?tab=password&password_error=mismatch' ) );
		exit;
	}

	// Update password
	wp_set_password( $new_password, $user->ID );

	// Re-login the user
	wp_set_auth_cookie( $user->ID );

	wp_redirect( home_url( '/moj-racun/?tab=password&password_changed=1' ) );
	exit;
}

/**
 * Handle profile updates from custom account page.
 */
add_action( 'admin_post_zaher_update_profile', 'zaher_handle_profile_update' );
function zaher_handle_profile_update() {
	if ( ! is_user_logged_in() ) {
		wp_redirect( home_url( '/prijava/' ) );
		exit;
	}

	if ( ! isset( $_POST['zaher_profile_nonce'] ) || ! wp_verify_nonce( $_POST['zaher_profile_nonce'], 'zaher_update_profile' ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=nonce' ) );
		exit;
	}

	$user = wp_get_current_user();

	$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	$email      = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';

	if ( $email === '' || ! is_email( $email ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=email' ) );
		exit;
	}

	$existing_id = email_exists( $email );
	if ( $existing_id && (int) $existing_id !== (int) $user->ID ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=exists' ) );
		exit;
	}

	$display_name = trim( $first_name . ' ' . $last_name );
	if ( $display_name === '' ) {
		$display_name = $user->display_name;
	}

	$result = wp_update_user(
		array(
			'ID'           => $user->ID,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'user_email'   => $email,
			'display_name' => $display_name,
			'nickname'     => $display_name,
		)
	);

	if ( is_wp_error( $result ) ) {
		wp_redirect( home_url( '/moj-racun/?tab=profile&profile_error=save' ) );
		exit;
	}

	if ( class_exists( 'MeprUser' ) ) {
		$address_fields = array(
			'mepr-address-one'     => isset( $_POST['mepr-address-one'] ) ? $_POST['mepr-address-one'] : '',
			'mepr-address-two'     => isset( $_POST['mepr-address-two'] ) ? $_POST['mepr-address-two'] : '',
			'mepr-address-city'    => isset( $_POST['mepr-address-city'] ) ? $_POST['mepr-address-city'] : '',
			'mepr-address-state'   => isset( $_POST['mepr-address-state'] ) ? $_POST['mepr-address-state'] : '',
			'mepr-address-zip'     => isset( $_POST['mepr-address-zip'] ) ? $_POST['mepr-address-zip'] : '',
			'mepr-address-country' => isset( $_POST['mepr-address-country'] ) ? $_POST['mepr-address-country'] : '',
		);

		$mepr_user = new MeprUser( $user->ID );
		$mepr_user->set_address( $address_fields );
	}

	wp_redirect( home_url( '/moj-racun/?tab=profile&profile_updated=1' ) );
	exit;
}
