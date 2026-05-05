<?php if ( ! defined( 'ABSPATH' ) ) {
    die( 'You are not allowed to call this page directly.' );
}

$katalog_page = get_page_by_path( 'katalog' );
$katalog_url  = $katalog_page instanceof WP_Post ? get_permalink( $katalog_page ) : home_url( '/katalog/' );
?>

<div class="mepr-signup-form mepr-form">
    <div class="mepr-checkout-container mepr-checkout-thankyou<?php echo $has_welcome_image && ! empty( $welcome_image ) ? ' has-welcome-image' : ''; ?> mp_wrapper alignwide">

        <?php if ( $has_welcome_image && ! empty( $welcome_image ) ) : ?>
            <aside class="mepr-checkout-thankyou__media" aria-hidden="true">
                <figure>
                    <img class="thankyou-image" src="<?php echo esc_url( $welcome_image ); ?>" alt="">
                </figure>
            </aside>
        <?php endif; ?>

        <section class="mepr-checkout-thankyou__card" aria-labelledby="mepr-checkout-thankyou-title">
            <div class="mepr-checkout-thankyou__hero">
                <span class="mepr-checkout-thankyou__icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20 6L9 17l-5-5"></path>
                    </svg>
                </span>
                <p class="mepr-checkout-thankyou__eyebrow"><?php esc_html_e( 'Plaćanje uspješno', 'zaherpilates' ); ?></p>
                <h1 id="mepr-checkout-thankyou-title"><?php esc_html_e( 'Čestitam na kupnji!', 'zaherpilates' ); ?></h1>
                <p class="mepr-checkout-thankyou__intro">
                    <?php esc_html_e( 'Tvoj pristup LOOP katalogu je aktivan. Možeš odmah odabrati prvi trening.', 'zaherpilates' ); ?>
                </p>
            </div>

            <?php if ( $hide_invoice ) : ?>
                <div class="mepr-checkout-thankyou__message">
                    <?php echo wp_kses_post( $invoice_message ); ?>
                </div>
            <?php else : ?>
                <div class="mepr-checkout-thankyou__summary">
                    <div>
                        <span><?php esc_html_e( 'Narudžba', 'zaherpilates' ); ?></span>
                        <strong><?php echo esc_html( $trans_num ); ?></strong>
                    </div>
                    <div>
                        <span><?php esc_html_e( 'Plaćeno danas', 'zaherpilates' ); ?></span>
                        <strong><?php echo wp_kses_post( $amount ); ?></strong>
                    </div>
                </div>

                <div class="mepr-checkout-thankyou__invoice">
                    <div class="mepr-checkout-section-title"><?php esc_html_e( 'Sažetak narudžbe', 'zaherpilates' ); ?></div>
                    <?php echo wp_kses_post( $invoice_html ); ?>
                </div>
            <?php endif; ?>

            <?php do_action( 'mepr_readylaunch_thank_you_page_after_content' ); ?>

            <div class="mepr-checkout-thankyou__actions">
                <a class="mepr-checkout-thankyou__catalog" href="<?php echo esc_url( $katalog_url ); ?>">
                    <?php esc_html_e( 'Idi na katalog treninga', 'zaherpilates' ); ?>
                </a>
                <?php if ( class_exists( 'MePdfInvoicesCtrl' ) ) : ?>
                    <a class="mepr-invoice-print mepr-checkout-thankyou__print" href="<?php echo esc_url( MeprUtils::admin_url(
                        'admin-ajax.php',
                        array( 'download_invoice', 'mepr_invoices_nonce' ),
                        array(
                            'action' => 'mepr_download_invoice',
                            'txn'    => $txn->id,
                        )
                    ) ); ?>" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                        </svg>
                        <?php esc_html_e( 'Ispiši', 'zaherpilates' ); ?>
                    </a>
                <?php endif; ?>
            </div>

            <p class="mepr-checkout-thankyou__note">
                <?php esc_html_e( 'Potvrda kupnje i račun stižu na tvoju e-mail adresu.', 'zaherpilates' ); ?>
            </p>
        </section>
    </div>
</div>
