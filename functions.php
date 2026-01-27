<?php
/**
 * Author: Ole Fredrik Lie
 * URL: http://olefredrik.com
 *
 * FoundationPress functions and definitions
 *
 * Set up the theme and provides some helper functions, which are used in the
 * theme as custom template tags. Others are attached to action and filter
 * hooks in WordPress to change core functionality.
 *
 * @link https://codex.wordpress.org/Theme_Development
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

 define('THEME_DIR', get_stylesheet_directory() . '/');
 define('THEME_URI', get_stylesheet_directory_uri() . '/');

 require THEME_DIR . 'inc/init.php';

/**
 * Member Catalog Router
 *
 * Handles URL routing for logged-in members:
 * - /katalog redirects to / (members always see "/" in browser)
 * - / serves catalog content for logged-in users
 * - Non-logged-in visitors see marketing homepage at / and catalog at /katalog
 *
 * @since 1.0.0
 */
class Zaher_Member_Catalog_Router {

	/**
	 * Whether we're serving catalog at home for a member.
	 *
	 * @var bool
	 */
	private static $is_home_as_catalog = false;

	/**
	 * The katalog page ID when serving at home.
	 *
	 * @var int|null
	 */
	private static $catalog_page_id = null;

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'redirect_katalog_to_home' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'setup_home_as_catalog' ), 2 );
		add_filter( 'template_include', array( __CLASS__, 'load_catalog_template' ), 99 );
	}

	/**
	 * Check if request should be processed.
	 *
	 * @return bool
	 */
	private static function should_process() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}

		// Allow bypass for debugging (e.g. /?no_member_redirect=1).
		if ( isset( $_GET['no_member_redirect'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		return is_user_logged_in();
	}

	/**
	 * Redirect logged-in users from /katalog to /.
	 */
	public static function redirect_katalog_to_home() {
		if ( ! self::should_process() ) {
			return;
		}

		if ( ! is_page( 'katalog' ) ) {
			return;
		}

		wp_safe_redirect( home_url( '/' ), 302 );
		exit;
	}

	/**
	 * Set up catalog content at / for logged-in users.
	 */
	public static function setup_home_as_catalog() {
		if ( ! self::should_process() ) {
			return;
		}

		if ( ! is_front_page() && ! is_home() ) {
			return;
		}

		$katalog_page = get_page_by_path( 'katalog' );
		if ( ! $katalog_page ) {
			return;
		}

		self::$is_home_as_catalog = true;
		self::$catalog_page_id    = $katalog_page->ID;
	}

	/**
	 * Load catalog template when serving catalog at home.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public static function load_catalog_template( $template ) {
		if ( ! self::$is_home_as_catalog ) {
			return $template;
		}

		// Reset the main query to load the katalog page.
		global $wp_query, $post;

		$wp_query = new WP_Query( array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			'page_id'     => self::$catalog_page_id,
			'post_type'   => 'page',
			'post_status' => 'publish',
		) );

		// Set up global $post for template tags.
		if ( $wp_query->have_posts() ) {
			$post = $wp_query->post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );
		}

		$catalog_template = locate_template( 'page-templates/page-catalog.php' );

		return $catalog_template ? $catalog_template : $template;
	}

	/**
	 * Check if we're displaying catalog content (at /katalog or / for members).
	 *
	 * @return bool
	 */
	public static function is_catalog_page() {
		return is_page( 'katalog' ) || self::$is_home_as_catalog;
	}
}

Zaher_Member_Catalog_Router::init();

/**
 * Helper function: Check if displaying catalog content.
 *
 * @return bool
 */
function zaher_is_catalog_page() {
	return Zaher_Member_Catalog_Router::is_catalog_page();
}

/**
 * Parse the "Video length" ACF field into minutes when possible.
 * Supports formats like: "15", "15 min", "15min", "15:00", "00:15:00".
 */
function zaher_parse_video_length_minutes( $value ) {
	$value = trim( (string) $value );
	if ( $value === '' ) {
		return null;
	}

	// HH:MM:SS or MM:SS
	if ( preg_match( '/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m ) ) {
		$h = 0;
		$min = 0;
		$sec = 0;
		if ( isset( $m[3] ) && $m[3] !== '' ) {
			// HH:MM:SS
			$h   = (int) $m[1];
			$min = (int) $m[2];
			$sec = (int) $m[3];
		} else {
			// MM:SS
			$min = (int) $m[1];
			$sec = (int) $m[2];
		}
		$total = ( $h * 60 ) + $min + ( $sec >= 30 ? 1 : 0 );
		return $total > 0 ? $total : null;
	}

	// "15", "15 min", "15min"
	if ( preg_match( '/\b(\d{1,3})\b/', $value, $m ) ) {
		$min = (int) $m[1];
		return $min > 0 ? $min : null;
	}

	return null;
}

/**
 * Render a Lineicons SVG icon.
 *
 * @param string $name Icon key.
 * @param string $class Additional classes for the SVG.
 * @return string
 */
function zaher_lineicon_svg( $name, $class = '' ) {
	static $icons = null;

	if ( null === $icons ) {
		$icons = array(
			'play'         => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M19.4357 13.9174C20.8659 13.0392 20.8659 10.9608 19.4357 10.0826L9.55234 4.01389C8.05317 3.09335 6.125 4.17205 6.125 5.93128L6.125 18.0688C6.125 19.828 8.05317 20.9067 9.55234 19.9861L19.4357 13.9174ZM18.6508 11.3609C19.1276 11.6536 19.1276 12.3464 18.6508 12.6391L8.76745 18.7079C8.26772 19.0147 7.625 18.6552 7.625 18.0688L7.625 5.93128C7.625 5.34487 8.26772 4.9853 8.76745 5.29215L18.6508 11.3609Z" fill="currentColor"/>
</svg>
SVG,
			'lock'         => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12.75 15.5C12.75 15.0858 12.4142 14.75 12 14.75C11.5858 14.75 11.25 15.0858 11.25 15.5V17.5C11.25 17.9142 11.5858 18.25 12 18.25C12.4142 18.25 12.75 17.9142 12.75 17.5V15.5Z" fill="currentColor"/>
<path d="M12 1.25C9.37665 1.25 7.25 3.37665 7.25 6V8.69562C5.57125 10.071 4.5 12.1604 4.5 14.5C4.5 18.6421 7.85786 22 12 22C16.1421 22 19.5 18.6421 19.5 14.5C19.5 12.1604 18.4288 10.071 16.75 8.69562V6C16.75 3.37665 14.6234 1.25 12 1.25ZM12 7C10.8356 7 9.73325 7.26533 8.75 7.73883V6C8.75 4.20507 10.2051 2.75 12 2.75C13.7949 2.75 15.25 4.20507 15.25 6V7.73883C14.2667 7.26533 13.1644 7 12 7ZM12 8.5C15.3137 8.5 18 11.1863 18 14.5C18 17.8137 15.3137 20.5 12 20.5C8.68629 20.5 6 17.8137 6 14.5C6 11.1863 8.68629 8.5 12 8.5Z" fill="currentColor"/>
</svg>
SVG,
			'eye'          => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 5.25C7.35183 5.25 3.55292 8.01131 2 12C3.55292 15.9887 7.35183 18.75 12 18.75C16.6482 18.75 20.4471 15.9887 22 12C20.4471 8.01131 16.6482 5.25 12 5.25ZM12 17.25C8.25899 17.25 5.06749 15.2132 3.65538 12C5.06749 8.78684 8.25899 6.75 12 6.75C15.741 6.75 18.9325 8.78684 20.3446 12C18.9325 15.2132 15.741 17.25 12 17.25ZM12 8.75C10.2051 8.75 8.75 10.2051 8.75 12C8.75 13.7949 10.2051 15.25 12 15.25C13.7949 15.25 15.25 13.7949 15.25 12C15.25 10.2051 13.7949 8.75 12 8.75ZM12 13.75C11.0335 13.75 10.25 12.9665 10.25 12C10.25 11.0335 11.0335 10.25 12 10.25C12.9665 10.25 13.75 11.0335 13.75 12C13.75 12.9665 12.9665 13.75 12 13.75Z" fill="currentColor"/>
</svg>
SVG,
			'eye-off'      => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 5.25C7.35183 5.25 3.55292 8.01131 2 12C3.55292 15.9887 7.35183 18.75 12 18.75C16.6482 18.75 20.4471 15.9887 22 12C20.4471 8.01131 16.6482 5.25 12 5.25ZM12 17.25C8.25899 17.25 5.06749 15.2132 3.65538 12C5.06749 8.78684 8.25899 6.75 12 6.75C15.741 6.75 18.9325 8.78684 20.3446 12C18.9325 15.2132 15.741 17.25 12 17.25ZM12 8.75C10.2051 8.75 8.75 10.2051 8.75 12C8.75 13.7949 10.2051 15.25 12 15.25C13.7949 15.25 15.25 13.7949 15.25 12C15.25 10.2051 13.7949 8.75 12 8.75ZM12 13.75C11.0335 13.75 10.25 12.9665 10.25 12C10.25 11.0335 11.0335 10.25 12 10.25C12.9665 10.25 13.75 11.0335 13.75 12C13.75 12.9665 12.9665 13.75 12 13.75Z" fill="currentColor"/>
<path d="M4.5 5.5L19.5 18.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
</svg>
SVG,
			'download'     => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12.4239 16.75C12.2079 16.75 12.0132 16.6587 11.8763 16.5126L7.26675 11.9059C6.97376 11.6131 6.97361 11.1382 7.26641 10.8452C7.55921 10.5523 8.03408 10.5521 8.32707 10.8449L11.6739 14.1896L11.6739 4C11.6739 3.58579 12.0096 3.25 12.4239 3.25C12.8381 3.25 13.1739 3.58579 13.1739 4L13.1739 14.1854L16.5168 10.8449C16.8098 10.5521 17.2846 10.5523 17.5774 10.8453C17.8702 11.1383 17.87 11.6131 17.5771 11.9059L13.0021 16.4776C12.8646 16.644 12.6566 16.75 12.4239 16.75Z" fill="currentColor"/>
<path d="M5.17188 16C5.17188 15.5858 4.83609 15.25 4.42188 15.25C4.00766 15.25 3.67188 15.5858 3.67188 16V18.5C3.67188 19.7426 4.67923 20.75 5.92188 20.75H18.9227C20.1654 20.75 21.1727 19.7426 21.1727 18.5V16C21.1727 15.5858 20.837 15.25 20.4227 15.25C20.0085 15.25 19.6727 15.5858 19.6727 16V18.5C19.6727 18.9142 19.337 19.25 18.9227 19.25H5.92188C5.50766 19.25 5.17188 18.9142 5.17188 18.5V16Z" fill="currentColor"/>
</svg>
SVG,
			'file'         => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M16.8923 16.7332V4.25C16.8923 3.00736 15.885 2 14.6423 2H10.6929C10.0959 2 9.52341 2.23725 9.10142 2.65951L4.75226 7.01138C4.33061 7.4333 4.09375 8.00538 4.09375 8.60187V16.7332C4.09375 17.9759 5.10111 18.9832 6.34375 18.9832H14.6423C15.885 18.9832 16.8923 17.9759 16.8923 16.7332ZM14.6423 17.4832H6.34375C5.92954 17.4832 5.59375 17.1475 5.59375 16.7332V8.73129H8.57486C9.81813 8.73129 10.8257 7.72296 10.8249 6.47969L10.8227 3.5H14.6423C15.0565 3.5 15.3923 3.83579 15.3923 4.25V16.7332C15.3923 17.1475 15.0566 17.4832 14.6423 17.4832ZM6.65314 7.23129L9.32349 4.55928L9.32486 6.48076C9.32516 6.89518 8.98928 7.23129 8.57486 7.23129H6.65314Z" fill="currentColor"/>
<path d="M18.4065 5.68442C18.4065 5.27021 18.7423 4.93442 19.1565 4.93442C19.5707 4.93442 19.9065 5.27021 19.9065 5.68442V17.2514C19.9065 19.8747 17.7799 22.0014 15.1565 22.0014H7.79765C7.38344 22.0014 7.04765 21.6656 7.04765 21.2514C7.04765 20.8371 7.38344 20.5014 7.79765 20.5014H15.1565C16.9514 20.5014 18.4065 19.0463 18.4065 17.2514V5.68442Z" fill="currentColor"/>
</svg>
SVG,
			'arrow-left'   => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M3.57813 12.4981C3.5777 12.6905 3.65086 12.8831 3.79761 13.0299L9.7936 19.0301C10.0864 19.3231 10.5613 19.3233 10.8543 19.0305C11.1473 18.7377 11.1474 18.2629 10.8546 17.9699L6.13418 13.2461L20.3295 13.2461C20.7437 13.2461 21.0795 12.9103 21.0795 12.4961C21.0795 12.0819 20.7437 11.7461 20.3295 11.7461L6.14168 11.7461L10.8546 7.03016C11.1474 6.73718 11.1473 6.2623 10.8543 5.9695C10.5613 5.6767 10.0864 5.67685 9.79362 5.96984L3.84392 11.9233C3.68134 12.0609 3.57812 12.2664 3.57812 12.4961L3.57813 12.4981Z" fill="currentColor"/>
</svg>
SVG,
			'arrow-right'  => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21.0791 12.519C21.0744 12.7044 21.0013 12.8884 20.8599 13.0299L14.8639 19.0301C14.5711 19.3231 14.0962 19.3233 13.8032 19.0305C13.5103 18.7377 13.5101 18.2629 13.8029 17.9699L18.5233 13.2461L4.32813 13.2461C3.91391 13.2461 3.57813 12.9103 3.57812 12.4961C3.57812 12.0819 3.91391 11.7461 4.32812 11.7461L18.5158 11.7461L13.8029 7.03016C13.5101 6.73718 13.5102 6.2623 13.8032 5.9695C14.0962 5.6767 14.5711 5.67685 14.8639 5.96984L20.813 11.9228C20.976 12.0603 21.0795 12.2661 21.0795 12.4961C21.0795 12.5038 21.0794 12.5114 21.0791 12.519Z" fill="currentColor"/>
</svg>
SVG,
			'user'         => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M16.4337 6.35C16.4337 8.74 14.4937 10.69 12.0937 10.69L12.0837 10.68C9.69365 10.68 7.74365 8.73 7.74365 6.34C7.74365 3.95 9.70365 2 12.0937 2C14.4837 2 16.4337 3.96 16.4337 6.35ZM14.9337 6.34C14.9337 4.78 13.6637 3.5 12.0937 3.5C10.5337 3.5 9.25365 4.78 9.25365 6.34C9.25365 7.9 10.5337 9.18 12.0937 9.18C13.6537 9.18 14.9337 7.9 14.9337 6.34Z" fill="currentColor"/>
<path d="M12.0235 12.1895C14.6935 12.1895 16.7835 12.9395 18.2335 14.4195V14.4095C20.2801 16.4956 20.2739 19.2563 20.2735 19.4344L20.2735 19.4395C20.2635 19.8495 19.9335 20.1795 19.5235 20.1795H19.5135C19.0935 20.1695 18.7735 19.8295 18.7735 19.4195C18.7735 19.3695 18.7735 17.0895 17.1535 15.4495C15.9935 14.2795 14.2635 13.6795 12.0235 13.6795C9.78346 13.6795 8.05346 14.2795 6.89346 15.4495C5.27346 17.0995 5.27346 19.3995 5.27346 19.4195C5.27346 19.8295 4.94346 20.1795 4.53346 20.1795C4.17346 20.1995 3.77346 19.8595 3.77346 19.4495L3.77345 19.4448C3.77305 19.2771 3.76646 16.506 5.81346 14.4195C7.26346 12.9395 9.35346 12.1895 12.0235 12.1895Z" fill="currentColor"/>
</svg>
SVG,
			'id-card'      => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M6.83691 9.8614C6.83691 8.96875 7.56055 8.24512 8.45319 8.24512C9.34584 8.24512 10.0695 8.96875 10.0695 9.8614C10.0695 10.754 9.34584 11.4777 8.45319 11.4777C7.56055 11.4777 6.83691 10.754 6.83691 9.8614Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M7.23935 12.0869C6.00313 12.0869 5.00098 13.0891 5.00098 14.3253V15.2555C5.00098 15.6697 5.33676 16.0055 5.75098 16.0055H11.156C11.5702 16.0055 11.906 15.6697 11.906 15.2555V14.3253C11.906 13.0891 10.9039 12.0869 9.66766 12.0869H7.23935ZM6.50098 14.3253C6.50098 13.9175 6.83156 13.5869 7.23935 13.5869H9.66766C10.0754 13.5869 10.406 13.9175 10.406 14.3253V14.5055H6.50098V14.3253Z" fill="currentColor"/>
<path d="M19.0004 10.501C19.0004 10.9152 18.6646 11.251 18.2504 11.251H14.1504C13.7362 11.251 13.4004 10.9152 13.4004 10.501C13.4004 10.0868 13.7362 9.75098 14.1504 9.75098H18.2504C18.6646 9.75098 19.0004 10.0868 19.0004 10.501Z" fill="currentColor"/>
<path d="M16.1508 14.251C16.565 14.251 16.9008 13.9152 16.9008 13.501C16.9008 13.0868 16.565 12.751 16.1508 12.751H14.1508C13.7366 12.751 13.4008 13.0868 13.4008 13.501C13.4008 13.9152 13.7366 14.251 14.1508 14.251H16.1508Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M4.25 4.50098C3.00736 4.50098 2 5.50834 2 6.75098V17.251C2 18.4936 3.00736 19.501 4.25 19.501H19.75C20.9926 19.501 22 18.4936 22 17.251V6.75098C22 5.50834 20.9926 4.50098 19.75 4.50098H4.25ZM3.5 6.75098C3.5 6.33676 3.83579 6.00098 4.25 6.00098H19.75C20.1642 6.00098 20.5 6.33676 20.5 6.75098V17.251C20.5 17.6652 20.1642 18.001 19.75 18.001H4.25C3.83579 18.001 3.5 17.6652 3.5 17.251V6.75098Z" fill="currentColor"/>
</svg>
SVG,
			'exit'         => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M11.5781 2.5C10.3355 2.5 9.32812 3.50736 9.32812 4.75V6.6285C9.44877 6.70925 9.56333 6.80292 9.66985 6.90952L10.8281 8.06853V4.75C10.8281 4.33579 11.1639 4 11.5781 4H17.5781C17.9923 4 18.3281 4.33579 18.3281 4.75V20.25C18.3281 20.6642 17.9923 21 17.5781 21H11.5781C11.1639 21 10.8281 20.6642 10.8281 20.25V16.9314L9.6699 18.0904C9.56336 18.197 9.44879 18.2907 9.32812 18.3715V20.25C9.32812 21.4926 10.3355 22.5 11.5781 22.5H17.5781C18.8208 22.5 19.8281 21.4926 19.8281 20.25V4.75C19.8281 3.50736 18.8208 2.5 17.5781 2.5H11.5781Z" fill="currentColor"/>
<path d="M3.57812 12.5C3.57812 12.7259 3.67796 12.9284 3.83591 13.0659L7.79738 17.0301C8.09017 17.3231 8.56504 17.3233 8.85804 17.0305C9.15104 16.7377 9.1512 16.2629 8.85841 15.9699L6.14046 13.25L12.0781 13.25C12.4923 13.25 12.8281 12.9142 12.8281 12.5C12.8281 12.0858 12.4923 11.75 12.0781 11.75L6.14028 11.75L8.85839 9.03016C9.15119 8.73718 9.15104 8.2623 8.85806 7.9695C8.56507 7.6767 8.0902 7.67685 7.7974 7.96984L3.83388 11.9359C3.67711 12.0733 3.57812 12.2751 3.57812 12.5Z" fill="currentColor"/>
</svg>
SVG,
			'info'         => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12.3144 6.18984C12.7562 6.18984 13.1144 5.83167 13.1144 5.38984C13.1144 4.94802 12.7562 4.58984 12.3144 4.58984C11.8726 4.58984 11.5134 4.94802 11.5134 5.38984C11.5134 5.83167 11.8716 6.18984 12.3134 6.18984H12.3144Z" fill="currentColor"/>
<path d="M11.5625 18.8896C11.5625 19.3039 11.8983 19.6396 12.3125 19.6396C12.7267 19.6396 13.0625 19.3039 13.0625 18.8896L13.0625 8.39014C13.0625 7.97592 12.7267 7.64014 12.3125 7.64014L10.8125 7.64014C10.3983 7.64014 10.0625 7.97592 10.0625 8.39014C10.0625 8.80435 10.3983 9.14014 10.8125 9.14014H11.5625L11.5625 18.8896Z" fill="currentColor"/>
</svg>
SVG,
			'search'       => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M11.25 2.75C6.14154 2.75 2 6.89029 2 11.998C2 17.1056 6.14154 21.2459 11.25 21.2459C13.5335 21.2459 15.6238 20.4187 17.2373 19.0475L20.7182 22.5287C21.011 22.8216 21.4859 22.8217 21.7788 22.5288C22.0717 22.2359 22.0718 21.761 21.7789 21.4681L18.2983 17.9872C19.6714 16.3736 20.5 14.2826 20.5 11.998C20.5 6.89029 16.3585 2.75 11.25 2.75ZM3.5 11.998C3.5 7.71905 6.96962 4.25 11.25 4.25C15.5304 4.25 19 7.71905 19 11.998C19 16.2769 15.5304 19.7459 11.25 19.7459C6.96962 19.7459 3.5 16.2769 3.5 11.998Z" fill="currentColor"/>
</svg>
SVG,
			'check-circle' => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M15.5071 10.5245C15.8 10.2316 15.8 9.75674 15.5071 9.46384C15.2142 9.17095 14.7393 9.17095 14.4464 9.46384L10.9649 12.9454L9.55359 11.5341C9.2607 11.2412 8.78582 11.2412 8.49293 11.5341C8.20004 11.827 8.20004 12.3019 8.49294 12.5947L10.4346 14.5364C10.7275 14.8293 11.2023 14.8292 11.4952 14.5364L15.5071 10.5245Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM3.5 12C3.5 7.30558 7.30558 3.5 12 3.5C16.6944 3.5 20.5 7.30558 20.5 12C20.5 16.6944 16.6944 20.5 12 20.5C7.30558 20.5 3.5 16.6944 3.5 12Z" fill="currentColor"/>
</svg>
SVG,
			'layout'       => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M5.52344 3.25C4.2808 3.25 3.27344 4.25736 3.27344 5.5V18.5C3.27344 19.7426 4.2808 20.75 5.52344 20.75H18.5234C19.7661 20.75 20.7734 19.7426 20.7734 18.5V5.5C20.7734 4.25736 19.7661 3.25 18.5234 3.25H5.52344ZM4.77344 5.5C4.77344 5.08579 5.10922 4.75 5.52344 4.75H18.5234C18.9377 4.75 19.2734 5.08579 19.2734 5.5V8.58301L4.77344 8.58301V5.5ZM4.77344 10.083H8.60645L8.60645 19.25H5.52344C5.10922 19.25 4.77344 18.9142 4.77344 18.5V10.083ZM10.1064 10.083L19.2734 10.083V13.916H10.1064V10.083ZM10.1064 15.416H19.2734V18.5C19.2734 18.9142 18.9377 19.25 18.5234 19.25H10.1064V15.416Z" fill="currentColor"/>
</svg>
SVG,
			'heart'        => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M11.8227 4.77124L12 4.94862L12.1773 4.77135C14.4244 2.52427 18.0676 2.52427 20.3147 4.77134C22.5618 7.01842 22.5618 10.6616 20.3147 12.9087L13.591 19.6324C12.7123 20.5111 11.2877 20.5111 10.409 19.6324L3.6853 12.9086C1.43823 10.6615 1.43823 7.01831 3.6853 4.77124C5.93237 2.52417 9.5756 2.52417 11.8227 4.77124ZM10.762 5.8319C9.10073 4.17062 6.40725 4.17062 4.74596 5.8319C3.08468 7.49319 3.08468 10.1867 4.74596 11.848L11.4697 18.5718C11.7625 18.8647 12.2374 18.8647 12.5303 18.5718L19.254 11.8481C20.9153 10.1868 20.9153 7.49329 19.254 5.83201C17.5927 4.17072 14.8993 4.17072 13.238 5.83201L12.5304 6.53961C12.3897 6.68026 12.199 6.75928 12 6.75928C11.8011 6.75928 11.6104 6.68026 11.4697 6.53961L10.762 5.8319Z" fill="currentColor"/>
</svg>
SVG,
			'refresh'      => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M3.13644 9.54175C3.02923 9.94185 3.26667 10.3531 3.66676 10.4603C4.06687 10.5675 4.47812 10.3301 4.58533 9.92998C5.04109 8.22904 6.04538 6.72602 7.44243 5.65403C8.83948 4.58203 10.5512 4.00098 12.3122 4.00098C14.0731 4.00098 15.7848 4.58203 17.1819 5.65403C18.3999 6.58866 19.3194 7.85095 19.8371 9.28639L18.162 8.34314C17.801 8.1399 17.3437 8.26774 17.1405 8.62867C16.9372 8.98959 17.0651 9.44694 17.426 9.65017L20.5067 11.3849C20.68 11.4825 20.885 11.5072 21.0766 11.4537C21.2682 11.4001 21.4306 11.2727 21.5282 11.0993L23.2629 8.01828C23.4661 7.65734 23.3382 7.2 22.9773 6.99679C22.6163 6.79358 22.159 6.92145 21.9558 7.28239L21.195 8.63372C20.5715 6.98861 19.5007 5.54258 18.095 4.464C16.436 3.19099 14.4033 2.50098 12.3122 2.50098C10.221 2.50098 8.1883 3.19099 6.52928 4.464C4.87027 5.737 3.67766 7.52186 3.13644 9.54175Z" fill="currentColor"/>
<path d="M21.4906 14.4582C21.5978 14.0581 21.3604 13.6469 20.9603 13.5397C20.5602 13.4325 20.1489 13.6699 20.0417 14.07C19.5859 15.7709 18.5816 17.274 17.1846 18.346C15.7875 19.418 14.0758 19.999 12.3149 19.999C10.5539 19.999 8.84219 19.418 7.44514 18.346C6.2292 17.4129 5.31079 16.1534 4.79261 14.721L6.45529 15.6573C6.81622 15.8605 7.27356 15.7327 7.47679 15.3718C7.68003 15.0108 7.55219 14.5535 7.19127 14.3502L4.11056 12.6155C3.93723 12.5179 3.73222 12.4932 3.54065 12.5467C3.34907 12.6003 3.18662 12.7278 3.08903 12.9011L1.3544 15.9821C1.15119 16.3431 1.27906 16.8004 1.64 17.0036C2.00094 17.2068 2.45828 17.079 2.66149 16.718L3.42822 15.3562C4.05115 17.0054 5.12348 18.4552 6.532 19.536C8.19102 20.809 10.2237 21.499 12.3149 21.499C14.406 21.499 16.4387 20.809 18.0977 19.536C19.7568 18.263 20.9494 16.4781 21.4906 14.4582Z" fill="currentColor"/>
</svg>
SVG,
			'menu'         => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M3.5625 6C3.5625 5.58579 3.89829 5.25 4.3125 5.25H20.3125C20.7267 5.25 21.0625 5.58579 21.0625 6C21.0625 6.41421 20.7267 6.75 20.3125 6.75L4.3125 6.75C3.89829 6.75 3.5625 6.41422 3.5625 6Z" fill="currentColor"/>
<path d="M3.5625 18C3.5625 17.5858 3.89829 17.25 4.3125 17.25L20.3125 17.25C20.7267 17.25 21.0625 17.5858 21.0625 18C21.0625 18.4142 20.7267 18.75 20.3125 18.75L4.3125 18.75C3.89829 18.75 3.5625 18.4142 3.5625 18Z" fill="currentColor"/>
<path d="M4.3125 11.25C3.89829 11.25 3.5625 11.5858 3.5625 12C3.5625 12.4142 3.89829 12.75 4.3125 12.75L20.3125 12.75C20.7267 12.75 21.0625 12.4142 21.0625 12C21.0625 11.5858 20.7267 11.25 20.3125 11.25L4.3125 11.25Z" fill="currentColor"/>
</svg>
SVG,
			'xmark'        => <<<SVG
<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M6.21967 7.28033C5.92678 6.98744 5.92678 6.51256 6.21967 6.21967C6.51256 5.92678 6.98744 5.92678 7.28033 6.21967L11.999 10.9384L16.7176 6.2198C17.0105 5.92691 17.4854 5.92691 17.7782 6.2198C18.0711 6.51269 18.0711 6.98757 17.7782 7.28046L13.0597 11.999L17.7782 16.7176C18.0711 17.0105 18.0711 17.4854 17.7782 17.7782C17.4854 18.0711 17.0105 18.0711 16.7176 17.7782L11.999 13.0597L7.28033 17.7784C6.98744 18.0713 6.51256 18.0713 6.21967 17.7784C5.92678 17.4855 5.92678 17.0106 6.21967 16.7177L10.9384 11.999L6.21967 7.28033Z" fill="currentColor"/>
</svg>
SVG,
		);
	}

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$classes = trim( 'lni-icon ' . $class );
	$svg     = $icons[ $name ];

	return preg_replace(
		'/^<svg\\b/',
		'<svg class="' . esc_attr( $classes ) . '" aria-hidden="true" focusable="false"',
		$svg,
		1
	);
}

/**
 * Keep a numeric minutes meta in sync for program posts.
 */
add_action( 'save_post_programs', 'zaher_sync_program_minutes_meta', 20, 2 );
function zaher_sync_program_minutes_meta( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}

	if ( ! $post || $post->post_type !== 'programs' ) {
		return;
	}

	$raw     = function_exists( 'get_field' ) ? get_field( 'video_length', $post_id ) : get_post_meta( $post_id, 'video_length', true );
	$minutes = zaher_parse_video_length_minutes( $raw );

	if ( $minutes === null ) {
		delete_post_meta( $post_id, 'zaher_video_length_minutes' );
	} else {
		update_post_meta( $post_id, 'zaher_video_length_minutes', (int) $minutes );
	}
}

/**
 * Check if the current user has access to a given program (based on MemberPress capabilities + ACF field).
 * If no subscription is set on a program, it is considered accessible.
 */
function zaher_user_has_program_access( $program_id ) {
	if ( current_user_can( 'administrator' ) ) {
		return true;
	}

	if ( ! function_exists( 'get_field' ) ) {
		return is_user_logged_in();
	}

	$subscription_type = get_field( 'subscription_type', $program_id );

	// If no subscription type set, program is accessible to all.
	if ( empty( $subscription_type ) ) {
		return true;
	}

	$membership_map = [
		'mjesecna'    => 387,
		'tromjesecna' => 111,
		'polugodisnja' => 148,
	];

	foreach ( (array) $subscription_type as $type ) {
		if ( isset( $membership_map[ $type ] ) && current_user_can( 'mepr-active', 'membership:' . $membership_map[ $type ] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Check if a user completed a program.
 */
function zaher_user_completed_program( $program_id, $user_id = 0 ) {
	if ( ! $user_id ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user_id = get_current_user_id();
	}

	return (bool) get_user_meta( $user_id, 'zaher_program_completed_' . (int) $program_id, true );
}

/**
 * Get completed program IDs for a user (most recent first).
 */
function zaher_get_completed_program_ids( $user_id = 0 ) {
	if ( ! $user_id ) {
		if ( ! is_user_logged_in() ) {
			return array();
		}
		$user_id = get_current_user_id();
	}

	global $wpdb;

	$prefix = $wpdb->esc_like( 'zaher_program_completed_' );
	$rows   = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
			$user_id,
			$prefix . '%'
		)
	);

	if ( empty( $rows ) ) {
		return array();
	}

	$completed = array();
	foreach ( $rows as $row ) {
		$program_id = (int) substr( (string) $row->meta_key, strlen( $prefix ) );
		if ( $program_id ) {
			$completed[ $program_id ] = (int) $row->meta_value;
		}
	}

	if ( empty( $completed ) ) {
		return array();
	}

	arsort( $completed );
	return array_keys( $completed );
}

/**
 * Get in-progress (started but not completed) program IDs for a user (most recent first).
 *
 * @param int $user_id Optional. User ID. Defaults to current user.
 * @return array Array of program IDs with progress data, or empty array.
 */
function zaher_get_in_progress_program_ids( $user_id = 0 ) {
	if ( ! $user_id ) {
		if ( ! is_user_logged_in() ) {
			return array();
		}
		$user_id = get_current_user_id();
	}

	global $wpdb;

	$prefix = $wpdb->esc_like( 'zaher_program_progress_' );
	$rows   = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
			$user_id,
			$prefix . '%'
		)
	);

	if ( empty( $rows ) ) {
		return array();
	}

	// Get completed IDs to exclude them.
	$completed_ids = zaher_get_completed_program_ids( $user_id );

	$in_progress = array();
	foreach ( $rows as $row ) {
		$program_id = (int) substr( (string) $row->meta_key, strlen( $prefix ) );
		if ( $program_id && ! in_array( $program_id, $completed_ids, true ) ) {
			// Parse timestamp:progress format.
			$parts     = explode( ':', (string) $row->meta_value );
			$timestamp = isset( $parts[0] ) ? (int) $parts[0] : 0;
			$progress  = isset( $parts[1] ) ? (float) $parts[1] : 0;
			if ( $timestamp && $progress > 0 && $progress < 0.98 ) {
				$in_progress[ $program_id ] = $timestamp;
			}
		}
	}

	if ( empty( $in_progress ) ) {
		return array();
	}

	// Sort by timestamp (most recent first).
	arsort( $in_progress );
	return array_keys( $in_progress );
}

/**
 * Get progress percentage for a specific program.
 *
 * @param int $program_id Program ID.
 * @param int $user_id    Optional. User ID. Defaults to current user.
 * @return float Progress as decimal (0-1), or 0 if not found.
 */
function zaher_get_program_progress( $program_id, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id || ! $program_id ) {
		return 0;
	}

	$meta = get_user_meta( $user_id, 'zaher_program_progress_' . (int) $program_id, true );
	if ( ! $meta ) {
		return 0;
	}

	$parts = explode( ':', (string) $meta );
	return isset( $parts[1] ) ? (float) $parts[1] : 0;
}

