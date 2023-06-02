<?php
/**
 *
 * SeoPress
 *
 */

// Disable breadcrumbs inline CSS
function sp_pro_breadcrumbs_css() {
	return false;
}
add_action('seopress_pro_breadcrumbs_css', 'sp_pro_breadcrumbs_css');
