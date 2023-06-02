<?php
/**
 *
 * ACF Admin Styling
 *
 */

function my_acf_admin_head() {
	?>
	<style type="text/css">

		/* Hide Flexible Content Label */
		.acf-field.acf-field-flexible-content > .acf-label {
			display: none;
		}

	</style>

	<script type="text/javascript">
	(function($){

		/* ... */

	})(jQuery);
	</script>
	<?php
}

add_action('acf/input/admin_head', 'my_acf_admin_head');
