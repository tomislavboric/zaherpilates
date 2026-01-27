document.addEventListener('DOMContentLoaded', () => {
  if (!window.jQuery) {
    return;
  }

  const $ = window.jQuery;

  $(document).on('favorites-updated-single', (event, favorites, postId, siteId, status) => {
    if (status !== 'inactive') {
      return;
    }

    const card = document.querySelector(`#moji-favoriti .cards__item[data-favorite-post-id="${postId}"]`);
    if (!card) {
      return;
    }

    card.remove();

    const list = document.querySelector('#moji-favoriti .cards');
    if (list && list.children.length === 0) {
      const empty = document.querySelector('#moji-favoriti .favorites-empty--inline');
      if (empty) {
        empty.style.display = '';
      }
    }

    const count = document.querySelector('#catalog-tab-favorites .tab__count');
    if (count) {
      const current = parseInt(count.textContent.replace(/\D/g, ''), 10);
      if (!Number.isNaN(current)) {
        count.textContent = `(${Math.max(0, current - 1)})`;
      }
    }
  });
});
