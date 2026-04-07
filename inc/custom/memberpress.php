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

function zaher_get_checkout_popup_default_template_key() {
    return 'template_1';
}

function zaher_get_checkout_popup_templates() {
    return array(
        'template_1' => array(
            'label'                   => 'Popup copy',
            'description'             => 'Osnovni popup s generičkim prodajnim copyjem i automatski izračunatim cijenama.',
            'badge_text'              => 'Ekskluzivna ponuda',
            'title_html'              => 'Prije završetka,<br>pogledaj ovu ponudu',
            'subtitle_html'           => 'Na ovom checkoutu možeš odmah prebaciti kupnju na <strong>povoljniju pretplatu</strong> uz posebnu ponudu dostupnu samo ovdje.',
            'body_html'               => '',
            'cta_label'               => 'Da, želim ovu ponudu',
            'skip_label'              => 'Ne, ostajem pri {{source_plan_locative_bare}}',
            'recommended_period_type' => '',
            'recommended_period'      => 0,
            'supports_manual_copy'    => false,
        ),
    );
}

function zaher_normalize_checkout_popup_title_html( $value ) {
    $value = (string) $value;
    $value = preg_replace( '#<\s*/p>\s*<\s*p[^>]*>\s*#i', '<br>', $value );
    $value = preg_replace( '#<\s*p[^>]*>\s*#i', '', $value );
    $value = preg_replace( '#\s*<\s*/p>\s*#i', '', $value );

    return trim( $value );
}

function zaher_merge_checkout_popup_content_html( $primary, $secondary ) {
    $parts = array();

    foreach ( array( $primary, $secondary ) as $value ) {
        $value = trim( (string) $value );

        if ( '' !== trim( wp_strip_all_tags( $value ) ) ) {
            $parts[] = $value;
        }
    }

    return implode( "\n", $parts );
}

function zaher_get_checkout_popup_custom_copy_field_map() {
    return array(
        'custom_title_html'    => 'title_html',
        'custom_subtitle_html' => 'subtitle_html',
    );
}

function zaher_get_checkout_popup_row_custom_copy( $row ) {
    $templates    = zaher_get_checkout_popup_templates();
    $template     = $templates[ zaher_get_checkout_popup_default_template_key() ];
    $field_map    = zaher_get_checkout_popup_custom_copy_field_map();
    $custom_copy  = array(
        'title_html'    => isset( $template['title_html'] ) ? (string) $template['title_html'] : '',
        'subtitle_html' => isset( $template['subtitle_html'] ) ? (string) $template['subtitle_html'] : '',
    );

    foreach ( $field_map as $row_key => $template_field ) {
        if ( is_array( $row ) && array_key_exists( $row_key, $row ) ) {
            $custom_copy[ $template_field ] = (string) $row[ $row_key ];
        }
    }

    if ( is_array( $row ) && array_key_exists( 'custom_body_html', $row ) ) {
        $custom_copy['subtitle_html'] = zaher_merge_checkout_popup_content_html( $custom_copy['subtitle_html'], $row['custom_body_html'] );
    }

    return $custom_copy;
}

