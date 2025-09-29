jQuery(function ($) {
  $('.burger').click(function() {
      // Toggle 'burger--active' class
      $(this).toggleClass('burger--active');

      // Toggle 'header--menu-open' class on body
      $('body').toggleClass('header--menu-open');

      // Check if body has the 'header--menu-open' class
      if ($('body').hasClass('header--menu-open')) {
          // Set visibility and opacity of the header-menu
          $('.header-menu').css({
              'visibility': 'visible',
              'opacity': '1'
          });
      } else {
          // Reset visibility and opacity of the header-menu
          $('.header-menu').css({
              'visibility': 'hidden',
              'opacity': '0'
          });
      }
  });
});
