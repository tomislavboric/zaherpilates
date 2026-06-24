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

/**
 * Allow members to upgrade/downgrade their plan at any time, even while still
 * inside the pro-rated trial window created by a previous plan change.
 *
 * By default MemberPress blocks a new upgrade/downgrade when the member's
 * current subscription in the group is a pro-rated trial that is still running
 * (see MeprProduct::can_you_buy_me(), the prorated_trial + in_trial() guard).
 * That is what produced the "You don't have access to purchase this item."
 * message for an active member trying to change plans shortly after a previous
 * change. Our business rule is that plan changes should always be available, so
 * we lift that restriction.
 *
 * @param bool $allow Whether MemberPress permits the change (default false).
 * @return bool
 */
add_filter( 'mepr-allow-multiple-upgrades-downgrades', '__return_true' );

/**
 * Credit the unused portion of the current plan when changing plans within a
 * group, and charge only the difference up front.
 *
 * With the group's "Reset billing period" enabled, MemberPress' default
 * proration prorates BOTH sides over the new (full) period, which collapses the
 * credit to zero and charges the full new-plan price. That ignores what the
 * member already paid for the days they have NOT yet used on their current plan.
 *
 * Our business rule: credit the value of the days still remaining on the current
 * plan — `current_period_price * (days_left / days_in_period)` — and charge
 * `new_plan_price - credit` today. The new plan then renews at its normal price.
 *
 * The three values we need are already provided to this filter:
 *   - $old_amount    : the current plan's single-period price (what was paid for
 *                      the running period, or its trial amount while in trial),
 *   - $old_period    : the length of the current period in days,
 *   - $old_days_left : the unused (remaining) days of the current period.
 *
 * Hooking `mepr-proration` covers both the displayed checkout total and the
 * actual first charge taken by the gateway, because both derive from the
 * subscription's trial_amount produced here. See MeprSubscription::maybe_prorate().
 *
 * @param object             $prorations Object with `proration` (float) and `days` (int).
 * @param float              $old_amount Old plan single-period amount.
 * @param float              $new_amount New plan price (the up-front reference amount).
 * @param int|string         $old_period Old period length in days (or 'lifetime').
 * @param int|string         $new_period New period length in days (or 'lifetime').
 * @param int|string         $old_days_left Unused days left on the old subscription.
 * @param MeprSubscription|false $old_sub The current/old subscription.
 * @param MeprSubscription|false $new_sub The new subscription being purchased.
 * @param bool               $reset_period Whether the group resets the billing period.
 * @return object
 */
function theme_proration_credit_unused_days(
	$prorations,
	$old_amount,
	$new_amount,
	$old_period,
	$new_period,
	$old_days_left,
	$old_sub,
	$new_sub,
	$reset_period
) {
	// TEMP DEBUG: confirm the filter fires and with what values. Remove after diagnosis.
	theme_proration_debug_log( 'mepr-proration FIRED', array(
		'old_amount'    => $old_amount,
		'new_amount'    => $new_amount,
		'old_period'    => $old_period,
		'new_period'    => $new_period,
		'old_days_left' => $old_days_left,
		'old_sub_id'    => ( $old_sub instanceof MeprSubscription ) ? $old_sub->id : 'not-a-sub',
		'reset_period'  => $reset_period,
		'default_in'    => isset( $prorations->proration ) ? $prorations->proration : null,
	) );

	// Only handle recurring-to-recurring changes where we have a real old sub
	// and numeric periods to prorate over.
	if ( ! ( $old_sub instanceof MeprSubscription ) || empty( $old_sub->id ) ) {
		theme_proration_debug_log( 'mepr-proration BAILED: no old sub', array() );
		return $prorations;
	}

	if ( ! is_numeric( $old_period ) || (int) $old_period <= 0 || ! is_numeric( $old_days_left ) ) {
		theme_proration_debug_log( 'mepr-proration BAILED: bad period/days', array( 'old_period' => $old_period, 'old_days_left' => $old_days_left ) );
		return $prorations;
	}

	// The override only takes effect when days > 0 (see maybe_prorate()), so the
	// prorated first charge needs a positive period to attach to.
	$days = is_numeric( $new_period ) ? (int) $new_period : (int) ( $prorations->days ?? 0 );

	if ( $days <= 0 ) {
		theme_proration_debug_log( 'mepr-proration BAILED: days <= 0', array( 'days' => $days ) );
		return $prorations;
	}

	// Value of the days the member has NOT yet used on the current plan, clamped
	// so we never credit more than a full period.
	$days_left = max( 0, min( (int) $old_days_left, (int) $old_period ) );
	$credit    = (float) $old_amount * ( $days_left / (int) $old_period );

	// Charge the new plan price minus that credit, never below zero (Stripe
	// rejects negative amounts).
	$prorations->proration = max( (float) $new_amount - $credit, 0.00 );
	$prorations->days      = $days;

	theme_proration_debug_log( 'mepr-proration APPLIED', array(
		'credit'       => $credit,
		'new_proration'=> $prorations->proration,
		'days'         => $days,
	) );

	return $prorations;
}