/**
 * Customize favorites button markup inside the favorites list.
 */
add_filter( 'favorites/button/html', 'zaher_favorites_list_button_html', 10, 4 );
function zaher_favorites_list_button_html( $html, $post_id, $favorited, $site_id ) {
	if ( empty( $GLOBALS['zaher_favorites_list_context'] ) ) {
		return $html;
	}

	$label = $favorited ? 'Ukloni' : 'Dodaj';
	$icon  = $favorited ? 'xmark' : 'heart';

	return zaher_lineicon_svg( $icon ) . '<span class="simplefavorite-label">' . esc_html( $label ) . '</span>';
}

/**
 * Track member progress: last viewed program + view count (for "Popular").
 */
add_action( 'template_redirect', 'zaher_track_program_view_for_members', 20 );
function zaher_track_program_view_for_members() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( ! is_user_logged_in() ) {
		return;
	}
	if ( ! is_singular( 'programs' ) ) {
		return;
	}

	$program_id = get_queried_object_id();
	if ( ! $program_id ) {
		return;
	}

	// Only track if user can access this workout.
	if ( ! zaher_user_has_program_access( $program_id ) ) {
		return;
	}

	$user_id = get_current_user_id();

	update_user_meta( $user_id, 'zaher_last_program_id', (int) $program_id );
	update_user_meta( $user_id, 'zaher_last_program_url', esc_url_raw( get_permalink( $program_id ) ) );
	update_user_meta( $user_id, 'zaher_last_program_ts', time() );

	$views = (int) get_post_meta( $program_id, 'zaher_views', true );
	update_post_meta( $program_id, 'zaher_views', $views + 1 );
}

