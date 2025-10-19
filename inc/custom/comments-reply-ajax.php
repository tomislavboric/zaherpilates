<?php
/**
 * Add this to your theme's functions.php file
 * AJAX handler for editing user comments
 */

add_action('wp_ajax_edit_user_comment', 'handle_edit_user_comment');

function handle_edit_user_comment() {
	// Verify nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edit_comment_nonce')) {
		wp_send_json_error('Nevažeći sigurnosni token');
		return;
	}

	$comment_id = intval($_POST['comment_id']);
	$new_content = sanitize_textarea_field($_POST['content']);

	// Validate content
	if (empty($new_content)) {
		wp_send_json_error('Komentar ne može biti prazan');
		return;
	}

	// Get comment
	$comment = get_comment($comment_id);

	if (!$comment) {
		wp_send_json_error('Komentar nije pronađen');
		return;
	}

	// Check if user owns this comment
	if (get_current_user_id() != $comment->user_id) {
		wp_send_json_error('Nemate dozvolu za uređivanje ovog komentara');
		return;
	}

	// Optional: Check if comment is not too old (limit editing to 30 minutes)
	// Uncomment the lines below if you want to enforce a time limit
	/*
	$comment_time = strtotime($comment->comment_date_gmt);
	$time_limit = 30 * 60; // 30 minutes in seconds

	if ((time() - $comment_time) > $time_limit) {
		wp_send_json_error('Komentar se može uređivati samo 30 minuta nakon objavljivanja');
		return;
	}
	*/

	// Update comment
	$updated = wp_update_comment([
		'comment_ID' => $comment_id,
		'comment_content' => $new_content
	]);

	if ($updated !== false) {
		wp_send_json_success([
			'content' => wpautop($new_content)
		]);
	} else {
		wp_send_json_error('Greška prilikom spremanja komentara');
	}
}
