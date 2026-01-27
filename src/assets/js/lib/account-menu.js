/**
 * Account dropdown menu - click-based toggle
 */
jQuery(function ($) {
  var $trigger = $('.header__account-trigger');
  var $dropdown = $('.header__account-dropdown');

  if (!$trigger.length) return;

  // Toggle dropdown on trigger click
  $trigger.on('click', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var isOpen = $dropdown.hasClass('is-open');

    if (isOpen) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  // Close on click outside
  $(document).on('click', function (e) {
    if (!$(e.target).closest('[data-account-menu]').length) {
      closeMenu();
    }
  });

  // Close on escape key
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      closeMenu();
      $trigger.focus();
    }
  });

  function openMenu() {
    $dropdown.addClass('is-open');
    $trigger.attr('aria-expanded', 'true');
  }

  function closeMenu() {
    $dropdown.removeClass('is-open');
    $trigger.attr('aria-expanded', 'false');
  }
});
