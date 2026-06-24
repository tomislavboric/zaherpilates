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

function theme_apply_minimal_memberpress_checkout_options() {
    if ( ! class_exists( 'MeprOptions' ) || ! theme_is_memberpress_checkout_context() ) {
        return;
    }

    $mepr_options = MeprOptions::fetch();

    if ( ! is_object( $mepr_options ) ) {
        return;
    }

    $mepr_options->username_is_email                = true;
    $mepr_options->disable_checkout_password_fields = false;
    $mepr_options->show_fname_lname                 = true;
    $mepr_options->require_fname_lname              = false;
    $mepr_options->show_address_fields              = false;
    $mepr_options->require_address_fields           = false;
    $mepr_options->show_fields_logged_in_purchases  = false;

    if ( isset( $mepr_options->custom_fields ) && is_array( $mepr_options->custom_fields ) ) {
        $mepr_options->custom_fields = array();
    }
}

add_action( 'init', 'theme_apply_minimal_memberpress_checkout_options', 1 );
add_action( 'wp', 'theme_apply_minimal_memberpress_checkout_options', 1 );

add_filter( 'mepr-checkout-no-billing-address', '__return_true' );

function theme_memberpress_stripe_elements_appearance( $appearance ) {
    $appearance = is_array( $appearance ) ? $appearance : array();

    $theme_appearance = array(
        'theme'     => 'stripe',
        'variables' => array(
            'fontFamily'           => 'Poppins, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'fontSizeBase'         => '14px',
            'fontLineHeight'       => '1.5',
            'colorPrimary'         => '#b47f75',
            'colorBackground'      => '#ffffff',
            'colorText'            => '#1c1917',
            'colorTextSecondary'   => '#8f8782',
            'colorTextPlaceholder' => '#c8c2bf',
            'colorDanger'          => '#d94a3a',
            'borderRadius'         => '8px',
            'spacingUnit'          => '4px',
            'gridRowSpacing'       => '12px',
            'gridColumnSpacing'    => '12px',
        ),
        'rules'     => array(
            '.Input'        => array(
                'border'    => '1px solid #e7e1de',
                'boxShadow' => 'none',
                'padding'   => '11px 14px',
            ),
            '.Input:focus'  => array(
                'border'    => '1px solid #b47f75',
                'boxShadow' => '0 0 0 3px rgba(180, 127, 117, 0.12)',
            ),
            '.Input--invalid' => array(
                'border'    => '1px solid rgba(217, 74, 58, 0.55)',
                'boxShadow' => '0 0 0 3px rgba(217, 74, 58, 0.07)',
            ),
            '.Label'        => array(
                'color'      => '#1c1917',
                'fontSize'   => '12px',
                'fontWeight' => '600',
            ),
            '.Error'        => array(
                'color'      => '#d94a3a',
                'fontSize'   => '12px',
                'fontWeight' => '600',
            ),
        ),
    );

    return array_replace_recursive( $theme_appearance, $appearance );
}

add_filter( 'mepr-stripe-elements-appearance', 'theme_memberpress_stripe_elements_appearance' );


function theme_validate_memberpress_checkout_password_length( $errors ) {
    if ( ! isset( $_POST['mepr_process_signup_form'], $_POST['mepr_product_id'] ) ) {
        return $errors;
    }

    if ( class_exists( 'MeprUtils' ) && MeprUtils::is_user_logged_in() ) {
        return $errors;
    }

    if ( class_exists( 'MeprOptions' ) ) {
        $mepr_options = MeprOptions::fetch();

        if ( is_object( $mepr_options ) && true === $mepr_options->disable_checkout_password_fields ) {
            return $errors;
        }
    }

    $password = isset( $_POST['mepr_user_password'] ) ? (string) wp_unslash( $_POST['mepr_user_password'] ) : '';

    if ( '' === $password ) {
        return $errors;
    }

    $password_length = function_exists( 'mb_strlen' ) ? mb_strlen( $password ) : strlen( $password );

    if ( $password_length < 8 ) {
        $errors['mepr_user_password'] = __( 'Lozinka mora imati najmanje 8 znakova.', 'foundationpress' );
    }

    return $errors;
}

add_filter( 'mepr-validate-signup', 'theme_validate_memberpress_checkout_password_length', 20 );