/**
 * REST: track member video progress for "Nastavi".
 */
add_action( 'rest_api_init', 'zaher_register_progress_endpoint' );
function zaher_register_progress_endpoint() {
	register_rest_route(
		'zaher/v1',
		'/progress',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'zaher_update_program_progress',
			'permission_callback' => function () {
				return is_user_logged_in();
			},
			'args'                => array(
				'program_id' => array(
					'type'     => 'integer',
					'required' => true,
				),
				'progress'   => array(
					'type'     => 'number',
					'required' => true,
				),
				'completed'  => array(
					'type' => 'boolean',
				),
			),
		)
	);
}

function zaher_update_program_progress( WP_REST_Request $request ) {
	$user_id    = get_current_user_id();
	$program_id = absint( $request->get_param( 'program_id' ) );
	$progress   = (float) $request->get_param( 'progress' );
	$completed  = filter_var( $request->get_param( 'completed' ), FILTER_VALIDATE_BOOLEAN );

	if ( ! $program_id || 'programs' !== get_post_type( $program_id ) ) {
		return new WP_Error( 'invalid_program', 'Invalid program.', array( 'status' => 400 ) );
	}

	if ( get_post_status( $program_id ) !== 'publish' ) {
		return new WP_Error( 'invalid_program', 'Program unavailable.', array( 'status' => 403 ) );
	}

	if ( ! zaher_user_has_program_access( $program_id ) ) {
		return new WP_Error( 'forbidden', 'No access to this program.', array( 'status' => 403 ) );
	}

	$progress = max( 0, min( 1, $progress ) );
	if ( $progress >= 0.98 ) {
		$completed = true;
	}

	if ( $completed ) {
		update_user_meta( $user_id, 'zaher_program_completed_' . $program_id, time() );
		// Remove from in-progress when completed.
		delete_user_meta( $user_id, 'zaher_program_progress_' . $program_id );
		$last_in_progress = (int) get_user_meta( $user_id, 'zaher_last_in_progress_program_id', true );
		if ( $last_in_progress === $program_id ) {
			delete_user_meta( $user_id, 'zaher_last_in_progress_program_id' );
			delete_user_meta( $user_id, 'zaher_last_in_progress_program_url' );
			delete_user_meta( $user_id, 'zaher_last_in_progress_program_ts' );
			delete_user_meta( $user_id, 'zaher_last_in_progress_program_progress' );
		}
	} else {
		// Store individual progress for this program (timestamp:progress format).
		update_user_meta( $user_id, 'zaher_program_progress_' . $program_id, time() . ':' . $progress );
		// Keep last in-progress for quick access.
		update_user_meta( $user_id, 'zaher_last_in_progress_program_id', $program_id );
		update_user_meta( $user_id, 'zaher_last_in_progress_program_url', esc_url_raw( get_permalink( $program_id ) ) );
		update_user_meta( $user_id, 'zaher_last_in_progress_program_ts', time() );
		update_user_meta( $user_id, 'zaher_last_in_progress_program_progress', $progress );
	}

	return rest_ensure_response(
		array(
			'ok'        => true,
			'completed' => $completed,
		)
	);
}

