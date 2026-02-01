<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'MPMLS_MemberPress_Hooks' ) || defined( 'MPMLS_BOOTSTRAPPED' ) ) {
	return;
}

define( 'MPMLS_BOOTSTRAPPED', true );
define( 'MPMLS_VERSION', '1.0.0' );
define( 'MPMLS_PATH', trailingslashit( get_stylesheet_directory() ) . 'inc/custom/mp-mailerlite-sync/' );
define( 'MPMLS_URL', trailingslashit( get_stylesheet_directory_uri() ) . 'inc/custom/mp-mailerlite-sync/' );
define( 'MPMLS_OPTION_KEY', 'mpmls_settings' );

class MPMLS_Logger {
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'mpmls_logs';
	}

	public static function is_enabled() {
		$settings = get_option( MPMLS_OPTION_KEY, array() );
		return ! empty( $settings['logging_enabled'] );
	}

	public static function log( $data ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		global $wpdb;
		$table = self::table_name();

		$wpdb->insert(
			$table,
			array(
				'created_at'    => current_time( 'mysql' ),
				'event'         => isset( $data['event'] ) ? (string) $data['event'] : '',
				'email'         => isset( $data['email'] ) ? (string) $data['email'] : '',
				'wp_user_id'    => isset( $data['wp_user_id'] ) ? (int) $data['wp_user_id'] : 0,
				'membership_id' => isset( $data['membership_id'] ) ? (int) $data['membership_id'] : 0,
				'group_id'      => isset( $data['group_id'] ) ? (string) $data['group_id'] : '',
				'action'        => isset( $data['action'] ) ? (string) $data['action'] : '',
				'success'       => ! empty( $data['success'] ) ? 1 : 0,
				'message'       => isset( $data['message'] ) ? (string) $data['message'] : '',
			),
			array( '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s' )
		);
	}
}

function mpmls_get_setting( $key, $default = '' ) {
	$settings = get_option( MPMLS_OPTION_KEY, array() );
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

function mpmls_activate() {
	global $wpdb;
	$table = MPMLS_Logger::table_name();
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		created_at datetime NOT NULL,
		event varchar(100) NOT NULL,
		email varchar(190) NOT NULL,
		wp_user_id bigint(20) unsigned DEFAULT 0,
		membership_id bigint(20) unsigned DEFAULT 0,
		group_id varchar(64) DEFAULT '',
		action varchar(50) DEFAULT '',
		success tinyint(1) NOT NULL DEFAULT 0,
		message text NULL,
		PRIMARY KEY  (id),
		KEY event (event),
		KEY email (email),
		KEY membership_id (membership_id),
		KEY created_at (created_at)
	) {$charset_collate};";

	dbDelta( $sql );
}

add_action( 'after_switch_theme', 'mpmls_activate' );

require_once MPMLS_PATH . 'includes/class-mailerlite-client.php';
require_once MPMLS_PATH . 'includes/class-mp-hooks.php';
require_once MPMLS_PATH . 'admin/class-admin-settings.php';

add_action( 'after_setup_theme', function () {
	new MPMLS_Admin_Settings();
	$hooks = new MPMLS_MemberPress_Hooks();
	$hooks->register();
} );
