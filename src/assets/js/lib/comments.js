// Global variables to avoid reference errors
let emojiPickerInitialized = false;

// Emoji Picker Functions
function toggleEmojiPicker() {
  const panel = document.getElementById('emojiPanel');
  if (panel) {
    panel.classList.toggle('show');
  }
}

function addEmoji(emoji) {
  const textarea = document.querySelector('.comment-textarea');
  if (!textarea) return;

  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const text = textarea.value;

  // Insert emoji at cursor position
  const before = text.substring(0, start);
  const after = text.substring(end, text.length);
  textarea.value = before + emoji + after;

  // Set cursor position after emoji
  textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
  textarea.focus();

  // Trigger auto-resize
  autoResizeTextarea(textarea);

  // Close emoji panel
  const panel = document.getElementById('emojiPanel');
  if (panel) {
    panel.classList.remove('show');
  }
}

function autoResizeTextarea(textarea) {
  textarea.style.height = 'auto';
  textarea.style.height = (textarea.scrollHeight) + 'px';
}

function toggleLike(button) {
  const count = button.querySelector('.like-count');
  if (!count) return;

  const currentLikes = parseInt(count.textContent) || 0;
  const isLiked = button.classList.contains('liked');

  if (isLiked) {
    count.textContent = Math.max(0, currentLikes - 1);
    button.classList.remove('liked');
  } else {
    count.textContent = currentLikes + 1;
    button.classList.add('liked');
  }
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  if (emojiPickerInitialized) return;
  emojiPickerInitialized = true;

  // Auto-expand textarea
  const textarea = document.querySelector('.comment-textarea');
  if (textarea) {
    textarea.addEventListener('input', function() {
      autoResizeTextarea(this);
    });
  }

  // Close emoji picker when clicking outside
  document.addEventListener('click', function(event) {
    const emojiPicker = document.querySelector('.emoji-picker');
    const emojiPanel = document.getElementById('emojiPanel');

    if (emojiPicker && emojiPanel && !emojiPicker.contains(event.target)) {
      emojiPanel.classList.remove('show');
    }
  });

  // Form submission feedback
  const form = document.querySelector('.comment-form-v5');
  if (form) {
    form.addEventListener('submit', function(e) {
      const button = this.querySelector('.comment-submit-btn');
      if (!button) return;

      const original = button.textContent;

      button.textContent = 'Hvala vam! ✨';
      button.disabled = true;

      // Reset button after form processes (or timeout)
      setTimeout(() => {
        if (button.textContent === 'Hvala vam! ✨') {
          button.textContent = original;
          button.disabled = false;
        }
      }, 3000);
    });
  }

  // Initialize like buttons
  document.querySelectorAll('.like-button').forEach(button => {
    if (!button.hasAttribute('data-initialized')) {
      button.setAttribute('data-initialized', 'true');
      button.addEventListener('click', function() {
        toggleLike(this);
      });
    }
  });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
  // Ctrl/Cmd + E to toggle emoji picker
  if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
    const textarea = document.querySelector('.comment-textarea');
    if (textarea && textarea === document.activeElement) {
      e.preventDefault();
      toggleEmojiPicker();
    }
  }

  // Escape to close emoji picker
  if (e.key === 'Escape') {
    const panel = document.getElementById('emojiPanel');
    if (panel) {
      panel.classList.remove('show');
    }
  }
});

// Handle dynamic content (if comments are loaded via AJAX)
function initializeNewComments() {
  document.querySelectorAll('.like-button:not([data-initialized])').forEach(button => {
    button.setAttribute('data-initialized', 'true');
    button.addEventListener('click', function() {
      toggleLike(this);
    });
  });
}

// Public API for WordPress integration
window.PilatesComments = {
  toggleEmojiPicker: toggleEmojiPicker,
  addEmoji: addEmoji,
  toggleLike: toggleLike,
  initializeNewComments: initializeNewComments
};

document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.querySelector('.emoji-toggle');
  if (toggleBtn) toggleBtn.addEventListener('click', toggleEmojiPicker);

  document.querySelectorAll('.emoji-btn').forEach(btn => {
    btn.addEventListener('click', () => addEmoji(btn.textContent.trim()));
  });
});
