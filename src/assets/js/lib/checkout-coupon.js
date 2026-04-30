import $ from 'jquery';

(function($) {
  const formatCheckoutPrice = function(value) {
    if (!value) {
      return value;
    }

    return String(value)
      .replace(/(\d)\.(?=\d{2}\b)/g, '$1,')
      .replace(/\s*with\s+coupon\s+/giu, ' uz kupon ')
      .replace(/\bFree\s+forever\b/giu, 'Besplatno zauvijek')
      .replace(/\s*\/\s*\d+\s+(?:Mjesec[a-zčćžšđ]*|Mjeseci|Godin[a-zčćžšđ]*|Tjed[a-zčćžšđ]*|Dan[a-zčćžšđ]*|Year[s]?|Month[s]?|Week[s]?|Day[s]?)\b/giu, '')
      .trim();
  };

  const keepCouponToggleVisible = function($toggle) {
    $toggle.removeClass('mepr-hidden').css('display', 'inline-flex');
  };

  const isCheckoutForm = function($form) {
    return $form.find('.mepr-checkout-container').length > 0;
  };

  const isExplicitlyShown = function(element) {
    return element.style && element.style.display && element.style.display !== 'none';
  };

  const hasVisibleCouponFeedback = function($label) {
    const hasLoader = $label.find('.mepr-coupon-loader').toArray().some(function(element) {
      const $element = $(element);

      return isExplicitlyShown(element) || (!$element.hasClass('mepr-hidden') && $element.is(':visible'));
    });

    const hasMessage = $label.find('.cc-error, .cc-success').toArray().some(function(element) {
      const $element = $(element);

      return $.trim($element.text()).length > 0 && (isExplicitlyShown(element) || $element.is(':visible'));
    });

    return hasLoader || hasMessage;
  };

  const BUTTON_LABELS = {
    apply: 'Primijeni',
    loading: 'Provjeravam…',
    remove: 'Ukloni',
  };

  const isElementVisible = function($element) {
    if (!$element.length) {
      return false;
    }

    const element = $element.get(0);

    if ($element.hasClass('mepr-hidden')) {
      return false;
    }

    if (isExplicitlyShown(element)) {
      return true;
    }

    if (element.style && element.style.display === 'none') {
      return false;
    }

    return $element.is(':visible');
  };

  const getCouponState = function($field) {
    const $label = $field.find('.mepr-coupon-feedback').first();
    const $loader = $label.find('.mepr-coupon-loader').first();
    const $success = $label.find('.cc-success').first();
    const $error = $label.find('.cc-error').first();
    const $input = $field.find('.mepr-coupon-code').first();
    const inputValue = $.trim($input.val() || '');
    const appliedValue = $input.data('coupon-applied-value') || '';

    if (isElementVisible($loader)) {
      return 'loading';
    }

    if (isElementVisible($success)) {
      if (appliedValue && appliedValue !== inputValue) {
        return inputValue ? 'idle' : 'empty';
      }

      return 'success';
    }

    if (isElementVisible($error) && $.trim($error.text()).length > 0) {
      return inputValue ? 'error' : 'empty';
    }

    return inputValue ? 'idle' : 'empty';
  };

  const updateCouponButton = function($field) {
    if (!$field || !$field.length) {
      return;
    }

    const $button = $field.find('.mepr-checkout-coupon-apply').first();

    if (!$button.length) {
      return;
    }

    const state = getCouponState($field);
    const isDisabled = state === 'loading' || state === 'empty';
    let label = BUTTON_LABELS.apply;

    if (state === 'loading') {
      label = BUTTON_LABELS.loading;
    } else if (state === 'success') {
      label = BUTTON_LABELS.remove;
    }

    $button
      .text(label)
      .attr('data-coupon-state', state)
      .prop('disabled', isDisabled)
      .attr('aria-disabled', String(isDisabled));

    if (state === 'success') {
      const $input = $field.find('.mepr-coupon-code').first();
      $input.data('coupon-applied-value', $.trim($input.val() || ''));
    } else if (state === 'idle' || state === 'empty' || state === 'error') {
      const $input = $field.find('.mepr-coupon-code').first();
      $input.removeData('coupon-applied-value');
    }
  };

  const updateCouponFeedback = function(context) {
    const $context = context ? $(context) : $(document);
    const $labels = $context.is('.mepr-coupon-feedback')
      ? $context
      : $context.find('.mepr-coupon-feedback');

    $labels.each(function() {
      const $label = $(this);
      const $field = $label.closest('.mepr-checkout-coupon-field');

      $label.toggleClass('is-empty', !hasVisibleCouponFeedback($label));

      if ($field.length) {
        updateCouponButton($field);
      }
    });
  };

  const scheduleCouponFeedbackUpdate = function(context) {
    setTimeout(function() {
      updateCouponFeedback(context);
    }, 0);
  };

  const observeCouponFeedback = function() {
    $('.mepr-coupon-feedback').each(function() {
      if (this.dataset.couponFeedbackObserved) {
        return;
      }

      this.dataset.couponFeedbackObserved = 'true';

      new MutationObserver(function() {
        scheduleCouponFeedbackUpdate(this);
      }.bind(this)).observe(this, {
        attributes: true,
        attributeFilter: ['class', 'style'],
        childList: true,
        characterData: true,
        subtree: true,
      });
    });
  };

  $('body').on('click', '.mepr-checkout-coupon-toggle', function(e) {
    e.preventDefault();

    const $toggle = $(this);
    const $coupon = $(this).closest('.mepr-checkout-coupon');
    const $field = $coupon.find('.mepr-checkout-coupon-field');
    const isOpen = !$field.hasClass('mepr-hidden');

    keepCouponToggleVisible($toggle);
    $coupon.toggleClass('is-open', !isOpen);
    $toggle.toggleClass('is-open', !isOpen).attr('aria-expanded', String(!isOpen));
    $field.toggleClass('mepr-hidden', isOpen).css('display', isOpen ? 'none' : 'flex');

    setTimeout(function() {
      keepCouponToggleVisible($toggle);
      observeCouponFeedback();
      updateCouponFeedback($field.find('.mepr-coupon-feedback'));
    }, 0);
  });

  $('body').on('mousedown', '.mepr-checkout-coupon-apply', function(e) {
    e.preventDefault();
  });

  $('body').on('click', '.mepr-checkout-coupon-apply', function(e) {
    e.preventDefault();

    const $button = $(this);

    if ($button.prop('disabled')) {
      return;
    }

    const $field = $button.closest('.mepr-checkout-coupon-field');
    const $input = $field.find('.mepr-coupon-code');
    const isRemoval = $button.attr('data-coupon-state') === 'success';

    if (isRemoval) {
      $input.val('').removeData('coupon-applied-value');
    }

    $input.trigger('blur');
    scheduleCouponFeedbackUpdate($input.prev('.mepr-coupon-feedback'));
  });

  $('body').on('mepr-validate-field focus blur input', '.mepr-coupon-code', function() {
    scheduleCouponFeedbackUpdate($(this).prev('.mepr-coupon-feedback'));
  });

  const escapeHtml = function(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  const wrapInvoiceAmountHtml = function($form, formattedPriceHtml) {
    const $cell = $form.find('div.mepr_price_cell.invoice-amount').first();
    const periodText = $cell.length ? String($cell.attr('data-billing-period') || '') : '';
    let html = '<span class="invoice-amount-value">' + formattedPriceHtml + '</span>';

    if (periodText) {
      html += '<span class="invoice-amount-period">' + escapeHtml(periodText) + '</span>';
    }

    return html;
  };

  $('body').on('meprPriceStringUpdated', '.mepr-signup-form', function(e, data) {
    if (!isCheckoutForm($(this))) {
      return;
    }

    if (data && data.price_string) {
      const formatted = formatCheckoutPrice(data.price_string);
      data.price_string = wrapInvoiceAmountHtml($(this), formatted);
    }
  });

  $('body').on('meprAfterPriceStringUpdated', '.mepr-signup-form', function(e, data) {
    if (!isCheckoutForm($(this))) {
      return;
    }

    const $submit = $(this).find('.mepr-submit');
    const submitLabel = $submit.data('submit-label');

    if (!submitLabel || !data || !data.price_string) {
      return;
    }

    const $parsed = $('<div />').html(data.price_string);
    const $valueOnly = $parsed.find('.invoice-amount-value');
    const priceText = ($valueOnly.length ? $valueOnly.text() : $parsed.text())
      .replace(/\s+/g, ' ')
      .trim();

    if (priceText) {
      $submit.val(`${submitLabel} · ${priceText}`);
    }
  });

  $(function() {
    observeCouponFeedback();
    updateCouponFeedback(document);
  });
})(jQuery);
