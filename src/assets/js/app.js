import $ from 'jquery';
import whatInput from 'what-input';

window.$ = $;

import './lib/foundation-explicit-pieces';
import './lib/sticky-header.js';
import './lib/mobile-menu.js';
import './lib/tabs.js';
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
    link.setAttribute('href', '/programs/');
  }
});

(function () {
  const wrap = document.getElementById('zp-comment');
  if (!wrap) return;

  const ta = wrap.querySelector('textarea#comment');
  const btnEmoji = wrap.querySelector('.zp-comment__emoji-btn');
  const picker = wrap.querySelector('emoji-picker');
  const counter = wrap.querySelector('.zp-comment__counter');
  const cancelBtn = wrap.querySelector('.zp-comment__cancel');
  const submit = wrap.querySelector('#submit');

  let pickerOpen = false;

  // Start collapsed; expand on focus/typing
  function expand() {
    wrap.classList.remove('is-collapsed');
    wrap.classList.add('is-expanded');
  }
  ta.addEventListener('focus', expand);
  ta.addEventListener('input', () => {
    expand();
    autoResize();
    updateCounter();
    toggleSubmit();
  });

  // Auto-resize
  function autoResize() {
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 320) + 'px';
  }

  // Counter
  function updateCounter() {
    const max = parseInt(ta.getAttribute('maxlength') || '3000', 10);
    const len = ta.value.length;
    counter.textContent = `${len}/${max}`;
  }

  function toggleSubmit() {
    submit.disabled = ta.value.trim().length === 0;
  }

  // Cancel -> collapse & reset (but keep existing WP validation)
  cancelBtn.addEventListener('click', () => {
    ta.value = '';
    updateCounter();
    toggleSubmit();
    ta.style.height = '';
    wrap.classList.remove('is-expanded');
    wrap.classList.add('is-collapsed');
    hidePicker();
  });

  // Emoji picker toggle
  function showPicker() {
    picker.style.display = 'block';
    pickerOpen = true;
  }
  function hidePicker() {
    picker.style.display = 'none';
    pickerOpen = false;
  }
  btnEmoji.addEventListener('click', (e) => {
    e.preventDefault();
    expand();
    pickerOpen ? hidePicker() : showPicker();
  });

  // Insert emoji at cursor
  picker?.addEventListener('emoji-click', (event) => {
    const emoji = event.detail.unicode;
    const start = ta.selectionStart ?? ta.value.length;
    const end = ta.selectionEnd ?? ta.value.length;
    ta.value = ta.value.slice(0, start) + emoji + ta.value.slice(end);
    // Move cursor after emoji
    const newPos = start + emoji.length;
    ta.setSelectionRange(newPos, newPos);
    ta.focus();
    updateCounter();
    toggleSubmit();
  });

  // Close picker if clicked outside
  document.addEventListener('click', (e) => {
    if (!pickerOpen) return;
    if (!wrap.contains(e.target)) hidePicker();
  });

  // Init
  updateCounter();
  toggleSubmit();
})();
