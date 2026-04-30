<?php
if ( ! defined( 'ABSPATH' ) ) {
    die( 'You are not allowed to call this page directly.' );
}

$mepr_coupon_code_value = isset( $mepr_coupon_code ) ? $mepr_coupon_code : '';
$first_name_value       = isset( $first_name_value ) ? $first_name_value : '';
$last_name_value        = isset( $last_name_value ) ? $last_name_value : '';
$user_email_value       = isset( $user_email ) ? $user_email : '';
$has_coupon_code        = '' !== trim( (string) $mepr_coupon_code_value );
$payment_methods        = isset( $payment_methods ) && is_array( $payment_methods ) ? $payment_methods : array();
$payment_methods_count  = count( $payment_methods );
$checkout_is_recurring  = method_exists( $product, 'is_one_time_payment' ) ? ! $product->is_one_time_payment() : true;
$checkout_product_title = get_the_title( $product->ID );
$format_checkout_price  = static function( $html ) {
    $html = preg_replace( '/(?<=\d)\.(?=\d{2}\b)/', ',', (string) $html );
    $html = preg_replace( '/\s*with\s+coupon\s+/iu', ' uz kupon ', $html );
    $html = preg_replace( '/\bFree\s+forever\b/iu', 'Besplatno zauvijek', $html );

    // Strip the recurring period suffix ("/ 3 Mjeseca", "/ 1 Year", "/ 6 Months", etc.)
    // — we render the period as a separate, smaller subtitle below the amount.
    $html = preg_replace(
        '#\s*/\s*\d+\s+(?:Mjesec[a-zčćžšđ]*|Mjeseci|Godin[a-zčćžšđ]*|Tjed[a-zčćžšđ]*|Dan[a-zčćžšđ]*|Year[s]?|Month[s]?|Week[s]?|Day[s]?)\b#iu',
        '',
        $html
    );

    return trim( $html );
};

if ( '' === $checkout_product_title ) {
    $checkout_product_title = $product->post_title;
}

ob_start();
MeprProductsHelper::display_invoice( $product, $mepr_coupon_code_value );
$checkout_price_html  = trim( $format_checkout_price( ob_get_clean() ) );
$checkout_price_label = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $checkout_price_html ) ) );
$signup_button_text   = trim( wp_strip_all_tags( stripslashes( $product->signup_button_text ) ) );

if ( '' === $signup_button_text ) {
    $signup_button_text = __( 'Pretplati se', 'zaherpilates' );
}

$submit_button_text = $signup_button_text;

if ( $payment_required && '' !== $checkout_price_label ) {
    $submit_button_text = sprintf(
        /* translators: 1: submit button label, 2: checkout price */
        __( '%1$s · %2$s', 'zaherpilates' ),
        $signup_button_text,
        $checkout_price_label
    );
}
?>

<div class="mepr-before-signup-form">
    <?php do_action( 'mepr-above-checkout-form', $product->ID ); ?>
</div>

<?php
$checkout_form_action = get_permalink( $product->ID );

if ( ! empty( $_GET['coupon'] ) ) {
    $checkout_form_action = add_query_arg( 'coupon', sanitize_text_field( wp_unslash( $_GET['coupon'] ) ), $checkout_form_action );
}

if ( ! empty( $_GET['mepr_transaction_id'] ) ) {
    $checkout_form_action = add_query_arg( 'mepr_transaction_id', absint( wp_unslash( $_GET['mepr_transaction_id'] ) ), $checkout_form_action );
}