add_filter( 'mepr-proration', 'theme_proration_credit_unused_days', 10, 9 );

/**
 * TEMP DEBUG: write a line to a debug file in uploads. Remove with the rest of
 * the temporary proration debugging once diagnosed.
 */
function theme_proration_debug_log( $label, $data ) {
	$upload = wp_upload_dir();
	$file   = trailingslashit( $upload['basedir'] ) . 'proration-debug.log';
	$line   = '[' . gmdate( 'Y-m-d H:i:s' ) . '] ' . $label . ' ' . wp_json_encode( $data ) . "\n";
	@file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
}

/**
 * TEMP DEBUG: on a MemberPress checkout page, log whether MemberPress would even
 * run proration for the current logged-in member (the maybe_prorate gate). This
 * catches the case where the mepr-proration filter never fires. Remove after
 * diagnosis.
 */
add_action(
	'wp',
	function () {
		if ( is_admin() || ! is_user_logged_in() ) {
			return;
		}
		if ( ! function_exists( 'theme_is_memberpress_checkout_context' ) || ! theme_is_memberpress_checkout_context() ) {
			return;
		}
		if ( ! class_exists( 'MeprProduct' ) || ! is_singular( MeprProduct::$cpt ) ) {
			return;
		}

		$product = new MeprProduct( get_queried_object_id() );
		$user    = MeprUtils::get_currentuserinfo();
		$opts    = MeprOptions::fetch();

		$info = array(
			'product_id'         => $product->ID,
			'product'            => $product->post_title,
			'pro_rated_upgrades' => $opts->pro_rated_upgrades,
			'user_id'            => $user ? $user->ID : 0,
		);

		$group = method_exists( $product, 'group' ) ? $product->group() : false;
		$info['group_id']         = $group ? $group->ID : 'none';
		$info['group_is_up_path'] = ( $group && method_exists( $group, 'is_upgrade_path' ) ) ? $group->is_upgrade_path() : 'n/a';

		if ( $user && $group ) {
			$sig = $user->subscription_in_group( $group->ID );
			if ( $sig instanceof MeprSubscription ) {
				$info['sub_in_group_id']     = $sig->id;
				$info['sub_in_group_status'] = $sig->status;
				$info['sub_in_group_product']= $sig->product_id;
				$info['sub_in_free_trial']   = method_exists( $sig, 'in_free_trial' ) ? $sig->in_free_trial() : 'n/a';
				$info['sub_days_in_period']  = method_exists( $sig, 'days_in_this_period' ) ? $sig->days_in_this_period() : 'n/a';
				$info['sub_days_left']       = method_exists( $sig, 'days_till_expiration' ) ? $sig->days_till_expiration() : 'n/a';
			} else {
				$info['sub_in_group'] = 'NONE FOUND';
			}
		}

		// All of the user's subscriptions, to see status/group of the current one.
		if ( $user && method_exists( $user, 'subscriptions' ) ) {
			$subs = array();
			foreach ( (array) $user->subscriptions() as $s ) {
				$so = ( $s instanceof MeprSubscription ) ? $s : new MeprSubscription( is_object( $s ) ? $s->id : $s );
				$subs[] = array( 'id' => $so->id, 'product' => $so->product_id, 'status' => $so->status );
			}
			$info['all_subs'] = $subs;
		}

		theme_proration_debug_log( 'CHECKOUT GATE', $info );
	},
	999
);

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
