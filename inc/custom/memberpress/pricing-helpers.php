<?php
/**
 * MemberPress pricing & price-box helpers.
 */

function theme_get_billing_period_text( $product ) {
    if ( ! $product instanceof MeprProduct || $product->is_one_time_payment() ) {
        return '';
    }

    $period      = (int) $product->period;
    $period_type = (string) $product->period_type;

    switch ( $period_type ) {
        case 'months':
            if ( 1 === $period ) {
                return __( 'Naplaćuje se mjesečno', 'foundationpress' );
            }

            if ( 12 === $period ) {
                return __( 'Naplaćuje se godišnje', 'foundationpress' );
            }

            if ( $period >= 2 && $period <= 4 ) {
                /* translators: %d: number of months between charges */
                return sprintf( __( 'Naplaćuje se svaka %d mjeseca', 'foundationpress' ), $period );
            }

            /* translators: %d: number of months between charges */
            return sprintf( __( 'Naplaćuje se svakih %d mjeseci', 'foundationpress' ), $period );

        case 'years':
            if ( 1 === $period ) {
                return __( 'Naplaćuje se godišnje', 'foundationpress' );
            }

            /* translators: %d: number of years between charges */
            return sprintf( __( 'Naplaćuje se svake %d godine', 'foundationpress' ), $period );

        case 'weeks':
            if ( 1 === $period ) {
                return __( 'Naplaćuje se tjedno', 'foundationpress' );
            }

            /* translators: %d: number of weeks between charges */
            return sprintf( __( 'Naplaćuje se svakih %d tjedana', 'foundationpress' ), $period );

        case 'days':
            if ( 1 === $period ) {
                return __( 'Naplaćuje se dnevno', 'foundationpress' );
            }

            /* translators: %d: number of days between charges */
            return sprintf( __( 'Naplaćuje se svakih %d dana', 'foundationpress' ), $period );
    }

    return '';
}

function theme_get_pricing_price_term( $product ) {
    if ( ! $product instanceof MeprProduct || $product->is_one_time_payment() ) {
        return '';
    }

    $period = max( 1, (int) $product->period );

    switch ( (string) $product->period_type ) {
        case 'months':
            if ( 1 === $period ) {
                return __( 'mjesec', 'foundationpress' );
            }

            if ( $period >= 2 && $period <= 4 ) {
                /* translators: %d: number of months in plan period */
                return sprintf( __( '%d mjeseca', 'foundationpress' ), $period );
            }

            /* translators: %d: number of months in plan period */
            return sprintf( __( '%d mjeseci', 'foundationpress' ), $period );

        case 'years':
            if ( 1 === $period ) {
                return __( 'godina', 'foundationpress' );
            }

            if ( $period >= 2 && $period <= 4 ) {
                /* translators: %d: number of years in plan period */
                return sprintf( __( '%d godine', 'foundationpress' ), $period );
            }

            /* translators: %d: number of years in plan period */
            return sprintf( __( '%d godina', 'foundationpress' ), $period );

        case 'weeks':
            if ( 1 === $period ) {
                return __( 'tjedan', 'foundationpress' );
            }

            /* translators: %d: number of weeks in plan period */
            return sprintf( __( '%d tjedana', 'foundationpress' ), $period );

        case 'days':
            if ( 1 === $period ) {
                return __( 'dan', 'foundationpress' );
            }

            /* translators: %d: number of days in plan period */
            return sprintf( __( '%d dana', 'foundationpress' ), $period );
    }

    return '';
}