$checkout_form_action .= '#mepr_jump';
?>
<form name="mepr_signup_form" id="mepr_signup_form" class="mepr-signup-form mepr-form alignwide" method="post"
    action="<?php echo esc_url( $checkout_form_action ); ?>" enctype="multipart/form-data" novalidate>
    <div class="mepr-checkout-container mp_wrapper <?php echo ! empty( $is_rl_widget ) ? 'mepr-is-footer-widget' : ''; ?>">
        <div class="invoice-wrapper">
            <div class="mepr-checkout-plan">
                <div class="mepr-checkout-plan-icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="invoice-heading"><?php echo esc_html( $checkout_product_title ); ?></h3>
                    <p class="mepr-checkout-plan-subtitle">
                        <?php echo esc_html( $checkout_is_recurring ? __( 'LOOP - ZaherPilates · online pretplata', 'zaherpilates' ) : get_bloginfo( 'name' ) ); ?>
                    </p>
                </div>
            </div>

            <?php MeprHooks::do_action( 'mepr-checkout-before-price', $product->ID ); ?>

            <?php if ( ( $product->register_price_action !== 'hidden' ) && MeprHooks::apply_filters( 'mepr_checkout_show_terms', true, $product ) ) : ?>
                <?php $checkout_period_text = function_exists( 'zaher_get_billing_period_text' ) ? zaher_get_billing_period_text( $product ) : ''; ?>
                <div class="mp-form-row mepr_bold mepr_price">
                    <div class="mepr_price_cell_label"><?php esc_html_e( 'Ukupno danas', 'zaherpilates' ); ?></div>
                    <div class="mepr_price_cell invoice-amount" data-billing-period="<?php echo esc_attr( $checkout_period_text ); ?>">
                        <span class="invoice-amount-value"><?php echo wp_kses_post( $checkout_price_html ); ?></span>
                        <?php if ( '' !== $checkout_period_text ) : ?>
                            <span class="invoice-amount-period"><?php echo esc_html( $checkout_period_text ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php $checkout_benefits = function_exists( 'zaher_get_checkout_benefits' ) ? zaher_get_checkout_benefits( $product ) : array(); ?>
            <?php if ( ! empty( $checkout_benefits ) ) : ?>
                <ul class="mepr-checkout-benefits">
                    <?php foreach ( $checkout_benefits as $checkout_benefit ) : ?>
                        <li>
                            <span aria-hidden="true">✓</span>
                            <?php echo wp_kses( $checkout_benefit, array( 'strong' => array(), 'em' => array(), 'br' => array() ) ); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php MeprHooks::do_action( 'mepr-before-coupon-field' ); ?>
            <?php MeprHooks::do_action( 'mepr-checkout-before-coupon-field', $product->ID ); ?>

            <div class="mepr-checkout-coupon">
                <?php if ( $payment_required || ! empty( $product->plan_code ) ) : ?>
                    <?php if ( $mepr_options->coupon_field_enabled ) : ?>
                        <button type="button" class="have-coupon-link mepr-checkout-coupon-toggle<?php echo $has_coupon_code ? ' is-open' : ''; ?>" data-prdid="<?php echo esc_attr( $product->ID ); ?>" aria-expanded="<?php echo $has_coupon_code ? 'true' : 'false'; ?>" aria-controls="mepr_coupon_row<?php echo esc_attr( $unique_suffix ); ?>">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                            <?php esc_html_e( 'Imam kupon', 'zaherpilates' ); ?>
                        </button>

                        <div id="mepr_coupon_row<?php echo esc_attr( $unique_suffix ); ?>" class="mp-form-row mepr_coupon mepr_coupon_<?php echo esc_attr( $product->ID ); ?> mepr-checkout-coupon-field<?php echo $has_coupon_code ? '' : ' mepr-hidden'; ?>">
                            <div class="mp-form-label mepr-coupon-feedback is-empty">
                                <span class="mepr-coupon-loader mepr-hidden">
                                    <img src="<?php echo esc_url( includes_url( 'js/thickbox/loadingAnimation.gif' ) ); ?>"
                                        alt="<?php esc_attr_e( 'Loading...', 'memberpress' ); ?>"
                                        title="<?php echo esc_attr_x( 'Loading icon', 'ui', 'memberpress' ); ?>" width="100" height="10" />
                                </span>
                                <span class="cc-error"><?php esc_html_e( 'Neispravan kupon', 'zaherpilates' ); ?></span>
                                <span class="cc-success"><?php esc_html_e( 'Kupon je primijenjen', 'zaherpilates' ); ?></span>
                            </div>
                            <input type="text" id="mepr_coupon_code<?php echo esc_attr( $unique_suffix ); ?>" class="mepr-form-input mepr-coupon-code"
                                placeholder="<?php esc_attr_e( 'Unesi kod', 'zaherpilates' ); ?>"
                                name="mepr_coupon_code"
                                value="<?php echo esc_attr( stripslashes( $mepr_coupon_code_value ) ); ?>"
                                data-prdid="<?php echo esc_attr( $product->ID ); ?>" />
                            <button type="button" class="mepr-checkout-coupon-apply">
                                <?php esc_html_e( 'Primijeni', 'zaherpilates' ); ?>
                            </button>
                        </div>
                    <?php else : ?>
                        <input type="hidden" id="mepr_coupon_code-<?php echo esc_attr( $product->ID ); ?>" name="mepr_coupon_code"
                            value="<?php echo esc_attr( stripslashes( $mepr_coupon_code_value ) ); ?>" />
                    <?php endif; ?>
                <?php else : ?>
                    <input type="hidden" id="mepr_coupon_code-<?php echo esc_attr( $product->ID ); ?>" name="mepr_coupon_code"
                        value="<?php echo esc_attr( stripslashes( $mepr_coupon_code_value ) ); ?>" />
                <?php endif; ?>
            </div>

            <?php MeprHooks::do_action( 'mepr-checkout-before-invoice', $product->ID ); ?>

            <div class="mepr-checkout-trust">
                <div>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                        <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                    </svg>
                    <?php esc_html_e( 'Sigurno plaćanje · SSL enkripcija', 'zaherpilates' ); ?>
                </div>
                <div>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="1" y="4" width="22" height="16" rx="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    <?php esc_html_e( 'Visa, Mastercard, Apple Pay, Google Pay', 'zaherpilates' ); ?>
                </div>
                <div>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?php esc_html_e( 'Otkaži u bilo kojem trenutku', 'zaherpilates' ); ?>
                </div>
            </div>
        </div>

        <div class="form-wrapper">
            <?php MeprView::render( '/shared/errors', get_defined_vars() ); ?>

            <input type="hidden" name="mepr_process_signup_form"
                value="<?php echo isset( $_GET['mepr_process_signup_form'] ) ? esc_attr( wp_unslash( $_GET['mepr_process_signup_form'] ) ) : 1; ?>" />
            <input type="hidden" name="mepr_product_id" value="<?php echo esc_attr( $product->ID ); ?>" />
            <input type="hidden" name="mepr_transaction_id"
                value="<?php echo isset( $_GET['mepr_transaction_id'] ) ? esc_attr( wp_unslash( $_GET['mepr_transaction_id'] ) ) : ''; ?>" />

            <?php if ( MeprUtils::is_user_logged_in() ) : ?>
                <input type="hidden" name="logged_in_purchase" value="1" />
                <input type="hidden" name="mepr_checkout_nonce" value="<?php echo esc_attr( wp_create_nonce( 'logged_in_purchase' ) ); ?>">
                <input type="hidden" name="user_email" value="<?php echo esc_attr( stripslashes( $mepr_current_user->user_email ) ); ?>" />
                <input type="hidden" name="user_first_name" value="<?php echo esc_attr( $first_name_value ); ?>" />
                <input type="hidden" name="user_last_name" value="<?php echo esc_attr( $last_name_value ); ?>" />
                <?php wp_referer_field(); ?>
            <?php else : ?>
                <input type="hidden" class="mepr-geo-country" name="mepr-geo-country" value="" />
            <?php endif; ?>

            <?php MeprHooks::do_action( 'mepr-checkout-before-name', $product->ID ); ?>

            <div class="mepr-checkout-section-title-row">
                <div class="mepr-checkout-section-title"><?php esc_html_e( 'Tvoj račun', 'zaherpilates' ); ?></div>
                <?php if ( ! MeprUtils::is_user_logged_in() ) : ?>
                    <p class="mepr-checkout-login-link">
                        <?php esc_html_e( 'Već imaš račun?', 'zaherpilates' ); ?>
                        <a href="<?php echo esc_url( wp_login_url( get_permalink( $product->ID ) ) ); ?>">
                            <?php esc_html_e( 'Prijavi se', 'zaherpilates' ); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ( MeprUtils::is_user_logged_in() ) : ?>
                <div class="mp-form-row mepr_logged_in">
                    <div class="mp-form-label">
                        <label><?php esc_html_e( 'E-mail adresa', 'zaherpilates' ); ?></label>
                    </div>
                    <div class="mepr-checkout-account-email">
                        <?php echo esc_html( stripslashes( $mepr_current_user->user_email ) ); ?>
                    </div>
                </div>
            <?php else : ?>
                <div class="mepr-checkout-field-grid">
                    <div class="mp-form-row mepr_first_name<?php echo $mepr_options->require_fname_lname ? ' mepr-field-required' : ''; ?>">
                        <div class="mp-form-label">
                            <label for="user_first_name<?php echo esc_attr( $unique_suffix ); ?>"><?php esc_html_e( 'Ime', 'zaherpilates' ); ?></label>
                            <span class="cc-error"><?php esc_html_e( 'Unesi ime', 'zaherpilates' ); ?></span>
                        </div>
                        <input type="text" name="user_first_name" id="user_first_name<?php echo esc_attr( $unique_suffix ); ?>" class="mepr-form-input"
                            value="<?php echo esc_attr( $first_name_value ); ?>" placeholder="<?php esc_attr_e( 'Ana', 'zaherpilates' ); ?>" <?php echo $mepr_options->require_fname_lname ? 'required' : ''; ?> />
                    </div>
                    <div class="mp-form-row mepr_last_name<?php echo $mepr_options->require_fname_lname ? ' mepr-field-required' : ''; ?>">
                        <div class="mp-form-label">
                            <label for="user_last_name<?php echo esc_attr( $unique_suffix ); ?>"><?php esc_html_e( 'Prezime', 'zaherpilates' ); ?></label>
                            <span class="cc-error"><?php esc_html_e( 'Unesi prezime', 'zaherpilates' ); ?></span>
                        </div>
                        <input type="text" name="user_last_name" id="user_last_name<?php echo esc_attr( $unique_suffix ); ?>" class="mepr-form-input"
                            value="<?php echo esc_attr( $last_name_value ); ?>" placeholder="<?php esc_attr_e( 'Horvat', 'zaherpilates' ); ?>" <?php echo $mepr_options->require_fname_lname ? 'required' : ''; ?> />
                    </div>
                </div>
                <?php if ( ! $mepr_options->username_is_email ) : ?>
                    <div class="mp-form-row mepr_username mepr-field-required">
                        <div class="mp-form-label">
                            <label for="user_login<?php echo esc_attr( $unique_suffix ); ?>"><?php esc_html_e( 'Korisničko ime', 'zaherpilates' ); ?>*</label>
                            <span class="cc-error"><?php esc_html_e( 'Unesi korisničko ime', 'zaherpilates' ); ?></span>
                        </div>
                        <input type="text" name="user_login" id="user_login<?php echo esc_attr( $unique_suffix ); ?>" class="mepr-form-input"
                            value="<?php echo isset( $user_login ) ? esc_attr( stripslashes( $user_login ) ) : ''; ?>" required />
                    </div>
                <?php endif; ?>
                <div class="mp-form-row mepr_email mepr-field-required">
                    <div class="mp-form-label">
                        <label for="user_email<?php echo esc_attr( $unique_suffix ); ?>"><?php esc_html_e( 'E-mail adresa', 'zaherpilates' ); ?></label>
                        <span class="cc-error"><?php esc_html_e( 'Unesi ispravnu e-mail adresu', 'zaherpilates' ); ?></span>
                    </div>
                    <input type="email" name="user_email" id="user_email<?php echo esc_attr( $unique_suffix ); ?>" class="mepr-form-input"
                        value="<?php echo esc_attr( stripslashes( $user_email_value ) ); ?>" placeholder="<?php esc_attr_e( 'ana@primjer.hr', 'zaherpilates' ); ?>" required />
                </div>
                <div class="mp-form-row mepr_email_stripe mepr-field-required mepr-hidden"></div>
                <?php MeprHooks::do_action( 'mepr-after-email-field' ); ?>
                <?php MeprHooks::do_action( 'mepr-checkout-after-email-field', $product->ID ); ?>
                <?php if ( false === $mepr_options->disable_checkout_password_fields ) : ?>
                    <div class="mp-form-row mepr_password mepr-field-required">
                        <div class="mp-form-label">
                            <label for="mepr_user_password<?php echo esc_attr( $unique_suffix ); ?>"><?php esc_html_e( 'Lozinka', 'zaherpilates' ); ?></label>
                            <span class="cc-error"><?php esc_html_e( 'Unesi lozinku', 'zaherpilates' ); ?></span>
                        </div>
                        <input type="password" name="mepr_user_password" id="mepr_user_password<?php echo esc_attr( $unique_suffix ); ?>" class="mepr-form-input mepr-password"
                            value="<?php echo isset( $mepr_user_password ) ? esc_attr( stripslashes( $mepr_user_password ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'Minimalno 8 znakova', 'zaherpilates' ); ?>" autocomplete="new-password" minlength="8" required />
                        <div class="mepr-checkout-password-meter" data-password-meter>
                            <div class="mepr-checkout-password-bars" aria-hidden="true">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <span class="mepr-checkout-password-label"><?php esc_html_e( 'Unesi lozinku', 'zaherpilates' ); ?></span>
                        </div>
                    </div>
                    <div class="mp-form-row mepr_password_confirm mepr-field-required">
                        <div class="mp-form-label">
                            <label for="mepr_user_password_confirm<?php echo esc_attr( $unique_suffix ); ?>"><?php esc_html_e( 'Potvrda lozinke', 'zaherpilates' ); ?></label>
                            <span class="cc-error"><?php esc_html_e( 'Lozinke se ne podudaraju', 'zaherpilates' ); ?></span>
                        </div>
                        <input type="password" name="mepr_user_password_confirm" id="mepr_user_password_confirm<?php echo esc_attr( $unique_suffix ); ?>" class="mepr-form-input mepr-password-confirm"
                            value="<?php echo isset( $mepr_user_password_confirm ) ? esc_attr( stripslashes( $mepr_user_password_confirm ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'Ponovi lozinku', 'zaherpilates' ); ?>" autocomplete="new-password" required />
                    </div>
                    <?php MeprHooks::do_action( 'mepr-after-password-fields' ); ?>
                    <?php MeprHooks::do_action( 'mepr-checkout-after-password-fields', $product->ID ); ?>
                <?php endif; ?>
            <?php endif; ?>

            <?php MeprHooks::do_action( 'mepr-checkout-before-custom-fields', $product->ID ); ?>
            <?php if ( class_exists( 'MeprUsersHelper' ) && method_exists( 'MeprUsersHelper', 'render_custom_fields' ) ) : ?>
                <?php MeprUsersHelper::render_custom_fields( $product ); ?>
            <?php endif; ?>
            <?php MeprHooks::do_action( 'mepr-checkout-after-custom-fields', $product->ID ); ?>

            <blockquote class="mepr-checkout-quote">
                <p>
                    <?php esc_html_e( 'Žene koje napreduju ne čekaju savršen trenutak — one iskoriste trenutak koji imaju. Ti si već napravila najteži korak — ostao je samo jedan klik.', 'zaherpilates' ); ?>
                </p>
            </blockquote>

            <?php MeprHooks::do_action( 'mepr_render_order_bumps', $product ); ?>

            <div class="mepr-checkout-section-title mepr-checkout-section-title-payment"><?php esc_html_e( 'Plaćanje', 'zaherpilates' ); ?></div>

            <?php if ( $payment_methods_count <= 0 && $payment_required ) : ?>
                <div class="mepr_error">
                    <?php esc_html_e( 'Trenutno nema aktivnih načina plaćanja. Kontaktiraj podršku.', 'zaherpilates' ); ?>
                </div>
            <?php endif; ?>

            <?php if ( $payment_required || ! empty( $product->plan_code ) ) : ?>
                <?php MeprHooks::do_action( 'mepr-checkout-before-payment-methods', $product->ID ); ?>

                <div class="mepr-payment-methods-wrapper">
                    <div class="mepr-payment-methods-heading">
                        <h3><?php esc_html_e( 'Kreditna / debitna kartica', 'zaherpilates' ); ?></h3>
                        <div class="mepr-payment-methods-icons">
                            <?php echo MeprOptionsHelper::payment_methods_icons( $payment_methods ); ?>
                        </div>
                    </div>
                    <div class="mepr-payment-methods-radios<?php echo 1 === $payment_methods_count ? ' mepr-hidden' : ''; ?>">
                        <?php echo MeprOptionsHelper::payment_methods_radios( $payment_methods ); ?>
                    </div>
                    <?php echo MeprOptionsHelper::payment_methods_descriptions( $payment_methods, $product ); ?>
                    <div class="mepr-stripe-badge">
                        <span aria-hidden="true">◇</span>
                        <?php esc_html_e( 'Plaćanje zaštićeno Stripe-om', 'zaherpilates' ); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( $mepr_options->require_tos ) : ?>
                <div class="mp-form-row mepr_tos">
                    <label for="mepr_agree_to_tos<?php echo esc_attr( $unique_suffix ); ?>" class="mepr-checkbox-field mepr-form-input" required>
                        <input type="checkbox" name="mepr_agree_to_tos" id="mepr_agree_to_tos<?php echo esc_attr( $unique_suffix ); ?>"
                            <?php checked( isset( $mepr_agree_to_tos ) ); ?> />
                        <span>
                            <?php esc_html_e( 'Prihvaćam', 'zaherpilates' ); ?>
                            <a href="<?php echo esc_url( stripslashes( $mepr_options->tos_url ) ); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo wp_kses_post( stripslashes( $mepr_options->tos_title ) ); ?>
                            </a>*
                        </span>
                    </label>
                </div>
            <?php endif; ?>

            <?php if ( $mepr_options->require_privacy_policy && $privacy_page_link = MeprAppHelper::privacy_policy_page_link() ) : ?>
                <div class="mp-form-row mepr_privacy">
                    <label for="mepr_agree_to_privacy_policy<?php echo esc_attr( $unique_suffix ); ?>" class="mepr-checkbox-field mepr-form-input" required>
                        <input type="checkbox" name="mepr_agree_to_privacy_policy" id="mepr_agree_to_privacy_policy<?php echo esc_attr( $unique_suffix ); ?>" />
                        <span>
                            <?php
                            echo preg_replace(
                                '/%(.*)%/',
                                '<a href="' . esc_url( $privacy_page_link ) . '" target="_blank" rel="noopener noreferrer">$1</a>',
                                wp_kses_post( __( $mepr_options->privacy_policy_title, 'memberpress' ) )
                            );
                            ?>
                        </span>
                    </label>
                </div>
            <?php endif; ?>

            <?php MeprHooks::do_action( 'mepr-user-signup-fields' ); ?>
            <?php MeprHooks::do_action( 'mepr-checkout-before-submit', $product->ID ); ?>

            <div class="mepr_spacer">&nbsp;</div>

            <div class="mp-form-submit">
                <label for="mepr_no_val" class="mepr-visuallyhidden"><?php esc_html_e( 'No val', 'memberpress' ); ?></label>
                <input type="text" id="mepr_no_val" name="mepr_no_val"
                    class="mepr-form-input mepr-visuallyhidden mepr_no_val mepr-hidden" autocomplete="off" />

                <?php if ( $payment_methods_count > 0 || ! $payment_required ) : ?>
                    <input type="submit" class="mepr-submit" value="<?php echo esc_attr( $submit_button_text ); ?>" data-submit-label="<?php echo esc_attr( $signup_button_text ); ?>" />
                <?php endif; ?>

                <img src="<?php echo esc_url( admin_url( 'images/loading.gif' ) ); ?>" alt="<?php esc_attr_e( 'Loading...', 'memberpress' ); ?>"
                    style="display: none;" class="mepr-loading-gif" title="<?php echo esc_attr_x( 'Loading icon', 'ui', 'memberpress' ); ?>" />
                <span class="mepr-form-has-errors"><?php esc_html_e( 'Provjeri označena polja.', 'zaherpilates' ); ?></span>

                <?php if ( $checkout_is_recurring ) : ?>
                    <p class="mepr-checkout-footer-note">
                        <?php esc_html_e( 'Pretplata se automatski obnavlja. Možeš otkazati u korisničkom računu u bilo kojem trenutku.', 'zaherpilates' ); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>
