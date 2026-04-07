<?php if ( ! defined( 'ABSPATH' ) ) { die(); } ?>

<div id="zaher-checkout-popup" class="zaher-popup" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="zaher-popup-title" aria-describedby="zaher-popup-description">
    <div class="zaher-popup__overlay" aria-hidden="true"></div>

    <div class="zaher-popup__card" tabindex="-1">
        <div class="zaher-popup__accent" aria-hidden="true"></div>

        <button type="button" class="zaher-popup__close" aria-label="<?php esc_attr_e( 'Zatvori', 'zaherpilates' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <h2 class="zaher-popup__title js-popup-title" id="zaher-popup-title">
            Prije završetka,<br>pogledaj ovu ponudu
        </h2>

        <div class="zaher-popup__description" id="zaher-popup-description">
            <p class="zaher-popup__subtitle js-popup-subtitle">
                Na ovom checkoutu možeš odmah prebaciti kupnju na <strong>povoljniju pretplatu</strong> uz posebnu ponudu dostupnu samo ovdje.
            </p>
            <div class="zaher-popup__body js-popup-body" hidden></div>
        </div>

        <div class="zaher-popup__prices">
            <p class="zaher-popup__price-kicker js-popup-price-kicker" hidden>Ponuda</p>
            <div class="zaher-popup__price-row">
                <div class="zaher-popup__price-old js-popup-old-price">79,99 € / 3 mj.</div>
                <span class="zaher-popup__price-arrow js-popup-price-arrow" aria-hidden="true">→</span>
                <div class="zaher-popup__price-new js-popup-new-price">55,99 € <span>/ 3 mj.</span></div>
            </div>
            <p class="zaher-popup__price-renewal js-popup-price-renewal" hidden>Popust vrijedi za prvi obračun. Nakon toga 79,99 € / 3 mj.</p>
            <p class="zaher-popup__price-benefit is-primary js-popup-price-benefit-primary" hidden>Štediš 33,98 € kroz isti period.</p>
        </div>

        <div class="zaher-popup__urgency">
            <span class="zaher-popup__urgency-dot" aria-hidden="true"></span>
            <p class="zaher-popup__urgency-text">Ponuda vrijedi samo na ovom checkoutu</p>
        </div>

        <a href="#" class="zaher-popup__cta js-popup-cta-btn" rel="nofollow">
            <span class="js-popup-cta-label">Da, želim ovu ponudu</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
            </svg>
        </a>

        <button type="button" class="zaher-popup__skip js-popup-skip">
            Ne, ostajem pri originalnoj pretplati
        </button>

    </div>
</div>
