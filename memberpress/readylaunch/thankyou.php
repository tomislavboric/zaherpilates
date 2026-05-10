<?php if ( ! defined( 'ABSPATH' ) ) {
    die( 'You are not allowed to call this page directly.' );
}

$katalog_page = get_page_by_path( 'katalog' );
$katalog_url  = $katalog_page instanceof WP_Post ? get_permalink( $katalog_page ) : home_url( '/katalog/' );
$account_url  = home_url( '/moj-racun/' );
$contact_url  = home_url( '/kontakt/' );

$membership_name = '';
if ( isset( $txn ) && $txn instanceof MeprTransaction ) {
    $product = $txn->product();
    if ( $product instanceof MeprProduct ) {
        $membership_name = $product->post_title;
    }
}
?>

<div class="zp-thankyou">
    <section class="zp-thankyou__hero" aria-labelledby="zp-thankyou-title">
        <span class="zp-thankyou__check" aria-hidden="true">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 6L9 17l-5-5"></path>
            </svg>
        </span>
        <p class="zp-thankyou__eyebrow"><?php esc_html_e( 'Članstvo aktivirano', 'foundationpress' ); ?></p>
        <h1 id="zp-thankyou-title" class="zp-thankyou__title">
            <?php esc_html_e( 'Dobrodošla u Zaher Pilates!', 'foundationpress' ); ?>
        </h1>
        <?php if ( $membership_name ) : ?>
            <span class="zp-thankyou__plan">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z" stroke-linejoin="round"></path>
                </svg>
                <?php echo esc_html( $membership_name ); ?>
            </span>
        <?php endif; ?>
        <p class="zp-thankyou__lead">
            <?php esc_html_e( 'Sretno na putu do snažnijeg, pokretnijeg i samouvjerenijeg tijela. Vrijeme je za prvi trening — odaberi onaj koji ti najviše odgovara.', 'foundationpress' ); ?>
        </p>

        <a class="button zp-thankyou__cta" href="<?php echo esc_url( $katalog_url ); ?>">
            <span><?php esc_html_e( 'Započni trenirati', 'foundationpress' ); ?></span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M5 12h14M13 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </a>
    </section>

    <section class="zp-thankyou__next" aria-label="<?php esc_attr_e( 'Sljedeći koraci', 'foundationpress' ); ?>">
        <a class="zp-next-card" href="<?php echo esc_url( $account_url ); ?>">
            <span class="zp-next-card__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </span>
            <span class="zp-next-card__title"><?php esc_html_e( 'Moj račun', 'foundationpress' ); ?></span>
            <span class="zp-next-card__text"><?php esc_html_e( 'Pregledaj pretplatu, podatke i postavke profila.', 'foundationpress' ); ?></span>
        </a>

        <a class="zp-next-card" href="<?php echo esc_url( $contact_url ); ?>">
            <span class="zp-next-card__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
            </span>
            <span class="zp-next-card__title"><?php esc_html_e( 'Pitanje za Ivanu?', 'foundationpress' ); ?></span>
            <span class="zp-next-card__text"><?php esc_html_e( 'Javi se Ivani ako ti zatreba pomoć oko treninga ili pretplate.', 'foundationpress' ); ?></span>
        </a>
    </section>

    <?php if ( $hide_invoice ) : ?>
        <?php if ( ! empty( $invoice_message ) ) : ?>
            <div class="zp-thankyou__message">
                <?php echo wp_kses_post( $invoice_message ); ?>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <section class="zp-thankyou__details" aria-labelledby="zp-thankyou-details-title">
            <h2 id="zp-thankyou-details-title" class="zp-thankyou__details-title">
                <?php esc_html_e( 'Detalji narudžbe', 'foundationpress' ); ?>
            </h2>

            <div class="zp-thankyou__details-body">
                <div class="zp-thankyou__summary">
                    <div>
                        <span><?php esc_html_e( 'Broj narudžbe', 'foundationpress' ); ?></span>
                        <strong><?php echo esc_html( $trans_num ); ?></strong>
                    </div>
                    <div>
                        <span><?php esc_html_e( 'Plaćeno danas', 'foundationpress' ); ?></span>
                        <strong><?php echo wp_kses_post( $amount ); ?></strong>
                    </div>
                </div>

                <div class="zp-thankyou__invoice">
                    <?php echo wp_kses_post( $invoice_html ); ?>
                </div>

                <?php if ( class_exists( 'MePdfInvoicesCtrl' ) ) : ?>
                    <a class="mepr-invoice-print zp-thankyou__print" href="<?php echo esc_url( MeprUtils::admin_url(
                        'admin-ajax.php',
                        array( 'download_invoice', 'mepr_invoices_nonce' ),
                        array(
                            'action' => 'mepr_download_invoice',
                            'txn'    => $txn->id,
                        )
                    ) ); ?>" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                        </svg>
                        <?php esc_html_e( 'Preuzmi račun (PDF)', 'foundationpress' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php do_action( 'mepr_readylaunch_thank_you_page_after_content' ); ?>

    <p class="zp-thankyou__note">
        <?php esc_html_e( 'Potvrda kupnje i račun stižu na tvoju e-mail adresu.', 'foundationpress' ); ?>
    </p>
</div>
