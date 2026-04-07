/**
 * Checkout upgrade popup.
 *
 * Shows a timed promotional popup on configured checkout pages.
 * The countdown timer is session-persistent: it starts once per browser
 * session and continues across page refreshes.
 *
 * Config is injected by PHP via wp_localize_script as window.zaherPopupConfig.
 */

(function () {
	'use strict';

	const POPUP_ID       = 'zaher-checkout-popup';
	const TIMER_KEY_BASE = 'zaher_popup_deadline';
	const DISMISSED_KEY_BASE = 'zaher_popup_dismissed';
	const FOCUSABLE_SELECTOR = [
		'a[href]',
		'button:not([disabled])',
		'input:not([disabled]):not([type="hidden"])',
		'select:not([disabled])',
		'textarea:not([disabled])',
		'[tabindex]:not([tabindex="-1"])',
	].join(', ');

	const config = window.zaherPopupConfig || {};
	const parseInteger = function (value, fallback) {
		const parsed = parseInt(value, 10);
		return Number.isNaN(parsed) ? fallback : parsed;
	};
	const readPersistentValue = function (key) {
		try {
			return window.localStorage.getItem(key);
		} catch (error) {
			return sessionStorage.getItem(key);
		}
	};
	const writePersistentValue = function (key, value) {
		try {
			window.localStorage.setItem(key, value);
			return;
		} catch (error) {
			sessionStorage.setItem(key, value);
		}
	};
	const defaults = config.defaults || {};

	// ── DOM ────────────────────────────────────────────────────────────────────

	const popup     = document.getElementById(POPUP_ID);
	if (!popup) return;

	const productInput = document.querySelector('input[name="mepr_product_id"]');
	const currentProductId = parseInt(productInput ? productInput.value : 0, 10);
	const popupConfigs = Array.isArray(config.popups) && config.popups.length ? config.popups : [config];
	const popupConfig = popupConfigs.find(function (item) {
		const sourceProductId = item && (item.sourceProductId || item.monthlyProductId);
		return parseInteger(sourceProductId, 0) === currentProductId;
	});

	if (!currentProductId || !popupConfig) {
		return;
	}

	const TIMER_MS    = Math.max(1, parseInteger(popupConfig.timerMinutes, defaults.timerMinutes || 10)) * 60 * 1000;
	const DELAY_MS    = Math.max(0, parseInteger(popupConfig.delaySeconds, defaults.delaySeconds || 6)) * 1000;
	const TARGET_URL  = popupConfig.targetUrl || '';
	const OLD_PRICE   = popupConfig.oldPrice || '';
	const NEW_PRICE   = popupConfig.newPrice || '';
	const PRICE_BOX   = popupConfig.priceBox || {};
	const TEMPLATE    = popupConfig.template || {};
	const OFFER_VERSION = popupConfig.offerVersion || String(currentProductId);
	const TIMER_KEY   = TIMER_KEY_BASE + '_' + String(OFFER_VERSION);
	const DISMISSED_KEY = DISMISSED_KEY_BASE + '_' + String(OFFER_VERSION);

	const closeBtn  = popup.querySelector('.zaher-popup__close');
	const skipBtn   = popup.querySelector('.js-popup-skip');
	const ctaBtn    = popup.querySelector('.js-popup-cta-btn');
	const card      = popup.querySelector('.zaher-popup__card');
	const minEl     = popup.querySelector('[data-unit="minutes"]');
	const secEl     = popup.querySelector('[data-unit="seconds"]');
	const titleEl   = popup.querySelector('.js-popup-title');
	const subtitleEl = popup.querySelector('.js-popup-subtitle');
	const bodyEl    = popup.querySelector('.js-popup-body');
	const priceKickerEl = popup.querySelector('.js-popup-price-kicker');
	const oldPriceEl = popup.querySelector('.js-popup-old-price');
	const priceArrowEl = popup.querySelector('.js-popup-price-arrow');
	const newPriceEl = popup.querySelector('.js-popup-new-price');
	const priceRenewalEl = popup.querySelector('.js-popup-price-renewal');
	const priceBenefitPrimaryEl = popup.querySelector('.js-popup-price-benefit-primary');
	const ctaLabelEl = popup.querySelector('.js-popup-cta-label');

	const renderPriceText = function (element, value) {
		if (!element) {
			return;
		}

		if (!value) {
			element.textContent = '';
			return;
		}

		const parts = value.split(' / ');

		if (parts.length === 2) {
			element.innerHTML = parts[0] + ' <span>/ ' + parts[1] + '</span>';
			return;
		}

		element.textContent = value;
	};

	const toggleTextElement = function (element, value) {
		if (!element) {
			return;
		}

		element.textContent = value || '';
		element.hidden = !value;
	};

	// ── Inject dynamic content from PHP config ─────────────────────────────────

	if (TEMPLATE.titleHtml && titleEl) {
		titleEl.innerHTML = TEMPLATE.titleHtml;
	}

	if (TEMPLATE.subtitleHtml && subtitleEl) {
		subtitleEl.innerHTML = TEMPLATE.subtitleHtml;
	}

	if (bodyEl) {
		if (TEMPLATE.bodyHtml) {
			bodyEl.innerHTML = TEMPLATE.bodyHtml;
			bodyEl.hidden = false;
		} else {
			bodyEl.innerHTML = '';
			bodyEl.hidden = true;
		}
	}

	if (TARGET_URL && ctaBtn) {
		ctaBtn.setAttribute('href', TARGET_URL);
	}

	if (TEMPLATE.ctaLabel && ctaLabelEl) {
		ctaLabelEl.textContent = TEMPLATE.ctaLabel;
	}

	if (TEMPLATE.skipLabel && skipBtn) {
		skipBtn.textContent = TEMPLATE.skipLabel;
	}

	toggleTextElement(priceKickerEl, PRICE_BOX.kicker || '');

	if (OLD_PRICE && oldPriceEl) {
		renderPriceText(oldPriceEl, OLD_PRICE);
	}

	if (oldPriceEl) {
		oldPriceEl.hidden = !OLD_PRICE;
	}

	if (priceArrowEl) {
		priceArrowEl.hidden = !OLD_PRICE;
	}

	toggleTextElement(priceRenewalEl, PRICE_BOX.renewalNote || '');
	toggleTextElement(priceBenefitPrimaryEl, PRICE_BOX.benefitPrimary || '');

	if (NEW_PRICE && newPriceEl) {
		renderPriceText(newPriceEl, NEW_PRICE);
	}

	// ── Session timer ──────────────────────────────────────────────────────────

	// If user already dismissed this exact offer version, bail out entirely.
	if (readPersistentValue(DISMISSED_KEY)) return;

	let deadline = parseInt(sessionStorage.getItem(TIMER_KEY), 10);

	if (!deadline || isNaN(deadline)) {
		deadline = Date.now() + TIMER_MS;
		sessionStorage.setItem(TIMER_KEY, String(deadline));
	}

	// Timer already expired before popup even loaded → don't show.
	if (Date.now() >= deadline) return;

	// ── Countdown ─────────────────────────────────────────────────────────────

	let intervalId = null;
	let previouslyFocusedElement = null;

	function getFocusableElements() {
		return Array.from(popup.querySelectorAll(FOCUSABLE_SELECTOR)).filter(function (element) {
			return !element.hasAttribute('disabled') && element.getAttribute('aria-hidden') !== 'true';
		});
	}

	function focusPopup() {
		const focusableElements = getFocusableElements();
		const firstElement = focusableElements[0] || card;

		if (firstElement && typeof firstElement.focus === 'function') {
			firstElement.focus();
		}
	}

	function restoreFocus() {
		if (
			previouslyFocusedElement &&
			typeof previouslyFocusedElement.focus === 'function' &&
			document.contains(previouslyFocusedElement)
		) {
			previouslyFocusedElement.focus();
		}

		previouslyFocusedElement = null;
	}

	function updateCountdown() {
		const diff    = Math.max(0, deadline - Date.now());
		const minutes = Math.floor(diff / 60000);
		const seconds = Math.floor((diff % 60000) / 1000);

		if (minEl) minEl.textContent = String(minutes).padStart(2, '0');
		if (secEl) secEl.textContent = String(seconds).padStart(2, '0');

		if (diff === 0) {
			closePopup();
		}
	}

	// ── Show / hide ────────────────────────────────────────────────────────────

	function openPopup() {
		if (Date.now() >= deadline) return; // expired while waiting for delay

		previouslyFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
		updateCountdown();
		popup.setAttribute('aria-hidden', 'false');
		popup.classList.add('is-open');
		document.body.classList.add('zaher-popup-open');
		focusPopup();

		intervalId = setInterval(updateCountdown, 1000);
	}

	function closePopup(options) {
		const settings = Object.assign({
			persistDismissal: false,
		}, options || {});

		clearInterval(intervalId);
		popup.classList.remove('is-open');
		popup.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('zaher-popup-open');

		if (settings.persistDismissal) {
			writePersistentValue(DISMISSED_KEY, '1');
		}

		restoreFocus();
	}

	// ── Event listeners ────────────────────────────────────────────────────────

	if (closeBtn) {
		closeBtn.addEventListener('click', function () {
			closePopup();
		});
	}

	if (skipBtn) {
		skipBtn.addEventListener('click', function () {
			closePopup({ persistDismissal: true });
		});
	}

	if (ctaBtn) {
		ctaBtn.addEventListener('click', function () {
			writePersistentValue(DISMISSED_KEY, '1');
		});
	}

	// Keep interaction contained inside the modal while it is open.
	if (card) {
		card.addEventListener('click', function (e) { e.stopPropagation(); });
	}

	document.addEventListener('keydown', function (e) {
		let focusableElements;
		let firstElement;
		let lastElement;

		if (!popup.classList.contains('is-open')) {
			return;
		}

		if (e.key !== 'Tab') {
			return;
		}

		focusableElements = getFocusableElements();

		if (!focusableElements.length) {
			e.preventDefault();
			if (card) {
				card.focus();
			}
			return;
		}

		firstElement = focusableElements[0];
		lastElement = focusableElements[focusableElements.length - 1];

		if (!popup.contains(document.activeElement)) {
			e.preventDefault();
			(e.shiftKey ? lastElement : firstElement).focus();
			return;
		}

		if (e.shiftKey && document.activeElement === firstElement) {
			e.preventDefault();
			lastElement.focus();
			return;
		}

		if (!e.shiftKey && document.activeElement === lastElement) {
			e.preventDefault();
			firstElement.focus();
		}
	});

	// ── Boot ───────────────────────────────────────────────────────────────────

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			setTimeout(openPopup, DELAY_MS);
		});
	} else {
		setTimeout(openPopup, DELAY_MS);
	}
})();