/**
 * Body class helpers.
 */
add_filter( 'body_class', 'zaher_member_body_class' );
function zaher_member_body_class( $classes ) {
	if ( is_user_logged_in() ) {
		$classes[] = 'is-member';
	}
	return $classes;
}

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

 // Extracts ID from Vimeo link
 function getVimeoVideoId($vimeoUrl) {
	$parts = parse_url($vimeoUrl);
	if (isset($parts['path'])) {
			$pathParts = explode('/', trim($parts['path'], '/'));
			return $pathParts[count($pathParts) - 1];
	}
	return false;
}

// This function will change the posts_per_page parameter to -1 (which means all posts) for the main query on the taxonomy archive page for 'kategorija'. Adjust the is_tax('kategorija') check if necessary to target the correct pages.
function modify_main_query( $query ) {
	if ( ! is_admin() && $query->is_main_query() ) {
			if ( is_tax('kategorija') ) { // If you're on a taxonomy archive page
					$query->set( 'posts_per_page', -1 );
			}
	}
}
add_action( 'pre_get_posts', 'modify_main_query' );

// check if the user is an admin or has a membership subscription.
function user_has_membership() {
	if(class_exists('MeprUser')) {
			$user = MeprUser::get_current_user();

			// Check if the user has a specific membership subscription.
			// Replace 'membership-slug' with the slug of your membership.
			if($user->is_member('membership-slug')) {
					return true;
			}
	}
	return false;
}

