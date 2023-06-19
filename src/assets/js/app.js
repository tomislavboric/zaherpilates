import $ from 'jquery';
import whatInput from 'what-input';

window.$ = $;

import Foundation from 'foundation-sites';
// If you want to pick and choose which modules to include, comment out the above and uncomment
// the line below
//import './lib/foundation-explicit-pieces';

$(document).foundation();

const swiper = new Swiper('.swiper', {
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
      }, 300);
    },
  },
});
