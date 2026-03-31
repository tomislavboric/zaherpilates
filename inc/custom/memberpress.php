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

/**
 * Checkout upgrade popup — enqueue & render.
 *
 * Shows timed upgrade popups on configured MemberPress checkout pages.
 */
function zaher_get_checkout_popup_defaults() {
    return array(
        'timer_minutes' => 10,
        'delay_seconds' => 6,
    );
}

function zaher_get_checkout_popup_default_template_key() {
    return 'template_1';
}

function zaher_get_checkout_popup_templates() {
    return array(
        'template_1' => array(
            'label'                   => 'Template 1 - Trenutni popup',
            'badge_text'              => 'Ekskluzivna ponuda',
            'title_html'              => 'Prije završetka,<br>pogledaj ovu ponudu',
            'subtitle_html'           => 'Na ovom checkoutu možeš odmah prebaciti kupnju na <strong>povoljniju pretplatu</strong> uz posebnu ponudu dostupnu samo ovdje.',
            'body_html'               => '',
            'cta_label'               => 'Da, želim ovu ponudu',
            'skip_label'              => 'Ne, ostajem na trenutnoj pretplati',
            'recommended_period_type' => '',
            'recommended_period'      => 0,
        ),
        'template_2' => array(
            'label'                   => 'Template 2 - 3 mjeseca commitment',
            'badge_text'              => 'Posebna ponuda',
            'title_html'              => 'Kladi se na sebe sljedeća 3 mjeseca!',
            'subtitle_html'           => 'Ova posebna ponuda dostupna ti je samo sada. Ako ju odbiješ, više te nećemo gnjaviti s njom.',
            'body_html'               => '<p>Po iskustvu naših članica, velika je šansa da ćeš na LOOPu ostati barem 3 mjeseca. Zato ima smisla odmah krenuti na <strong>{{target_title}}</strong> i u startu si osigurati {{price_comparison_html}}{{savings_suffix}}.</p><p>Kad si daš 3 mjeseca kontinuiteta, trening postane dio rutine, držanje se popravlja, bolovi se često smanjuju, a promjene u snazi, izdržljivosti i izgledu postaju puno realnije.</p><p>Uz bolju cijenu, <strong>{{target_title}}</strong> ti otključava i dodatne kategorije koje se kroz godinu pojavljuju u membershipu.</p>',
            'cta_label'               => 'Da, želim ovu ponudu',
            'skip_label'              => 'Ne, ostajem na trenutnoj pretplati',
            'recommended_period_type' => 'months',
            'recommended_period'      => 3,
        ),
    );
}

function zaher_get_checkout_popup_template_choices() {
    $templates = zaher_get_checkout_popup_templates();
    $choices   = array();

    foreach ( $templates as $key => $template ) {
        $choices[ $key ] = array(
            'label'                 => isset( $template['label'] ) ? (string) $template['label'] : $key,
            'recommendedPeriodType' => isset( $template['recommended_period_type'] ) ? (string) $template['recommended_period_type'] : '',
            'recommendedPeriod'     => isset( $template['recommended_period'] ) ? (int) $template['recommended_period'] : 0,
        );
    }

    return $choices;
}

function zaher_get_checkout_popup_product( $product_id ) {
    if ( ! class_exists( 'MeprProduct' ) ) {
        return null;
    }

    $product_id = (int) $product_id;

    if ( ! $product_id ) {
        return null;
    }

    $product = new MeprProduct( $product_id );

    if ( empty( $product->ID ) || 'publish' !== get_post_status( $product->ID ) ) {
        return null;
    }

    return $product;
}

function zaher_find_checkout_popup_product_id_by_url( $url ) {
    if ( ! class_exists( 'MeprProduct' ) || '' === trim( (string) $url ) ) {
        return 0;
    }

    $target_parts = wp_parse_url( $url );
    $target_path  = isset( $target_parts['path'] ) ? untrailingslashit( $target_parts['path'] ) : '';

    if ( '' === $target_path ) {
        return 0;
    }

    $posts = get_posts(
        array(
            'post_type'      => MeprProduct::$cpt,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        )
    );

    foreach ( $posts as $product_id ) {
        $product = zaher_get_checkout_popup_product( $product_id );

        if ( ! $product ) {
            continue;
        }

        $product_parts = wp_parse_url( $product->url() );
        $product_path  = isset( $product_parts['path'] ) ? untrailingslashit( $product_parts['path'] ) : '';

        if ( $product_path === $target_path ) {
            return (int) $product->ID;
        }
    }

    return 0;
}

