<?php
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
