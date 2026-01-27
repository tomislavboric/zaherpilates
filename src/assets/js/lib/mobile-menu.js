jQuery(function ($) {
  function toggleMenu() {
      // Toggle 'header--menu-open' class on body
      $('body').toggleClass('header--menu-open');

      // Toggle burger active state
      $('.burger').toggleClass('burger--active');

      // Update bottom nav menu toggle aria-expanded
      var isOpen = $('body').hasClass('header--menu-open');
      $('.bottom-nav__menu-toggle').attr('aria-expanded', isOpen);

      // Set visibility and opacity of the header-menu
      if (isOpen) {
          $('.header-menu').css({
              'visibility': 'visible',
              'opacity': '1'
          });
      } else {
          $('.header-menu').css({
              'visibility': 'hidden',
              'opacity': '0'
          });
      }
  }

  // Burger menu click
  $('.burger').click(toggleMenu);

  // Bottom nav menu toggle click
  $('.bottom-nav__menu-toggle').click(toggleMenu);
});
