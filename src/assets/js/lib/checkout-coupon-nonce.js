// Refreshes the MemberPress coupon AJAX nonce when the cached one expires.
// Activates only on checkout pages where window.themeMemberPressCouponNonce
// has been set via wp_localize_script in inc/custom/memberpress.php.

(function(window, $) {
  if (!$ || !window.themeMemberPressCouponNonce) {
    return;
  }

  var config = window.themeMemberPressCouponNonce;
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

    var $input = findCouponInput(payload);
    if ($input.length && $input.data('themeCouponNonceRetrying')) {
      options.themeCouponNonceRetried = true;
    }
  });

  function retryCouponRequest(payload, settings) {
    var $input;

    if (!isCouponRequest(payload) || (settings && settings.themeCouponNonceRetried)) {
      return;
    }

    $input = findCouponInput(payload);

    if (!$input.length || $input.data('themeCouponNonceRetrying')) {
      return;
    }

    $input.data('themeCouponNonceRetrying', true);

    refreshCouponNonce().done(function() {
      if (payload.action === 'mepr_get_checkout_state') {
        $input.closest('.mepr-signup-form').trigger('meprUpdateCheckoutState');
      } else {
        $input.trigger('blur');
      }
    }).always(function() {
      setTimeout(function() {
        $input.removeData('themeCouponNonceRetrying');
      }, 250);
    });
  }

  $(document).ajaxError(function(event, jqXHR, settings) {
    if (jqXHR && jqXHR.status === 403) {
      retryCouponRequest(parseData(settings && settings.data), settings);
    }
  });

  $(document).ajaxSuccess(function(event, jqXHR, settings, response) {
    var payload = parseData(settings && settings.data);

    // MemberPress returns HTTP 200 with success:false for an expired checkout
    // nonce. Retry it once, just as we do for validation's explicit HTTP 403.
    if (payload.action === 'mepr_get_checkout_state' && response && response.success === false) {
      retryCouponRequest(payload, settings);
    }
  });

  // Use the page's nonce normally. Only a failed request refreshes it;
  // refreshing before every click/Enter needlessly serializes three requests.
})(window, window.jQuery);
