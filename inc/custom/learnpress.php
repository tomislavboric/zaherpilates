<?php

// Hide LearnPress Admin Menus
function plt_hide_learnpress_menus() {
	//Hide "LearnPress".
	// emove_menu_page('learn_press');
	//Hide "LearnPress → Courses".
	// remove_submenu_page('learn_press', 'edit.php?post_type=lp_course');
	//Hide "LearnPress → Lessons".
	// remove_submenu_page('learn_press', 'edit.php?post_type=lp_lesson');
	//Hide "LearnPress → Quizzes".
	remove_submenu_page('learn_press', 'edit.php?post_type=lp_quiz');
	//Hide "LearnPress → Questions".
	remove_submenu_page('learn_press', 'edit.php?post_type=lp_question');
	//Hide "LearnPress → Orders".
	// remove_submenu_page('learn_press', 'edit.php?post_type=lp_order');
	//Hide "LearnPress → Categories".
	// remove_submenu_page('learn_press', 'edit-tags.php?taxonomy=course_category');
	//Hide "LearnPress → Tags".
	remove_submenu_page('learn_press', 'edit-tags.php?taxonomy=course_tag');
	//Hide "LearnPress → Statistics".
	// remove_submenu_page('learn_press', 'learn-press-statistics');
	//Hide "LearnPress → Add-ons".
	// remove_submenu_page('learn_press', 'learn-press-addons');
	//Hide "LearnPress → Themes".
	// remove_submenu_page('learn_press', 'learn-press-themes');
	//Hide "LearnPress → Settings".
	// remove_submenu_page('learn_press', 'learn-press-settings');
	//Hide "LearnPress → Tools".
	// remove_submenu_page('learn_press', 'learn-press-tools');
}

add_action('admin_menu', 'plt_hide_learnpress_menus', 11);


// How to Hide LearnPress Meta Boxes
function plt_hide_learnpress_metaboxes() {
	$screen = get_current_screen();
	if ( !$screen ) {
		return;
	}

	//Hide the "Curriculum" meta box.
	// remove_meta_box('course-editor', $screen->id, 'normal');
	//Hide the "Course Settings" meta box.
	// remove_meta_box('course-settings', $screen->id, 'normal');
	//Hide the "Assigned" meta box.
	// remove_meta_box('lesson_assigned', $screen->id, 'side');
	//Hide the "Lesson Settings" meta box.
	// remove_meta_box('lesson_settings', $screen->id, 'normal');
	//Hide the "Assigned" meta box.
	// remove_meta_box('quiz_assigned', $screen->id, 'side');
	//Hide the "Questions" meta box.
	// remove_meta_box('quiz-editor', $screen->id, 'normal');
	//Hide the "Quiz Settings" meta box.
	// remove_meta_box('quiz_settings', $screen->id, 'normal');
	//Hide the "Assigned" meta box.
	// remove_meta_box('question_assigned', $screen->id, 'side');
	//Hide the "Answer Options" meta box.
	// remove_meta_box('question-editor', $screen->id, 'normal');
	//Hide the "Question Settings" meta box.
	// remove_meta_box('question_settings', $screen->id, 'normal');
	//Hide the "Order Details" meta box.
	// remove_meta_box('order_details', $screen->id, 'normal');
	//Hide the "Order Actions" meta box.
	// remove_meta_box('submitdiv', $screen->id, 'side');
	//Hide the "Order Exports" meta box.
	// remove_meta_box('order_exports', $screen->id, 'side');
}

add_action('add_meta_boxes', 'plt_hide_learnpress_metaboxes', 20);

// Hide LearnPress Dashboard Widgets
function plt_hide_learnpress_dashboard_widgets() {
	$screen = get_current_screen();
	if ( !$screen ) {
		return;
	}

	//Remove the "LearnPress order status" widget.
	// remove_meta_box('learn_press_dashboard_order_statuses', 'dashboard', 'normal');
	//Remove the "LearnPress status" widget.
	// remove_meta_box('learn_press_dashboard_plugin_status', 'dashboard', 'normal');
}

add_action('wp_dashboard_setup', 'plt_hide_learnpress_dashboard_widgets', 20);
