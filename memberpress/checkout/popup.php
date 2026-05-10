<?php if ( ! defined( 'ABSPATH' ) ) { die(); } ?>

<div id="theme-checkout-popup" class="theme-popup" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="theme-popup-title" aria-describedby="theme-popup-description">
    <div class="theme-popup__overlay" aria-hidden="true"></div>

    <div class="theme-popup__card" tabindex="-1">
        <div class="theme-popup__accent" aria-hidden="true"></div>

        <button type="button" class="theme-popup__close" aria-label="<?php esc_attr_e( 'Zatvori', 'foundationpress' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <h2 class="theme-popup__title js-popup-title" id="theme-popup-title">
            Prije završetka,<br>pogledaj ovu ponudu
        </h2>

        <div class="theme-popup__description" id="theme-popup-description">
            Na ovom checkoutu možeš odmah prebaciti kupnju na <strong>povoljniju pretplatu</strong> uz posebnu ponudu dostupnu samo ovdje.
        </div>

        <div class="theme-popup__prices">
            <p class="theme-popup__price-kicker js-popup-price-kicker" hidden></p>
            <div class="theme-popup__price-row">
                <div class="theme-popup__price-old js-popup-old-price" hidden></div>
                <span class="theme-popup__price-arrow js-popup-price-arrow" aria-hidden="true" hidden>→</span>
                <div class="theme-popup__price-new js-popup-new-price"></div>
            </div>
            <p class="theme-popup__price-renewal js-popup-price-renewal" hidden></p>
            <p class="theme-popup__price-benefit is-primary js-popup-price-benefit-primary" hidden></p>
        </div>

        <div class="theme-popup__urgency">
            <span class="theme-popup__urgency-dot" aria-hidden="true"></span>
            <p class="theme-popup__urgency-text">Ponuda vrijedi samo na ovom checkoutu</p>
        </div>

        <a href="#" class="theme-popup__cta js-popup-cta-btn" rel="nofollow">
            <span class="js-popup-cta-label">Da, želim ovu ponudu</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
            </svg>
        </a>

        <button type="button" class="theme-popup__skip js-popup-skip">
            Ne, ostajem pri originalnoj pretplati
        </button>

    </div>
</div>
