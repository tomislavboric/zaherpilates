<header class="header">
	<div class="grid-container">
		<?php
			$post_terms = get_the_terms( get_the_ID(), 'category' );

			if ( ! empty( $post_terms ) && ! is_wp_error( $post_terms ) ) {
					$term = array_pop( $post_terms );
					echo '<a href="' . esc_url( get_term_link( $term ) ) . '">' . '&larr; ' . $term->name . '</a>';
			}
		?>
	</div>
</header>
