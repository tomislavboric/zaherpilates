<?php
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