function zaher_get_checkout_popup_template_choices() {
    $templates = zaher_get_checkout_popup_templates();
    $choices   = array();

    foreach ( $templates as $key => $template ) {
        $choices[ $key ] = array(
            'label'                 => isset( $template['label'] ) ? (string) $template['label'] : $key,
            'description'           => isset( $template['description'] ) ? (string) $template['description'] : '',
            'recommendedPeriodType' => isset( $template['recommended_period_type'] ) ? (string) $template['recommended_period_type'] : '',
            'recommendedPeriod'     => isset( $template['recommended_period'] ) ? (int) $template['recommended_period'] : 0,
            'supportsManualCopy'    => ! empty( $template['supports_manual_copy'] ),
            'badgeText'             => isset( $template['badge_text'] ) ? (string) $template['badge_text'] : '',
            'titleHtml'             => isset( $template['title_html'] ) ? (string) $template['title_html'] : '',
            'subtitleHtml'          => isset( $template['subtitle_html'] ) ? (string) $template['subtitle_html'] : '',
            'ctaLabel'              => isset( $template['cta_label'] ) ? (string) $template['cta_label'] : '',
            'skipLabel'             => isset( $template['skip_label'] ) ? (string) $template['skip_label'] : '',
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
            'enabled'           => 1,
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

function zaher_get_checkout_popup_plan_label( $product, $case = 'nominative' ) {
    if ( ! $product instanceof MeprProduct ) {
        switch ( $case ) {
            case 'accusative':
                return 'odabranu pretplatu';
            case 'locative_bare':
                return 'odabranoj pretplati';
            case 'locative':
                return 'na odabranoj pretplati';
            case 'genitive':
                return 'odabrane pretplate';
            case 'nominative':
            default:
                return 'odabrana pretplata';
        }
    }

    $labels = array(
        'months:1'  => array(
            'nominative' => 'mjesečna pretplata',
            'accusative' => 'mjesečnu pretplatu',
            'locative_bare' => 'mjesečnoj pretplati',
            'locative'   => 'na mjesečnoj pretplati',
            'genitive'   => 'mjesečne pretplate',
        ),
        'months:3'  => array(
            'nominative' => 'tromjesečna pretplata',
            'accusative' => 'tromjesečnu pretplatu',
            'locative_bare' => 'tromjesečnoj pretplati',
            'locative'   => 'na tromjesečnoj pretplati',
            'genitive'   => 'tromjesečne pretplate',
        ),
        'months:6'  => array(
            'nominative' => 'polugodišnja pretplata',
            'accusative' => 'polugodišnju pretplatu',
            'locative_bare' => 'polugodišnjoj pretplati',
            'locative'   => 'na polugodišnjoj pretplati',
            'genitive'   => 'polugodišnje pretplate',
        ),
        'months:12' => array(
            'nominative' => 'godišnja pretplata',
            'accusative' => 'godišnju pretplatu',
            'locative_bare' => 'godišnjoj pretplati',
            'locative'   => 'na godišnjoj pretplati',
            'genitive'   => 'godišnje pretplate',
        ),
        'years:1'   => array(
            'nominative' => 'godišnja pretplata',
            'accusative' => 'godišnju pretplatu',
            'locative_bare' => 'godišnjoj pretplati',
            'locative'   => 'na godišnjoj pretplati',
            'genitive'   => 'godišnje pretplate',
        ),
    );
    $key = $product->period_type . ':' . (int) $product->period;

    if ( isset( $labels[ $key ][ $case ] ) ) {
        return $labels[ $key ][ $case ];
    }

    return zaher_get_checkout_popup_plan_label( null, $case );
}

function zaher_format_checkout_popup_product_amount_text( $target_product, $amount ) {
    if ( ! $target_product instanceof MeprProduct ) {
        return '';
    }

    $amount_text = MeprAppHelper::format_currency( (float) $amount, true, false );

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

function zaher_get_checkout_popup_valid_coupon( $coupon_code, $target_product_id ) {
    $coupon_code = trim( (string) $coupon_code );

    if ( '' === $coupon_code || ! class_exists( 'MeprCoupon' ) || ! MeprCoupon::is_valid_coupon_code( $coupon_code, $target_product_id ) ) {
        return null;
    }

    $coupon = MeprCoupon::get_one_from_code( $coupon_code );

    if ( ! $coupon instanceof MeprCoupon || empty( $coupon->ID ) ) {
        return null;
    }

    return $coupon;
}

function zaher_get_checkout_popup_pricing_data( $target_product, $coupon_code = '' ) {
    $empty = array(
        'baseAmount'       => 0,
        'baseText'         => '',
        'displayAmount'    => 0,
        'displayText'      => '',
        'comparisonAmount' => 0,
        'comparisonHtml'   => '',
        'offerSummaryHtml' => '',
        'renewalText'      => '',
        'couponMode'       => '',
    );

    if ( ! $target_product instanceof MeprProduct ) {
        return $empty;
    }

    $base_amount = (float) $target_product->price;
    $base_text   = zaher_format_checkout_popup_product_amount_text( $target_product, $base_amount );
    $pricing     = array_merge(
        $empty,
        array(
            'baseAmount'       => $base_amount,
            'baseText'         => $base_text,
            'displayAmount'    => $base_amount,
            'displayText'      => $base_text,
            'comparisonAmount' => $base_amount,
            'comparisonHtml'   => '<strong>' . esc_html( $base_text ) . '</strong>',
            'offerSummaryHtml' => 'Na ovom checkoutu danas plaćaš <strong>' . esc_html( $base_text ) . '</strong>.',
            'renewalText'      => $base_text,
        )
    );
    $coupon      = zaher_get_checkout_popup_valid_coupon( $coupon_code, $target_product->ID );

    if ( ! $coupon ) {
        return $pricing;
    }

    $pricing['couponMode'] = (string) $coupon->discount_mode;

    if ( in_array( $pricing['couponMode'], array( 'trial-override', 'first-payment' ), true ) ) {
        $coupon_product = new MeprProduct( $target_product->ID );
        $coupon->maybe_apply_trial_override( $coupon_product );

        if ( ! empty( $coupon_product->trial ) ) {
            $display_amount = isset( $coupon_product->trial_amount ) ? (float) $coupon_product->trial_amount : 0;
            $display_text   = zaher_format_checkout_popup_product_amount_text( $target_product, $display_amount );

            $pricing['displayAmount']    = $display_amount;
            $pricing['displayText']      = $display_text;
            $pricing['comparisonAmount'] = $display_amount;
            $pricing['comparisonHtml']   = '<strong>' . esc_html( $display_text ) . '</strong>, poslije <strong>' . esc_html( $base_text ) . '</strong>';
            $pricing['offerSummaryHtml'] = 'Prvi obračun danas iznosi <strong>' . esc_html( $display_text ) . '</strong> umjesto redovne cijene od <strong>' . esc_html( $base_text ) . '</strong>.';
            $pricing['renewalText']      = $base_text;

            return $pricing;
        }
    }

    $display_amount = (float) $target_product->adjusted_price( $coupon_code, false );
    $display_text   = zaher_format_checkout_popup_product_amount_text( $target_product, $display_amount );

    $pricing['displayAmount']    = $display_amount;
    $pricing['displayText']      = $display_text;
    $pricing['comparisonAmount'] = $display_amount;

    if ( $display_text === $base_text ) {
        return $pricing;
    }

    $pricing['comparisonHtml']   = '<strong>' . esc_html( $display_text ) . '</strong> umjesto <strong>' . esc_html( $base_text ) . '</strong>';
    $pricing['offerSummaryHtml'] = 'Na ovom checkoutu danas plaćaš <strong>' . esc_html( $display_text ) . '</strong> umjesto redovne cijene od <strong>' . esc_html( $base_text ) . '</strong>.';

    return $pricing;
}

function zaher_get_checkout_popup_new_price_text( $target_product, $coupon_code = '' ) {
    $pricing = zaher_get_checkout_popup_pricing_data( $target_product, $coupon_code );

    return isset( $pricing['displayText'] ) ? (string) $pricing['displayText'] : '';
}

function zaher_get_checkout_popup_savings_text( $source_product, $target_product, $coupon_code = '' ) {
    if ( ! $target_product instanceof MeprProduct ) {
        return '';
    }

    $pricing          = zaher_get_checkout_popup_pricing_data( $target_product, $coupon_code );
    $reference_amount = zaher_get_checkout_popup_reference_price_amount( $source_product, $target_product );
    $savings_amount   = max( 0, $reference_amount - (float) $pricing['comparisonAmount'] );

    if ( $savings_amount <= 0 ) {
        return '';
    }

    return MeprAppHelper::format_currency( $savings_amount, true, false );
}

function zaher_get_checkout_popup_price_comparison_html( $target_product, $coupon_code = '' ) {
    $pricing = zaher_get_checkout_popup_pricing_data( $target_product, $coupon_code );

    if ( empty( $pricing['comparisonHtml'] ) ) {
        return '';
    }

    return (string) $pricing['comparisonHtml'];
}

function zaher_get_checkout_popup_offer_summary_html( $target_product, $coupon_code = '' ) {
    $pricing = zaher_get_checkout_popup_pricing_data( $target_product, $coupon_code );

    if ( empty( $pricing['offerSummaryHtml'] ) ) {
        return '';
    }

    return (string) $pricing['offerSummaryHtml'];
}

function zaher_get_checkout_popup_value_sentence_html( $source_product, $target_product, $coupon_code = '' ) {
    if ( ! $target_product instanceof MeprProduct ) {
        return '';
    }

    $pricing      = zaher_get_checkout_popup_pricing_data( $target_product, $coupon_code );
    $savings_text = zaher_get_checkout_popup_savings_text( $source_product, $target_product, $coupon_code );
    $sentence     = '';

    if ( 'first-payment' === $pricing['couponMode'] || 'trial-override' === $pricing['couponMode'] ) {
        $sentence = 'Na ovom checkoutu prvi obračun plaćaš <strong>' . esc_html( $pricing['displayText'] ) . '</strong> umjesto <strong>' . esc_html( $pricing['baseText'] ) . '</strong>';
    } elseif ( $pricing['displayText'] !== $pricing['baseText'] ) {
        $sentence = 'Na ovom checkoutu plaćaš <strong>' . esc_html( $pricing['displayText'] ) . '</strong> umjesto <strong>' . esc_html( $pricing['baseText'] ) . '</strong>';
    } else {
        $sentence = 'Na ovom checkoutu odmah prelaziš na <strong>' . esc_html( zaher_get_checkout_popup_plan_label( $target_product, 'accusative' ) ) . '</strong>';
    }

    if ( '' !== $savings_text ) {
        $sentence .= ', a kroz isti period štediš <strong>' . esc_html( $savings_text ) . '</strong> u odnosu na ostanak ' . esc_html( zaher_get_checkout_popup_plan_label( $source_product, 'locative' ) );
    }

    return $sentence . '.';
}

function zaher_get_checkout_popup_savings_sentence_html( $source_product, $target_product, $coupon_code = '' ) {
    $savings_text = zaher_get_checkout_popup_savings_text( $source_product, $target_product, $coupon_code );

    if ( '' === $savings_text ) {
        return '';
    }

    return ' U odnosu na ostanak ' . esc_html( zaher_get_checkout_popup_plan_label( $source_product, 'locative' ) ) . ' kroz isti period štediš <strong>' . esc_html( $savings_text ) . '</strong>.';
}

function zaher_get_checkout_popup_equivalent_source_period_price_text( $source_product, $target_product, $amount ) {
    if ( ! $source_product instanceof MeprProduct || ! $target_product instanceof MeprProduct ) {
        return '';
    }

    $ratio = zaher_get_checkout_popup_period_ratio( $source_product, $target_product );

    if ( $ratio <= 1 ) {
        return '';
    }

    return zaher_format_checkout_popup_product_amount_text( $source_product, (float) $amount / $ratio );
}

function zaher_get_checkout_popup_vs_current_plan_benefit_html( $source_product, $target_product, $coupon_code = '' ) {
    $savings_text = zaher_get_checkout_popup_savings_text( $source_product, $target_product, $coupon_code );

    if ( '' === $savings_text ) {
        return 'U odnosu na ostanak ' . esc_html( zaher_get_checkout_popup_plan_label( $source_product, 'locative' ) ) . ' kroz isti period, ovo je isplativiji start.';
    }

    return 'U odnosu na ostanak ' . esc_html( zaher_get_checkout_popup_plan_label( $source_product, 'locative' ) ) . ' kroz isti period štediš <strong>' . esc_html( $savings_text ) . '</strong>.';
}

function zaher_get_checkout_popup_price_box_data( $source_product, $target_product, $coupon_code = '' ) {
    $pricing                  = zaher_get_checkout_popup_pricing_data( $target_product, $coupon_code );
    $savings_text             = zaher_get_checkout_popup_savings_text( $source_product, $target_product, $coupon_code );
    $price_box                = array(
        'kicker'           => '',
        'oldPriceLabel'    => '',
        'oldPrice'         => '',
        'newPriceLabel'    => '',
        'newPrice'         => isset( $pricing['displayText'] ) ? (string) $pricing['displayText'] : '',
        'renewalNote'      => '',
        'benefitPrimary'   => '',
        'benefitSecondary' => '',
    );

    if ( ! empty( $pricing['displayText'] ) && $pricing['displayText'] !== $pricing['baseText'] ) {
        $price_box['oldPrice']      = isset( $pricing['baseText'] ) ? (string) $pricing['baseText'] : '';
    }

    if ( 'first-payment' === $pricing['couponMode'] || 'trial-override' === $pricing['couponMode'] ) {
        if ( ! empty( $pricing['renewalText'] ) ) {
            $price_box['renewalNote'] = 'Popust vrijedi za prvi obračun. Nakon toga ' . $pricing['renewalText'] . '.';
        }
    }

    if ( '' !== $savings_text ) {
        $price_box['benefitPrimary'] = 'Štediš ' . $savings_text . ' kroz isti period.';
    }

    return $price_box;
}

function zaher_get_checkout_popup_template_content( $template_key, $source_product, $target_product, $coupon_code = '', $custom_copy = array() ) {
    $templates     = zaher_get_checkout_popup_templates();
    $default_key   = zaher_get_checkout_popup_default_template_key();
    $template_key  = isset( $templates[ $template_key ] ) ? $template_key : $default_key;
    $template      = $templates[ $template_key ];
    $content       = array(
        'title_html'    => isset( $template['title_html'] ) ? (string) $template['title_html'] : '',
        'subtitle_html' => isset( $template['subtitle_html'] ) ? (string) $template['subtitle_html'] : '',
    );
    $target_title  = $target_product instanceof MeprProduct ? get_the_title( $target_product->ID ) : '';
    $replacements  = array(
        '{{target_title}}'          => esc_html( $target_title ),
        '{{target_plan_nominative}}' => esc_html( zaher_get_checkout_popup_plan_label( $target_product, 'nominative' ) ),
        '{{target_plan_accusative}}' => esc_html( zaher_get_checkout_popup_plan_label( $target_product, 'accusative' ) ),
        '{{target_plan_genitive}}'   => esc_html( zaher_get_checkout_popup_plan_label( $target_product, 'genitive' ) ),
        '{{source_plan_locative_bare}}' => esc_html( zaher_get_checkout_popup_plan_label( $source_product, 'locative_bare' ) ),
        '{{source_plan_locative}}'   => esc_html( zaher_get_checkout_popup_plan_label( $source_product, 'locative' ) ),
        '{{value_sentence_html}}'    => zaher_get_checkout_popup_value_sentence_html( $source_product, $target_product, $coupon_code ),
        '{{price_comparison_html}}' => zaher_get_checkout_popup_price_comparison_html( $target_product, $coupon_code ),
        '{{offer_summary_html}}'    => zaher_get_checkout_popup_offer_summary_html( $target_product, $coupon_code ),
        '{{savings_text}}'          => esc_html( zaher_get_checkout_popup_savings_text( $source_product, $target_product, $coupon_code ) ),
        '{{savings_sentence_html}}' => zaher_get_checkout_popup_savings_sentence_html( $source_product, $target_product, $coupon_code ),
        '{{vs_current_plan_benefit_html}}' => zaher_get_checkout_popup_vs_current_plan_benefit_html( $source_product, $target_product, $coupon_code ),
        '{{savings_suffix}}'        => '',
    );

    foreach ( $content as $key => $value ) {
        if ( is_array( $custom_copy ) && array_key_exists( $key, $custom_copy ) ) {
            $content[ $key ] = (string) $custom_copy[ $key ];
        }
    }

    return array(
        'key'          => $template_key,
        'label'        => isset( $template['label'] ) ? (string) $template['label'] : $template_key,
        'badgeText'    => wp_strip_all_tags( strtr( (string) $template['badge_text'], $replacements ) ),
        'titleHtml'    => wp_kses_post( zaher_normalize_checkout_popup_title_html( strtr( $content['title_html'], $replacements ) ) ),
        'subtitleHtml' => wp_kses_post( strtr( $content['subtitle_html'], $replacements ) ),
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
    $template_key   = zaher_get_checkout_popup_default_template_key();
    $source_product = zaher_get_checkout_popup_product( isset( $row['source_product_id'] ) ? $row['source_product_id'] : 0 );
    $target_product = zaher_get_checkout_popup_product( isset( $row['target_product_id'] ) ? $row['target_product_id'] : 0 );
    $coupon_code    = isset( $row['coupon_code'] ) ? sanitize_text_field( $row['coupon_code'] ) : '';
    $custom_copy    = zaher_get_checkout_popup_row_custom_copy( $row );
    $enabled        = ! isset( $row['enabled'] ) || ! empty( $row['enabled'] );

    if ( ! $enabled ) {
        return null;
    }

    if ( ! $source_product || ! $target_product ) {
        return null;
    }

    if ( $coupon_code && class_exists( 'MeprCoupon' ) && ! MeprCoupon::is_valid_coupon_code( $coupon_code, $target_product->ID ) ) {
        $coupon_code = '';
    }

    $template_content = zaher_get_checkout_popup_template_content( $template_key, $source_product, $target_product, $coupon_code, $custom_copy );
    $pricing_data     = zaher_get_checkout_popup_pricing_data( $target_product, $coupon_code );
    $price_box        = zaher_get_checkout_popup_price_box_data( $source_product, $target_product, $coupon_code );
    $target_url       = zaher_get_checkout_popup_target_url( $target_product, $coupon_code );
    $old_price        = isset( $price_box['oldPrice'] ) ? (string) $price_box['oldPrice'] : '';
    $new_price        = isset( $pricing_data['displayText'] ) ? (string) $pricing_data['displayText'] : '';
    $offer_version    = sha1(
        wp_json_encode(
            array(
                'source_product_id' => (int) $source_product->ID,
                'target_product_id' => (int) $target_product->ID,
                'target_url'        => $target_url,
                'old_price'         => $old_price,
                'new_price'         => $new_price,
                'price_box'         => $price_box,
                'template'          => $template_content,
            )
        )
    );

    return array(
        'sourceProductId'  => (int) $source_product->ID,
        'sourceUrl'        => $source_product->url(),
        'targetProductId'  => (int) $target_product->ID,
        'offerVersion'     => $offer_version,
        'template'         => $template_content,
        'targetUrl'        => $target_url,
        'oldPrice'         => $old_price,
        'newPrice'         => $new_price,
        'priceBox'         => $price_box,
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
            'popups' => $popup_configs,
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