function theme_get_checkout_benefits( $product ) {
    $benefits = array();

    if ( $product instanceof MeprProduct && is_array( $product->pricing_benefits ) ) {
        foreach ( $product->pricing_benefits as $raw_benefit ) {
            $raw_benefit = trim( (string) $raw_benefit );

            if ( '' === $raw_benefit ) {
                continue;
            }

            $plain = wp_strip_all_tags( $raw_benefit );

            if ( preg_match( '/^\s*(?:\[note\]|\[price-note\]|\[cijena\])\s*/iu', $plain ) ) {
                continue;
            }

            if ( preg_match( '/^\s*(?:\[x\]|\[no\]|x\s|×|✕|-|–)\s*/iu', $plain ) ) {
                continue;
            }

            $benefits[] = theme_format_price_box_benefit_text( $raw_benefit );
        }
    }

    if ( empty( $benefits ) ) {
        $is_recurring = $product instanceof MeprProduct && method_exists( $product, 'is_one_time_payment' )
            ? ! $product->is_one_time_payment()
            : false;
        $has_trial = $product instanceof MeprProduct && ! empty( $product->trial ) && (int) $product->trial_days > 0;

        $benefits[] = esc_html__( '200+ treninga po fazama ciklusa', 'foundationpress' );
        $benefits[] = esc_html__( 'Sve kategorije + live događanja', 'foundationpress' );

        if ( $has_trial ) {
            $trial_days = (int) $product->trial_days;
            $benefits[] = esc_html(
                sprintf(
                    /* translators: %d: number of trial days */
                    _n( '%d dan probnog razdoblja uključen', '%d dana probnog razdoblja uključeno', $trial_days, 'foundationpress' ),
                    $trial_days
                )
            );
        }

        if ( $is_recurring ) {
            $benefits[] = esc_html__( 'Otkaži u bilo kojem trenutku', 'foundationpress' );
        }
    }

    $custom = $product instanceof MeprProduct ? get_post_meta( $product->ID, '_theme_checkout_benefits', true ) : '';

    if ( is_string( $custom ) && '' !== trim( $custom ) ) {
        $lines     = preg_split( "/\r\n|\r|\n/", $custom );
        $lines     = array_values( array_filter( array_map( 'trim', (array) $lines ) ) );
        $overrides = array();

        foreach ( $lines as $line ) {
            $overrides[] = theme_format_price_box_benefit_text( $line );
        }

        if ( ! empty( $overrides ) ) {
            $benefits = $overrides;
        }
    }

    return apply_filters( 'theme_checkout_benefits', $benefits, $product );
}

