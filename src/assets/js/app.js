import $ from 'jquery';
import whatInput from 'what-input';

window.$ = $;

import Foundation from 'foundation-sites';
// If you want to pick and choose which modules to include, comment out the above and uncomment
// the line below
// import './lib/foundation-explicit-pieces';

$(document).foundation();

// jQuery function
$(function() {
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

document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', function() {
    // Remove active class from current active tab and content
    const activeTab = document.querySelector('.tab.active');
    activeTab.classList.remove('active');
    document.querySelector(activeTab.dataset.tabTarget).classList.remove('active');

    // Add active class to clicked tab and its content
    this.classList.add('active');
    document.querySelector(this.dataset.tabTarget).classList.add('active');
  });
});






/* const swiper = new Swiper('.swiper', {
  slidesPerView: 1.2,
  spaceBetween: 16,
  breakpoints: {
    640: {
      slidesPerView: 2
    },
    900: {
      slidesPerView: 3
    },
    1200: {
      slidesPerView: 4
    },
    1400: {
      slidesPerView: 5
    },
    1600: {
      slidesPerView: 6
    }
  },
  pagination: {
    el: ".swiper-pagination",
    type: "fraction",
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
  on: {
    init: function() {
      var slides = document.querySelectorAll('.swiper-slide');
      slides.forEach(function(slide) {
        slide.style.opacity = 1;
      });
    },
    transitionStart: function() {
      var slides = document.querySelectorAll('.swiper-slide');
      slides.forEach(function(slide) {
        slide.classList.add('swiper-no-click');
      });
    },
    transitionEnd: function() {
      setTimeout(() => {
        var slides = document.querySelectorAll('.swiper-slide');
        slides.forEach(function(slide) {
          slide.classList.remove('swiper-no-click');
        });
      }, 200);
    },
  },
}); */
