const initDragScroll = (el) => {
  let isDown = false;
  let startX;
  let scrollLeft;
  let hasDragged = false;

  const onPointerDown = (e) => {
    // Only handle primary button (left click / touch)
    if (e.pointerType === 'mouse' && e.button !== 0) return;

    isDown = true;
    hasDragged = false;
    startX = e.pageX - el.offsetLeft;
    scrollLeft = el.scrollLeft;
  };

  const onPointerMove = (e) => {
    if (!isDown) return;
    const x = e.pageX - el.offsetLeft;
    const walk = (x - startX) * 1.5;

    if (Math.abs(walk) > 8) {
      if (!hasDragged) {
        hasDragged = true;
        el.classList.add('is-dragging');
        el.setPointerCapture(e.pointerId);
      }
      e.preventDefault();
      el.scrollLeft = scrollLeft - walk;
    }
  };

  const onPointerUp = (e) => {
    if (!isDown) return;
    isDown = false;
    el.classList.remove('is-dragging');

    if (hasDragged) {
      try {
        el.releasePointerCapture(e.pointerId);
      } catch (err) {
        // Ignore if not captured
      }
      // Prevent click if user dragged
      const preventClick = (evt) => {
        evt.preventDefault();
        evt.stopPropagation();
      };
      el.addEventListener('click', preventClick, { capture: true, once: true });
    }
  };

  el.addEventListener('pointerdown', onPointerDown);
  el.addEventListener('pointermove', onPointerMove);
  el.addEventListener('pointerup', onPointerUp);
  el.addEventListener('pointercancel', onPointerUp);
};

const initTabs = (group) => {
  const tabs = Array.from(group.querySelectorAll('.tab[data-tab-target]'));

  if (!tabs.length) {
    return;
  }

  // Init drag scroll on tabs bar for mobile
  const tabsBar = group.querySelector('.catalog-tabs__bar');
  if (tabsBar) {
    initDragScroll(tabsBar);
  }

  const setActiveTab = (tab, { updateHash = false } = {}) => {
    tabs.forEach((item) => {
      const isActive = item === tab;
      item.classList.toggle('active', isActive);
      item.setAttribute('aria-selected', isActive ? 'true' : 'false');
      item.tabIndex = isActive ? 0 : -1;

      const target = group.querySelector(item.dataset.tabTarget);
      if (target) {
        target.classList.toggle('active', isActive);
      }
    });

    if (updateHash && tab.dataset.tabTarget) {
      const targetHash = tab.dataset.tabTarget;
      if (history.replaceState) {
        if (window.location.hash !== targetHash) {
          history.replaceState(null, document.title, targetHash);
        }
      } else {
        window.location.hash = targetHash;
      }
    }
  };

  const activateFromHash = () => {
    if (!window.location.hash) {
      return;
    }

    const anchor = document.querySelector(window.location.hash);
    if (!anchor) {
      return;
    }

    const panel = anchor.classList.contains('content-container')
      ? anchor
      : anchor.closest('.content-container');

    if (!panel || !group.contains(panel)) {
      return;
    }

    const tab = tabs.find((item) => item.dataset.tabTarget === `#${panel.id}`);
    if (tab) {
      setActiveTab(tab);
    }
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => setActiveTab(tab, { updateHash: true }));
  });

  if (!tabs.some((tab) => tab.classList.contains('active'))) {
    setActiveTab(tabs[0]);
  }

  activateFromHash();
  window.addEventListener('hashchange', activateFromHash);
};

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-tabs]').forEach(initTabs);
});
