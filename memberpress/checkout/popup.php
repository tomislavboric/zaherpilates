<?php if ( ! defined( 'ABSPATH' ) ) { die(); } ?>

<div id="zaher-checkout-popup" class="zaher-popup" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="zaher-popup-title" aria-describedby="zaher-popup-description">
    <div class="zaher-popup__overlay" aria-hidden="true"></div>

    <div class="zaher-popup__card" tabindex="-1">

        <button type="button" class="zaher-popup__close" aria-label="<?php esc_attr_e( 'Zatvori', 'zaherpilates' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <div class="zaher-popup__badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
            </svg>
            <span class="js-popup-badge-text">Ekskluzivna ponuda</span>
        </div>

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
            <div class="zaher-popup__price-old js-popup-old-price">3 × 22,90 €</div>
            <div class="zaher-popup__price-new js-popup-new-price">34,90 € <span>/ 3 mj.</span></div>
        </div>

        <div class="zaher-popup__timer">
            <p class="zaher-popup__timer-label">Ponuda ističe za:</p>
            <div class="zaher-popup__countdown">
                <div class="zaher-popup__unit">
                    <span class="zaher-popup__digit" data-unit="minutes">15</span>
                    <small>min</small>
                </div>
                <span class="zaher-popup__sep">:</span>
                <div class="zaher-popup__unit">
                    <span class="zaher-popup__digit" data-unit="seconds">00</span>
                    <small>sek</small>
                </div>
            </div>
        </div>

        <a href="#" class="zaher-popup__cta js-popup-cta-btn" rel="nofollow">
            <span class="js-popup-cta-label">Da, želim ovu ponudu</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
            </svg>
        </a>

        <button type="button" class="zaher-popup__skip js-popup-skip">
            Ne, ostajem na trenutnoj pretplati
        </button>

    </div>
</div>
