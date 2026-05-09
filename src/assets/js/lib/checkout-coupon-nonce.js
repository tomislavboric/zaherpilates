// Refreshes the MemberPress coupon AJAX nonce when the cached one expires.
// Activates only on checkout pages where window.zaherMemberPressCouponNonce
// has been set via wp_localize_script in inc/custom/memberpress.php.

(function(window, $) {
  if (!$ || !window.zaherMemberPressCouponNonce) {
    return;
  }

  var config = window.zaherMemberPressCouponNonce;
  var refreshRequest = null;

  function decode(value) {
    try {
      return decodeURIComponent(String(value || '').replace(/\+/g, ' '));
    } catch (e) {
      return String(value || '');
    }
  }

  function parseData(data) {
    var output = {};

    if (!data) {
      return output;
    }

    if (typeof data === 'string') {
      data.split('&').forEach(function(part) {
        var pieces;
        var key;

        if (!part) {
          return;
        }

        pieces = part.split('=');
        key = decode(pieces.shift());

        if (key) {
          output[key] = decode(pieces.join('='));
        }
      });

      return output;
    }

    if (typeof data === 'object') {
      $.each(data, function(key, value) {
        output[key] = value;
      });
    }

    return output;
  }

  function setDataValue(data, key, value) {
    var parsed;

    if (!data || typeof data === 'string') {
      parsed = parseData(data);
      parsed[key] = value;

      return $.param(parsed);
    }

    if (typeof data === 'object') {
      data[key] = value;
    }

    return data;
  }

  function getAjaxUrl() {
    if (window.MeprI18n && window.MeprI18n.ajaxurl) {
      return window.MeprI18n.ajaxurl;
    }

    return config.ajaxUrl;
  }

  function getCouponNonce() {
    return window.MeprSignup && window.MeprSignup.coupon_nonce
      ? window.MeprSignup.coupon_nonce
      : '';
  }

  function setCouponNonce(nonce) {
    if (!nonce) {
      return;
    }

    window.MeprSignup = window.MeprSignup || {};
    window.MeprSignup.coupon_nonce = nonce;
  }

  function refreshCouponNonce() {
    if (refreshRequest) {
      return refreshRequest;
    }

    refreshRequest = $.ajax({
      type: 'POST',
      url: getAjaxUrl(),
      dataType: 'json',
      data: {
        action: config.action
      }
    }).done(function(response) {
      if (response && response.success && response.data) {
        setCouponNonce(response.data.coupon_nonce);
      }
    }).always(function() {
      refreshRequest = null;
    });

    return refreshRequest;
  }

  function isCouponRequest(payload) {
    if (!payload || !payload.action) {
      return false;
    }

    if (payload.action === 'mepr_validate_coupon') {
      return true;
    }

    return payload.action === 'mepr_get_checkout_state' && !!payload.mepr_coupon_code;
  }

  function nonceFieldForAction(action) {
    return action === 'mepr_get_checkout_state' ? 'mepr_coupon_nonce' : 'coupon_nonce';
  }

  function findCouponInput(payload) {
    var selector = '.mepr-signup-form .mepr-coupon-code';
    var $inputs = $(selector);

    if (payload.prd_id) {
      $inputs = $inputs.filter(function() {
        return String($(this).data('prdid')) === String(payload.prd_id);
      });
    }

    if (payload.code || payload.mepr_coupon_code) {
      var code = String(payload.code || payload.mepr_coupon_code);
      var $matching = $inputs.filter(function() {
        return String($(this).val()) === code;
      });

      if ($matching.length) {
        return $matching.first();
      }
    }

    return $inputs.first();
  }

  $.ajaxPrefilter(function(options) {
    var payload = parseData(options.data);
    var nonce = getCouponNonce();

    if (!nonce || !isCouponRequest(payload)) {
      return;
    }

    options.data = setDataValue(options.data, nonceFieldForAction(payload.action), nonce);
  });

  $(document).ajaxError(function(event, jqXHR, settings) {
    var payload;
    var $input;

    if (!jqXHR || jqXHR.status !== 403) {
      return;
    }

    payload = parseData(settings && settings.data);

    if (!isCouponRequest(payload)) {
      return;
    }

    $input = findCouponInput(payload);

    if (!$input.length || $input.data('zaherCouponNonceRetrying')) {
      return;
    }

    $input.data('zaherCouponNonceRetrying', true);

    refreshCouponNonce().done(function() {
      if (payload.action === 'mepr_get_checkout_state') {
        $input.closest('.mepr-signup-form').trigger('meprUpdateCheckoutState');
      } else {
        $input.trigger('blur');
      }
    }).always(function() {
      setTimeout(function() {
        $input.removeData('zaherCouponNonceRetrying');
      }, 250);
    });
  });

  document.addEventListener('click', function(event) {
    var button = event.target && event.target.closest
      ? event.target.closest('.mepr-checkout-coupon-apply')
      : null;

    if (!button || button.disabled) {
      return;
    }

    if (button.getAttribute('data-zaher-coupon-nonce-ready') === '1') {
      button.removeAttribute('data-zaher-coupon-nonce-ready');
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    refreshCouponNonce().always(function() {
      button.setAttribute('data-zaher-coupon-nonce-ready', '1');
      button.click();
    });
  }, true);

  document.addEventListener('keydown', function(event) {
    var input = event.target;

    if (!input || event.key !== 'Enter' || !input.matches || !input.matches('.mepr-signup-form .mepr-coupon-code')) {
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    refreshCouponNonce().always(function() {
      input.blur();
    });
  }, true);
})(window, window.jQuery);
