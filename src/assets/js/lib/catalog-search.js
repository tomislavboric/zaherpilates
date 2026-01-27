const initCatalogSearch = () => {
  const form = document.querySelector('.catalog-tabs__search[data-search-endpoint]');
  if (!form) {
    return;
  }

  const input = form.querySelector('.catalog-tabs__search-input');
  const results = form.querySelector('.catalog-tabs__search-results');
  const endpoint = form.getAttribute('data-search-endpoint');
  const placeholderImage = form.getAttribute('data-search-placeholder') || '';

  if (!input || !results || !endpoint) {
    return;
  }

  let abortController = null;
  let debounceId = null;
  let lastQuery = '';

  const closeResults = () => {
    results.classList.remove('is-open');
    results.setAttribute('aria-hidden', 'true');
    results.removeAttribute('aria-busy');
    results.innerHTML = '';
  };

  const openResults = () => {
    results.classList.add('is-open');
    results.setAttribute('aria-hidden', 'false');
  };

  const renderEmpty = () => {
    results.innerHTML = '<div class="catalog-tabs__search-empty">Nema rezultata.</div>';
    openResults();
  };

  const getPlainText = (html) => {
    const temp = document.createElement('div');
    temp.innerHTML = html || '';
    return temp.textContent || temp.innerText || '';
  };

  const getImageUrl = (item) => {
    if (item && item._embedded && item._embedded['wp:featuredmedia']) {
      const media = item._embedded['wp:featuredmedia'][0];
      const sizes = media && media.media_details ? media.media_details.sizes : null;
      if (sizes) {
        const preferred =
          sizes['fp-small'] ||
          sizes.medium ||
          sizes.thumbnail;
        if (preferred && preferred.source_url) {
          return preferred.source_url;
        }
      }
      if (media && media.source_url) {
        return media.source_url;
      }
    }
    return placeholderImage;
  };

  const renderItems = (items) => {
    results.innerHTML = '';
    const fragment = document.createDocumentFragment();

    items.forEach((item) => {
      if (!item || !item.link) {
        return;
      }

      const titleHtml = item.title && item.title.rendered ? item.title.rendered : '';
      const titleText = getPlainText(titleHtml);
      const imageUrl = getImageUrl(item);

      const link = document.createElement('a');
      link.className = 'catalog-tabs__search-result';
      link.href = item.link;
      link.setAttribute('role', 'option');

      if (imageUrl) {
        const thumb = document.createElement('span');
        thumb.className = 'catalog-tabs__search-thumb';

        const img = document.createElement('img');
        img.src = imageUrl;
        img.alt = titleText;
        img.loading = 'lazy';

        thumb.appendChild(img);
        link.appendChild(thumb);
      }

      const title = document.createElement('span');
      title.className = 'catalog-tabs__search-title';
      title.innerHTML = titleHtml;

      link.appendChild(title);
      fragment.appendChild(link);
    });

    results.appendChild(fragment);
    openResults();
  };

  const fetchResults = (query) => {
    if (abortController) {
      abortController.abort();
    }

    abortController = new AbortController();
    results.setAttribute('aria-busy', 'true');

    const url = `${endpoint}?search=${encodeURIComponent(query)}&per_page=6&_embed=1&_fields=title,link,_embedded`;

    fetch(url, { signal: abortController.signal, credentials: 'same-origin' })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Search request failed');
        }
        return response.json();
      })
      .then((data) => {
        if (query !== input.value.trim()) {
          return;
        }

        results.removeAttribute('aria-busy');

        if (!Array.isArray(data) || data.length === 0) {
          renderEmpty();
          return;
        }

        renderItems(data);
      })
      .catch((error) => {
        if (error.name === 'AbortError') {
          return;
        }
        closeResults();
      });
  };

  const scheduleFetch = () => {
    const query = input.value.trim();
    if (query.length < 2) {
      lastQuery = query;
      closeResults();
      return;
    }

    if (query === lastQuery) {
      return;
    }

    lastQuery = query;
    window.clearTimeout(debounceId);
    debounceId = window.setTimeout(() => fetchResults(query), 200);
  };

  input.addEventListener('input', scheduleFetch);
  input.addEventListener('focus', scheduleFetch);
  input.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeResults();
      input.blur();
    }
  });
  form.addEventListener('submit', () => {
    closeResults();
  });
  document.addEventListener('click', (event) => {
    if (!form.contains(event.target)) {
      closeResults();
    }
  });
};

document.addEventListener('DOMContentLoaded', initCatalogSearch);