function zaher_get_legacy_checkout_popup_rows() {
    $defaults      = zaher_get_checkout_popup_defaults();
    $source_id     = (int) get_option( 'zaher_popup_monthly_product_id', 0 );
    $target_url    = trim( (string) get_option( 'zaher_popup_quarterly_url', '' ) );
    $target_id     = zaher_find_checkout_popup_product_id_by_url( $target_url );
    $coupon_code   = '';
    $query         = wp_parse_url( $target_url, PHP_URL_QUERY );
    $query_params  = array();

    if ( ! $source_id && '' === $target_url ) {
        return array();
    }

    if ( is_string( $query ) && '' !== $query ) {
        parse_str( $query, $query_params );
        $coupon_code = isset( $query_params['coupon'] ) ? sanitize_text_field( $query_params['coupon'] ) : '';
    }

    return array(
        array(
            'template_key'      => zaher_get_checkout_popup_default_template_key(),
            'source_product_id' => $source_id,
            'target_product_id' => $target_id,
            'coupon_code'       => $coupon_code,
            'timer_minutes'     => max( 1, (int) get_option( 'zaher_popup_timer_minutes', $defaults['timer_minutes'] ) ),
            'delay_seconds'     => max( 0, (int) get_option( 'zaher_popup_delay_seconds', $defaults['delay_seconds'] ) ),
        ),
    );
}

function zaher_get_saved_checkout_popup_rows() {
    $saved_rows = get_option( 'zaher_checkout_popups', null );

    if ( is_array( $saved_rows ) ) {
        return $saved_rows;
    }

    return zaher_get_legacy_checkout_popup_rows();
}

function zaher_get_checkout_popup_period_units( $product ) {
    if ( ! $product instanceof MeprProduct || $product->is_one_time_payment() ) {
        return null;
    }

    switch ( $product->period_type ) {
        case 'months':
            return array(
                'group' => 'months',
                'value' => (int) $product->period,
            );
        case 'years':
            return array(
                'group' => 'months',
                'value' => (int) $product->period * 12,
            );
        case 'days':
            return array(
                'group' => 'days',
                'value' => (int) $product->period,
            );
        case 'weeks':
            return array(
                'group' => 'days',
                'value' => (int) $product->period * 7,
            );
        default:
            return null;
    }
}

function zaher_get_checkout_popup_period_ratio( $source_product, $target_product ) {
    $source_units = zaher_get_checkout_popup_period_units( $source_product );
    $target_units = zaher_get_checkout_popup_period_units( $target_product );

    if ( ! is_array( $source_units ) || ! is_array( $target_units ) ) {
        return 0;
    }

    if ( $source_units['group'] !== $target_units['group'] || (int) $source_units['value'] <= 0 ) {
        return 0;
    }

    $ratio = (int) $target_units['value'] / (int) $source_units['value'];

    if ( $ratio < 1 || (int) $source_units['value'] * $ratio !== (int) $target_units['value'] ) {
        return 0;
    }

    return $ratio;
}

function zaher_get_checkout_popup_short_period_label( $product ) {
    if ( ! $product instanceof MeprProduct ) {
        return '';
    }

    switch ( $product->period_type ) {
        case 'days':
            return 'd.';
        case 'weeks':
            return 'tj.';
        case 'months':
            return 'mj.';
        case 'years':
            return 'god.';
        default:
            return '';
    }
}

function zaher_get_checkout_popup_old_price_text( $source_product, $target_product ) {
    if ( ! $source_product instanceof MeprProduct ) {
        return '';
    }

    $source_price = MeprAppHelper::format_currency( $source_product->price, true, false );
    $ratio        = zaher_get_checkout_popup_period_ratio( $source_product, $target_product );

    if ( $ratio > 1 ) {
        return sprintf( '%d × %s', $ratio, $source_price );
    }

    return $source_price;
}

function zaher_get_checkout_popup_reference_price_amount( $source_product, $target_product ) {
    if ( ! $target_product instanceof MeprProduct ) {
        return 0;
    }

    if ( $source_product instanceof MeprProduct ) {
        $ratio = zaher_get_checkout_popup_period_ratio( $source_product, $target_product );

        if ( $ratio > 1 ) {
            return (float) $source_product->price * $ratio;
        }
    }

    return (float) $target_product->price;
}

