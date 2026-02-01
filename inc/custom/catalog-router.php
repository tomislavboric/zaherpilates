<?php
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
