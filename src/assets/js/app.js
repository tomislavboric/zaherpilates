import $ from 'jquery';
import whatInput from 'what-input';

window.$ = $;

import './lib/foundation-explicit-pieces';
import './lib/sticky-header.js';
import './lib/mobile-menu.js';
import './lib/tabs.js';
import './lib/comments.js';
import './lib/bf-countdown.js';
// import './lib/lazy-loading.js';

$(document).foundation();

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

document.addEventListener('DOMContentLoaded', function () {
  const link = document.querySelector('.mepr-invoice-print ~ a'); // selektira link nakon gumba "Ispiši"
  if (link) {
    link.textContent = 'Započni trenirati';
    link.setAttribute('href', '/katalog/');
  }
});