add_action(
    'wp_enqueue_scripts',
    function() {
        if ( function_exists( 'theme_is_memberpress_checkout_shell_context' ) && theme_is_memberpress_checkout_shell_context() ) {
            wp_enqueue_style(
                'my-mepr-checkout-style',
                get_stylesheet_directory_uri() . '/dist/assets/css/' . foundationpress_asset_path( 'app.css' ),
                array( 'mp-pro-checkout' ),
                wp_get_theme()->get( 'Version' )
            );
        }
    },
    1000001
);

add_filter(
    'mepr_design_style_handles',
    function( $handles ) {
        $handles[] = 'my-mepr-checkout-style';
        return array_unique( $handles );
    }
);

add_filter(
    'gettext',
    function( $translation, $text, $domain ) {
        if ( 'memberpress' !== $domain ) {
            return $translation;
        }

        $map = array(
            'Most Popular'   => 'Najpovoljnije',
            'Free forever'   => 'Besplatno zauvijek',
            'with coupon'    => 'uz kupon',
            ' with coupon '  => ' uz kupon ',
        );

        if ( isset( $map[ $text ] ) ) {
            return $map[ $text ];
        }

        return $translation;
    },
    20,
    3
);

add_filter(
    'gettext_with_context',
    function( $translation, $text, $context, $domain ) {
        if ( 'memberpress' !== $domain ) {
            return $translation;
        }

        if ( 'Free forever' === $text ) {
            return 'Besplatno zauvijek';
        }

        if ( 'with coupon' === $text ) {
            return 'uz kupon';
        }

        return $translation;
    },
    20,
    4
);


function theme_is_memberpress_checkout_context() {
    $is_checkout_post = isset( $_POST['mepr_process_signup_form'] );
    $ajax_action      = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
    $is_checkout_ajax = wp_doing_ajax() && in_array(
        $ajax_action,
        array(
            'mepr_get_checkout_state',
            'mepr_stripe_confirm_payment',
            'mepr_stripe_create_checkout_session',
        ),
        true
    );

    if ( $is_checkout_post || $is_checkout_ajax ) {
        return true;
    }

    if ( ! did_action( 'wp' ) ) {
        return false;
    }

    if ( class_exists( 'MeprAppHelper' ) && MeprAppHelper::has_block( 'memberpress/checkout' ) ) {
        return true;
    }

    return class_exists( 'MeprProduct' ) && is_singular( MeprProduct::$cpt );
}

function theme_is_memberpress_thankyou_context() {
    if ( ! did_action( 'wp' ) ) {
        return false;
    }

    $post = get_queried_object();

    if ( class_exists( 'MeprAppHelper' ) && $post instanceof WP_Post ) {
        return MeprAppHelper::is_thankyou_page( $post );
    }

    if ( ! class_exists( 'MeprOptions' ) ) {
        return false;
    }

    $mepr_options = MeprOptions::fetch();

    return $mepr_options && ! empty( $mepr_options->thankyou_page_id ) && is_page( (int) $mepr_options->thankyou_page_id );
}

function theme_is_memberpress_checkout_shell_context() {
    return theme_is_memberpress_checkout_context() || theme_is_memberpress_thankyou_context();
}

add_action( 'wp_ajax_theme_memberpress_coupon_nonce', 'theme_memberpress_coupon_nonce_ajax' );
add_action( 'wp_ajax_nopriv_theme_memberpress_coupon_nonce', 'theme_memberpress_coupon_nonce_ajax' );
function theme_memberpress_coupon_nonce_ajax() {
    nocache_headers();

    wp_send_json_success(
        array(
            'coupon_nonce' => wp_create_nonce( 'mepr_coupons' ),
        )
    );
}

// Bridge for the checkout-coupon-nonce.js module. Config is exposed as
// window.themeMemberPressCouponNonce; the JS lives in src/assets/js/lib/checkout-coupon-nonce.js.
add_action( 'wp_enqueue_scripts', 'theme_enqueue_memberpress_coupon_nonce_refresh', 1000002 );
function theme_enqueue_memberpress_coupon_nonce_refresh() {
    if ( ! theme_is_memberpress_checkout_context() ) {
        return;
    }

    $handle = wp_script_is( 'mp-signup', 'enqueued' ) ? 'mp-signup' : 'foundation';

    if ( ! wp_script_is( $handle, 'enqueued' ) && ! wp_script_is( $handle, 'registered' ) ) {
        return;
    }

    wp_add_inline_script(
        $handle,
        'window.themeMemberPressCouponNonce=' . wp_json_encode(
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'action'  => 'theme_memberpress_coupon_nonce',
            )
        ) . ';',
        'before'
    );
}
