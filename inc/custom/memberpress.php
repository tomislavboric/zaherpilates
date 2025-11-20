<?php // Function to check if the current user has the required subscriptions
function has_required_subscriptions($post_id) {
    // Retrieve the selected subscription types from the subscription_type field
    $subscription_types = get_field('subscription_type', $post_id);

    // Check if the subscription types are selected and the user has those subscriptions
    if ($subscription_types && is_user_logged_in()) {
        $user_subscriptions = memberpress_get_user_active_membership_ids(get_current_user_id());

        foreach ($subscription_types as $subscription_type) {
            if (!in_array($subscription_type, $user_subscriptions)) {
                return false;
            }
        }

        return true;
    }

    return false;
}

// Shortcode to display content based on the required subscriptions
function subscription_content_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID(),
    ), $atts, 'subscription_content');

    $post_id = $atts['post_id'];

    if (has_required_subscriptions($post_id)) {
        return do_shortcode($content);
    }

    return '';
}

add_shortcode('subscription_content', 'subscription_content_shortcode');

add_action(
    'wp_enqueue_scripts',
    function() {
        $is_memberpress_checkout = false;

        if ( class_exists( 'MeprReadyLaunchCtrl' ) && MeprReadyLaunchCtrl::template_enabled( 'checkout' ) ) {
            $is_memberpress_checkout = true;
        } elseif ( class_exists( 'MeprProduct' ) && is_singular( MeprProduct::$cpt ) ) {
            $is_memberpress_checkout = true;
        }

        if ( $is_memberpress_checkout ) {
            wp_enqueue_style(
                'my-mepr-checkout-style',
                get_stylesheet_directory_uri() . '/dist/assets/css/' . foundationpress_asset_path( 'app.css' ),
                [],
                wp_get_theme()->get( 'Version' )
            );
        }
    }
);

add_filter(
    'mepr_design_style_handles',
    function( $handles ) {
        $handles[] = 'my-mepr-checkout-style';
        return array_unique( $handles );
    }
);