function zaher_get_checkout_popup_new_price_text( $target_product, $coupon_code = '' ) {
    if ( ! $target_product instanceof MeprProduct ) {
        return '';
    }

    if ( $coupon_code && class_exists( 'MeprCoupon' ) && ! MeprCoupon::is_valid_coupon_code( $coupon_code, $target_product->ID ) ) {
        $coupon_code = '';
    }

    $amount_text = MeprAppHelper::format_currency( $target_product->adjusted_price( $coupon_code, false ), true, false );

    if ( $target_product->is_one_time_payment() ) {
        return $amount_text;
    }

    if ( (int) $target_product->period <= 1 ) {
        return sprintf( '%s / %s', $amount_text, zaher_get_checkout_popup_short_period_label( $target_product ) );
    }

    return sprintf(
        '%s / %d %s',
        $amount_text,
        (int) $target_product->period,
        zaher_get_checkout_popup_short_period_label( $target_product )
    );
}

function zaher_get_checkout_popup_savings_text( $source_product, $target_product, $coupon_code = '' ) {
    if ( ! $target_product instanceof MeprProduct ) {
        return '';
    }

    if ( $coupon_code && class_exists( 'MeprCoupon' ) && ! MeprCoupon::is_valid_coupon_code( $coupon_code, $target_product->ID ) ) {
        $coupon_code = '';
    }

    $reference_amount = zaher_get_checkout_popup_reference_price_amount( $source_product, $target_product );
    $adjusted_amount  = (float) $target_product->adjusted_price( $coupon_code, false );
    $savings_amount   = max( 0, $reference_amount - $adjusted_amount );

    if ( $savings_amount <= 0 ) {
        return '';
    }

    return MeprAppHelper::format_currency( $savings_amount, true, false );
}

function zaher_get_checkout_popup_price_comparison_html( $target_product, $coupon_code = '' ) {
    $adjusted_price = zaher_get_checkout_popup_new_price_text( $target_product, $coupon_code );
    $base_price     = zaher_get_checkout_popup_new_price_text( $target_product );

    if ( '' === $adjusted_price ) {
        return '';
    }

    if ( $adjusted_price === $base_price ) {
        return '<strong>' . esc_html( $adjusted_price ) . '</strong>';
    }

    return '<strong>' . esc_html( $adjusted_price ) . '</strong> umjesto <strong>' . esc_html( $base_price ) . '</strong>';
}

function zaher_get_checkout_popup_template_content( $template_key, $source_product, $target_product, $coupon_code = '' ) {
    $templates     = zaher_get_checkout_popup_templates();
    $default_key   = zaher_get_checkout_popup_default_template_key();
    $template_key  = isset( $templates[ $template_key ] ) ? $template_key : $default_key;
    $template      = $templates[ $template_key ];
    $target_title  = $target_product instanceof MeprProduct ? get_the_title( $target_product->ID ) : '';
    $savings_text  = zaher_get_checkout_popup_savings_text( $source_product, $target_product, $coupon_code );
    $replacements  = array(
        '{{target_title}}'          => esc_html( $target_title ),
        '{{price_comparison_html}}' => zaher_get_checkout_popup_price_comparison_html( $target_product, $coupon_code ),
        '{{savings_suffix}}'        => $savings_text ? ', uz uštedu od <strong>' . esc_html( $savings_text ) . '</strong>' : '',
    );

    return array(
        'key'          => $template_key,
        'label'        => isset( $template['label'] ) ? (string) $template['label'] : $template_key,
        'badgeText'    => wp_strip_all_tags( strtr( (string) $template['badge_text'], $replacements ) ),
        'titleHtml'    => wp_kses_post( strtr( (string) $template['title_html'], $replacements ) ),
        'subtitleHtml' => wp_kses_post( strtr( (string) $template['subtitle_html'], $replacements ) ),
        'bodyHtml'     => wp_kses_post( strtr( (string) $template['body_html'], $replacements ) ),
        'ctaLabel'     => wp_strip_all_tags( strtr( (string) $template['cta_label'], $replacements ) ),
        'skipLabel'    => wp_strip_all_tags( strtr( (string) $template['skip_label'], $replacements ) ),
    );
}

function zaher_get_checkout_popup_target_url( $target_product, $coupon_code = '' ) {
    if ( ! $target_product instanceof MeprProduct ) {
        return '';
    }

    $url = $target_product->url();

    if ( $coupon_code && class_exists( 'MeprCoupon' ) && MeprCoupon::is_valid_coupon_code( $coupon_code, $target_product->ID ) ) {
        $url = add_query_arg( 'coupon', $coupon_code, $url );
    }

    return $url;
}