// whether a user has any membership at all (rather than a specific membership)
function user_has_memberships() {
	if(class_exists('MeprUser')) {
			$user = MeprUser::get_current_user();

			// Get all memberships
			$memberships = get_posts(array(
					'post_type' => 'memberpressproduct',
					'numberposts' => -1
			));

			// Check if the user has any membership subscription.
			foreach($memberships as $membership) {
					if($user->is_member($membership->post_name)) {
							return true;
					}
			}
	}
	return false;
}

// Check if a user is an admin or has a membership:
function user_is_admin_or_has_memberships() {
	if(current_user_can('administrator') || user_has_memberships()) {
			return true;
	}
	return false;
}

// This code will add a CSS rule to the admin page for editing tags and terms, which will hide the description field. Please be aware that the field is just hidden, not removed entirely, and it won't affect your site's frontend.
function remove_tax_description_field() {
	echo '<style>
			.term-description-wrap {
					display: none;
			}
	</style>';
}

add_action( 'admin_head-edit-tags.php', 'remove_tax_description_field' );
add_action( 'admin_head-term.php', 'remove_tax_description_field' );

// Remove admin bar for non-wp users
add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
	if (!current_user_can('administrator') && !is_admin()) {
		show_admin_bar(false);
	}
}

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

add_filter( 'rank_math/metabox/priority', function( $priority ) {
	return 'low';
});

// GTM u <head>
add_action('wp_head', 'zaher_add_gtm_head', 1);
function zaher_add_gtm_head() {
  ?>
  <!-- Google Tag Manager -->
  <script>
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-TZGV2HD6');
  </script>
  <!-- End Google Tag Manager -->
  <?php
}

// GTM noscript u <body>
add_action('wp_body_open', 'zaher_add_gtm_body');
function zaher_add_gtm_body() {
  ?>
  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TZGV2HD6"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->
  <?php
}

function remove_memberpress_meta_boxes() {
    remove_meta_box('mepr_metabox_postbox', 'post', 'normal');
}
add_action('add_meta_boxes', 'remove_memberpress_meta_boxes', 20);