function theme_format_price_box_benefit_text( $benefit ) {
    $benefit = trim( wp_strip_all_tags( (string) $benefit ) );
    $parts   = preg_split( '/(\*\*.*?\*\*)/u', $benefit, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
    $html    = '';

    foreach ( $parts as $part ) {
        if ( preg_match( '/^\*\*(.*?)\*\*$/u', $part, $matches ) ) {
            $html .= '<strong>' . esc_html( $matches[1] ) . '</strong>';
        } else {
            $html .= esc_html( $part );
        }
    }

    return $html;
}

function theme_get_plan_months_count( $product ) {
    if ( ! $product instanceof MeprProduct || $product->is_one_time_payment() ) {
        return 0;
    }

    $period = (int) $product->period;

    if ( $period <= 0 ) {
        return 0;
    }

    switch ( (string) $product->period_type ) {
        case 'months':
            return $period;
        case 'years':
            return $period * 12;
        case 'weeks':
            return (int) round( $period / 4.345 );
        case 'days':
            return (int) round( $period / 30.4 );
    }

    return 0;
}

function theme_user_has_upgrade_available() {
    if ( ! is_user_logged_in() || ! class_exists( 'MeprProduct' ) ) {
        return false;
    }

    $sub_data = theme_get_user_active_subscription_data();

    if ( empty( $sub_data['active_ids'] ) || ! $sub_data['primary_product'] instanceof MeprProduct ) {
        return false;
    }

    $current = $sub_data['primary_product'];
    $group   = method_exists( $current, 'group' ) ? $current->group() : null;

    if ( ! $group instanceof MeprGroup ) {
        return false;
    }

    $products = $group->products();

    if ( empty( $products ) ) {
        return false;
    }

    $current_months = theme_get_plan_months_count( $current );

    foreach ( $products as $product ) {
        if ( ! $product instanceof MeprProduct || (int) $product->ID === (int) $current->ID ) {
            continue;
        }

        if ( 'publish' !== get_post_status( $product->ID ) ) {
            continue;
        }

        if ( theme_get_plan_months_count( $product ) > $current_months ) {
            return true;
        }
    }

    return false;
}

function theme_get_plan_monthly_price( $product ) {
    if ( ! $product instanceof MeprProduct || $product->is_one_time_payment() ) {
        return 0.0;
    }

    $period = (int) $product->period;

    if ( $period <= 0 ) {
        return 0.0;
    }

    $months = 0.0;

    switch ( (string) $product->period_type ) {
        case 'months':
            $months = (float) $period;
            break;
        case 'years':
            $months = (float) $period * 12.0;
            break;
        case 'weeks':
            $months = (float) $period / 4.345;
            break;
        case 'days':
            $months = (float) $period / 30.4;
            break;
    }

    if ( $months <= 0 ) {
        return 0.0;
    }

    return (float) $product->price / $months;
}

function theme_format_eur_amount( $amount ) {
    return '€' . number_format( (float) $amount, 2, ',', '.' );
}

function theme_get_plan_savings_text( $current_product, $other_product ) {
    $current_monthly = theme_get_plan_monthly_price( $current_product );
    $other_monthly   = theme_get_plan_monthly_price( $other_product );

    if ( $current_monthly <= 0 || $other_monthly <= 0 ) {
        return '';
    }

    $monthly_savings = $current_monthly - $other_monthly;

    if ( $monthly_savings <= 0.005 ) {
        return '';
    }

    $annual_savings = $monthly_savings * 12.0;

    /* translators: %s: amount saved per year */
    return sprintf( __( 'Štediš %s godišnje', 'foundationpress' ), theme_format_eur_amount( $annual_savings ) );
}

/**
 * Compute the prorated "due today" amount for the current member switching to
 * $product, exactly as the checkout will charge it.
 *
 * Builds a throwaway subscription for the target product owned by the current
 * user and runs MemberPress' own maybe_prorate(), which goes through the same
 * mepr-proration filter as checkout — so the displayed amount matches the
 * checkout total to the cent. Returns null when no proration applies (e.g. not
 * an in-group upgrade/downgrade, or member would pay full price anyway).
 *
 * @param MeprProduct $product Target plan.
 * @return array|null { amount: float, days: int, full_price: float } or null.
 */
function theme_get_plan_proration_for_current_user( $product ) {
    if ( ! ( $product instanceof MeprProduct ) || empty( $product->ID ) ) {
        return null;
    }

    if ( ! is_user_logged_in() || ! class_exists( 'MeprSubscription' ) || ! class_exists( 'MeprUtils' ) ) {
        return null;
    }

    $user = MeprUtils::get_currentuserinfo();

    if ( ! $user || empty( $user->ID ) ) {
        return null;
    }

    // Mirror the checkout: a fresh subscription for this product, owned by the
    // current user, then let MemberPress prorate it.
    $sub             = new MeprSubscription();
    $sub->user_id    = (int) $user->ID;
    $sub->product_id = (int) $product->ID;
    $sub->price      = (float) $product->price;
    $sub->period     = $product->period;
    $sub->period_type = $product->period_type;

    if ( ! method_exists( $sub, 'maybe_prorate' ) ) {
        return null;
    }

    $sub->maybe_prorate();

    // maybe_prorate() only sets a prorated trial when proration actually applies.
    if ( empty( $sub->prorated_trial ) || (int) $sub->trial_days <= 0 ) {
        return null;
    }

    $amount = isset( $sub->trial_total ) ? (float) $sub->trial_total : (float) $sub->trial_amount;

    return array(
        'amount'     => $amount,
        'days'       => (int) $sub->trial_days,
        'full_price' => (float) $product->price,
    );
}

function theme_get_user_active_subscription_data() {
    $data = array(
        'is_logged_in'    => false,
        'active_ids'      => array(),
        'primary_product' => null,
        'next_billing'    => '',
        'period_end'      => '',
        'sub_status'      => '',
        'is_cancelled'    => false,
        'account_url'     => '',
    );

    if ( ! is_user_logged_in() || ! class_exists( 'MeprUtils' ) ) {
        return $data;
    }

    $user = MeprUtils::get_currentuserinfo();

    if ( ! $user || empty( $user->ID ) ) {
        return $data;
    }

    $data['is_logged_in'] = true;
    $active_ids           = $user->active_product_subscriptions( 'ids' );
    $data['active_ids']   = array_values( array_unique( array_map( 'intval', (array) $active_ids ) ) );

    if ( ! empty( $data['active_ids'] ) ) {
        $first_id = $data['active_ids'][0];
        $product  = new MeprProduct( $first_id );

        if ( ! empty( $product->ID ) ) {
            $data['primary_product'] = $product;
        }

        $txns = $user->active_product_subscriptions( 'transactions' );

        if ( is_array( $txns ) && ! empty( $txns ) ) {
            foreach ( $txns as $txn ) {
                if ( (int) $txn->product_id !== $first_id ) {
                    continue;
                }

                if ( ! empty( $txn->subscription_id ) && class_exists( 'MeprSubscription' ) ) {
                    $sub = new MeprSubscription( (int) $txn->subscription_id );

                    if ( $sub && ! empty( $sub->id ) ) {
                        $data['sub_status'] = isset( $sub->status ) ? (string) $sub->status : '';

                        if ( MeprSubscription::$cancelled_str === $data['sub_status'] ) {
                            $data['is_cancelled'] = true;
                        }
                    }
                }

                if ( empty( $txn->expires_at ) || '0000-00-00 00:00:00' === $txn->expires_at ) {
                    break;
                }

                $timestamp = strtotime( $txn->expires_at );

                if ( $timestamp ) {
                    $formatted          = date_i18n( 'd.m.Y', $timestamp );
                    $data['period_end'] = $formatted;

                    if ( ! $data['is_cancelled'] ) {
                        $data['next_billing'] = $formatted;
                    }
                }

                break;
            }
        }
    }

    $data['account_url'] = function_exists( 'theme_account_tab_url' )
        ? theme_account_tab_url( 'subscription' )
        : home_url( '/moj-racun/?tab=subscription' );

    return $data;
}

function theme_render_pricing_status_bar() {
    $data = theme_get_user_active_subscription_data();

    if ( ! $data['is_logged_in'] || empty( $data['active_ids'] ) || ! $data['primary_product'] instanceof MeprProduct ) {
        return '';
    }

    $plan_title   = get_the_title( $data['primary_product']->ID );
    $count        = count( $data['active_ids'] );
    $extra_count  = $count - 1;
    $manage_url   = $data['account_url'] ? $data['account_url'] : home_url( '/' );
    $is_cancelled = ! empty( $data['is_cancelled'] );

    ob_start();
    ?>
    <div class="pricing-status-bar" role="status">
        <div class="pricing-status-bar__icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M9 12l2 2 4-4"></path>
                <circle cx="12" cy="12" r="10"></circle>
            </svg>
        </div>
        <div class="pricing-status-bar__copy">
            <p class="pricing-status-bar__label">
                <?php esc_html_e( 'Tvoja aktivna pretplata', 'foundationpress' ); ?>
            </p>
            <p class="pricing-status-bar__plan">
                <strong><?php echo esc_html( $plan_title ); ?></strong>
                <?php if ( $extra_count > 0 ) : ?>
                    <span class="pricing-status-bar__extra">
                        <?php
                        printf(
                            /* translators: %d: number of additional active plans */
                            esc_html( _n( '+ još %d aktivan plan', '+ još %d aktivnih planova', $extra_count, 'foundationpress' ) ),
                            $extra_count
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </p>
            <?php
            $meta_date = $is_cancelled ? $data['period_end'] : $data['next_billing'];
            if ( ! empty( $meta_date ) ) :
                ?>
                <p class="pricing-status-bar__meta">
                    <?php
                    if ( $is_cancelled ) {
                        printf(
                            /* translators: %s: date subscription ends, in d.m.Y format */
                            esc_html__( 'Završava %s', 'foundationpress' ),
                            '<strong>' . esc_html( $meta_date ) . '</strong>'
                        );
                    } else {
                        printf(
                            /* translators: %s: next billing date in d.m.Y format */
                            esc_html__( 'Sljedeći obračun: %s', 'foundationpress' ),
                            '<strong>' . esc_html( $meta_date ) . '</strong>'
                        );
                    }
                    ?>
                </p>
            <?php endif; ?>
        </div>
        <a class="pricing-status-bar__cta" href="<?php echo esc_url( $manage_url ); ?>">
            <?php esc_html_e( 'Upravljaj pretplatom', 'foundationpress' ); ?>
            <span aria-hidden="true">→</span>
        </a>
    </div>
    <?php

    return (string) ob_get_clean();
}

add_filter(
    'mepr-group-page-item-output',
    function( $output, $product = null, $group = null, $preview = false ) {
        static $is_first = true;

        $price_note = '';
        $prefix     = '';

        if ( $is_first ) {
            $prefix   = theme_render_pricing_status_bar();
            $is_first = false;
        }

        $subscription_data = theme_get_user_active_subscription_data();
        $is_current_plan   = $product instanceof MeprProduct
            && in_array( (int) $product->ID, $subscription_data['active_ids'], true );

        if ( $is_current_plan ) {
            $output = preg_replace(
                '/class="mepr-price-box([^"]*)"/',
                'class="mepr-price-box is-current-plan$1"',
                $output,
                1
            );

            $manage_url      = $subscription_data['account_url']
                ? $subscription_data['account_url']
                : home_url( '/' );
            $button_label    = __( 'Upravljaj pretplatom', 'foundationpress' );
            $current_button  = '<div class="mepr-price-box-button mepr-price-box-button--current">';
            $current_button .= '<a class="mepr-price-box-current-manage" href="' . esc_url( $manage_url ) . '">';
            $current_button .= esc_html( $button_label );
            $current_button .= ' <span aria-hidden="true">→</span>';
            $current_button .= '</a>';
            $current_button .= '</div>';

            $output = preg_replace(
                '#<div class="mepr-price-box-button">.*?</div>#s',
                $current_button,
                $output,
                1
            );
        } elseif ( $product instanceof MeprProduct && $subscription_data['primary_product'] instanceof MeprProduct ) {
            $output = preg_replace_callback(
                '#(<div class="mepr-price-box-button">\s*<a\b[^>]*>)(.*?)(</a>\s*</div>)#s',
                function( $matches ) {
                    return $matches[1] . esc_html__( 'Prijeđi na ovaj plan', 'foundationpress' ) . $matches[3];
                },
                $output,
                1
            );

            $savings_text = theme_get_plan_savings_text( $subscription_data['primary_product'], $product );

            if ( '' !== $savings_text ) {
                $savings_html = '<div class="mepr-price-box-savings"><span class="mepr-price-box-savings-icon" aria-hidden="true">↓</span>' . esc_html( $savings_text ) . '</div>';

                $output = preg_replace(
                    '#(</div>)(\s*<div class="mepr-price-box-button"\b)#s',
                    '$1' . $savings_html . '$2',
                    $output,
                    1
                );
            }

            // Show the actual prorated "due today" amount, matching the checkout.
            $proration = theme_get_plan_proration_for_current_user( $product );

            if ( null !== $proration ) {
                $today_amount = theme_format_eur_amount( $proration['amount'] );
                $full_price   = theme_format_eur_amount( $proration['full_price'] );
                $term         = theme_get_pricing_price_term( $product );
                $after_text   = '' !== $term
                    /* translators: 1: full plan price, 2: billing term, e.g. "6 mjeseci" */
                    ? sprintf( __( 'Nakon toga %1$s / %2$s', 'foundationpress' ), $full_price, $term )
                    /* translators: %s: full plan price */
                    : sprintf( __( 'Nakon toga %s', 'foundationpress' ), $full_price );

                $proration_html  = '<div class="mepr-price-box-proration">';
                $proration_html .= '<span class="mepr-price-box-proration-today">' . esc_html( sprintf( __( 'Danas %s', 'foundationpress' ), $today_amount ) ) . '</span>';
                $proration_html .= '<span class="mepr-price-box-proration-note">' . esc_html__( 'za preostali dio trenutnog razdoblja', 'foundationpress' ) . '</span>';
                $proration_html .= '<span class="mepr-price-box-proration-after">' . esc_html( $after_text ) . '</span>';
                $proration_html .= '</div>';

                // Place it right after the price block.
                $output = preg_replace(
                    '#(<div class="mepr-price-box-price">.*?</div>)#s',
                    '$1' . $proration_html,
                    $output,
                    1
                );
            }
        }

        $output = preg_replace(
            '/class="mepr-price-box([^"]*)"/',
            'class="mepr-price-box pricing-plans__card$1"',
            $output,
            1
        );

        $output = preg_replace_callback(
            '#<div class="mepr-price-box-price">\s*(.*?)\s*</div>#s',
            function( $matches ) use ( $product ) {
                $price = trim( wp_strip_all_tags( $matches[1] ) );

                if ( '' === $price ) {
                    return $matches[0];
                }

                $parts  = preg_split( '#\s*/\s*#', $price, 2 );
                $amount = preg_replace( '/(?<=\d)\.(?=\d{2}\b)/', ',', trim( $parts[0] ) );
                $term   = isset( $parts[1] ) ? trim( $parts[1] ) : theme_get_pricing_price_term( $product );

                $html  = '<div class="mepr-price-box-price">';
                $html .= '<span class="mepr-price-box-price-amount">' . esc_html( $amount ) . '</span>';

                if ( '' !== $term ) {
                    $html .= ' <span class="mepr-price-box-price-term">/ ' . esc_html( $term ) . '</span>';
                }

                $html .= '</div>';

                return $html;
            },
            $output
        );

        $output = preg_replace_callback(
            '#<div class="mepr-price-box-benefits-item">(.*?)</div>#s',
            function( $matches ) use ( &$price_note ) {
                $benefit  = trim( $matches[1] );
                $plain    = wp_strip_all_tags( $benefit );
                $is_note  = preg_match( '/^\s*(?:\[note\]|\[price-note\]|\[cijena\])\s*/iu', $plain );
                $is_muted = preg_match( '/^\s*(?:\[x\]|\[no\]|x\s|×|✕|-|–)\s*/i', $plain );

                if ( $is_note ) {
                    $benefit    = preg_replace( '/^\s*(?:\[note\]|\[price-note\]|\[cijena\])\s*/iu', '', $benefit );
                    $price_note = theme_format_price_box_benefit_text( $benefit );

                    return '';
                }

                if ( $is_muted ) {
                    $benefit = preg_replace( '/^\s*(?:\[x\]|\[no\]|x\s|×|✕|-|–)\s*/iu', '', $benefit );
                }

                $class = 'mepr-price-box-benefits-item' . ( $is_muted ? ' is-muted' : '' );

                return '<div class="' . esc_attr( $class ) . '"><span class="mepr-price-box-benefit-text">' . theme_format_price_box_benefit_text( $benefit ) . '</span></div>';
            },
            $output
        );

        if ( '' !== $price_note ) {
            $output = preg_replace(
                '#(<div class="mepr-price-box-price">.*?</div>)#s',
                '$1<div class="mepr-price-box-note">' . $price_note . '</div>',
                $output,
                1
            );
        }

        return $prefix . $output;
    },
    10,
    4
);