function zaher_build_checkout_popup_runtime_config( $row ) {
    $defaults       = zaher_get_checkout_popup_defaults();
    $template_key   = isset( $row['template_key'] ) ? sanitize_key( $row['template_key'] ) : zaher_get_checkout_popup_default_template_key();
    $source_product = zaher_get_checkout_popup_product( isset( $row['source_product_id'] ) ? $row['source_product_id'] : 0 );
    $target_product = zaher_get_checkout_popup_product( isset( $row['target_product_id'] ) ? $row['target_product_id'] : 0 );
    $coupon_code    = isset( $row['coupon_code'] ) ? sanitize_text_field( $row['coupon_code'] ) : '';

    if ( ! $source_product || ! $target_product ) {
        return null;
    }

    if ( $coupon_code && class_exists( 'MeprCoupon' ) && ! MeprCoupon::is_valid_coupon_code( $coupon_code, $target_product->ID ) ) {
        $coupon_code = '';
    }

    $template_content = zaher_get_checkout_popup_template_content( $template_key, $source_product, $target_product, $coupon_code );
    $target_url       = zaher_get_checkout_popup_target_url( $target_product, $coupon_code );
    $old_price        = zaher_get_checkout_popup_old_price_text( $source_product, $target_product );
    $new_price        = zaher_get_checkout_popup_new_price_text( $target_product, $coupon_code );
    $offer_version    = sha1(
        wp_json_encode(
            array(
                'source_product_id' => (int) $source_product->ID,
                'target_product_id' => (int) $target_product->ID,
                'target_url'        => $target_url,
                'old_price'         => $old_price,
                'new_price'         => $new_price,
                'template'          => $template_content,
            )
        )
    );

    return array(
        'sourceProductId'  => (int) $source_product->ID,
        'targetProductId'  => (int) $target_product->ID,
        'offerVersion'     => $offer_version,
        'template'         => $template_content,
        'targetUrl'        => $target_url,
        'oldPrice'         => $old_price,
        'newPrice'         => $new_price,
        'timerMinutes'     => isset( $row['timer_minutes'] ) && '' !== $row['timer_minutes'] ? max( 1, (int) $row['timer_minutes'] ) : $defaults['timer_minutes'],
        'delaySeconds'     => isset( $row['delay_seconds'] ) && '' !== $row['delay_seconds'] ? max( 0, (int) $row['delay_seconds'] ) : $defaults['delay_seconds'],
    );
}

function zaher_get_checkout_popup_runtime_configs() {
    $rows          = zaher_get_saved_checkout_popup_rows();
    $runtime       = array();
    $seen_products = array();

    if ( ! is_array( $rows ) ) {
        return $runtime;
    }

    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $config = zaher_build_checkout_popup_runtime_config( $row );

        if ( ! is_array( $config ) || empty( $config['sourceProductId'] ) || empty( $config['targetUrl'] ) ) {
            continue;
        }

        if ( isset( $seen_products[ $config['sourceProductId'] ] ) ) {
            continue;
        }

        $runtime[] = $config;
        $seen_products[ $config['sourceProductId'] ] = true;
    }

    return $runtime;
}

function zaher_is_memberpress_checkout_context() {
    if ( class_exists( 'MeprReadyLaunchCtrl' ) && MeprReadyLaunchCtrl::template_enabled( 'checkout' ) ) {
        return true;
    }

    if ( class_exists( 'MeprAppHelper' ) && MeprAppHelper::has_block( 'memberpress/checkout' ) ) {
        return true;
    }

    return class_exists( 'MeprProduct' ) && is_singular( MeprProduct::$cpt );
}

add_action( 'wp_enqueue_scripts', 'zaher_enqueue_checkout_popup' );
function zaher_enqueue_checkout_popup() {
    $popup_configs = zaher_get_checkout_popup_runtime_configs();

    if ( empty( $popup_configs ) ) {
        return;
    }

    if ( ! zaher_is_memberpress_checkout_context() ) {
        return;
    }

    // Render on any MemberPress checkout surface; JS will verify the active product ID.
    wp_localize_script(
        'foundation',
        'zaherPopupConfig',
        array(
            'popups'   => $popup_configs,
            'defaults' => zaher_get_checkout_popup_defaults(),
        )
    );
}

add_action( 'wp_footer', 'zaher_render_checkout_popup' );
function zaher_render_checkout_popup() {
    $popup_configs = zaher_get_checkout_popup_runtime_configs();

    if ( empty( $popup_configs ) ) {
        return;
    }

    if ( ! zaher_is_memberpress_checkout_context() ) {
        return;
    }

    include get_stylesheet_directory() . '/memberpress/checkout/popup.php';
}
