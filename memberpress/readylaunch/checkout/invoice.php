<?php
/**
 * Preserve MemberPress' invoice and expose its coupon amounts to the price card.
 * The same view renders on page load and in checkout-state AJAX responses.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$checkout_original_price = '';
$checkout_coupon_saving  = '';

// The summary above this invoice describes one membership. Do not present a
// combined order-bump discount beside that membership's individual price.
if ( count( $invoice['items'] ) === 1 && ! empty( $invoice['coupon']['id'] ) && (float) $invoice['coupon']['amount'] > 0 ) {
    $checkout_original_price = MeprAppHelper::format_currency( $invoice['items'][0]['amount'], true, false, true );
    $checkout_coupon_saving  = MeprAppHelper::format_currency( $invoice['coupon']['amount'], true, false, true );
}

// Delegate the invoice markup to the plugin so its billing/tax/payment fields
// remain intact. Only add display metadata to its existing wrapper.
ob_start();
require MEPR_VIEWS_PATH . '/readylaunch/checkout/invoice.php';
$checkout_invoice_markup = new WP_HTML_Tag_Processor( ob_get_clean() );

if ( $checkout_invoice_markup->next_tag( array( 'class_name' => 'mp_invoice' ) ) ) {
    $checkout_invoice_markup->set_attribute( 'data-coupon-original-price', $checkout_original_price );
    $checkout_invoice_markup->set_attribute( 'data-coupon-saving', $checkout_coupon_saving );
}

echo $checkout_invoice_markup->get_updated_html(); // MemberPress renders and escapes the invoice.
