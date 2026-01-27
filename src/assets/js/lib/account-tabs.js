document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-account-tabs]').forEach((container) => {
    const tabs = Array.from(container.querySelectorAll('[data-account-tab]'));
    const panels = Array.from(container.querySelectorAll('[data-account-panel]'));

    if (!tabs.length || !panels.length) {
      return;
    }

    const setActive = (name) => {
      tabs.forEach((tab) => {
        const isActive = tab.dataset.accountTab === name;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        tab.tabIndex = isActive ? 0 : -1;
      });

      panels.forEach((panel) => {
        const isActive = panel.dataset.accountPanel === name;
        panel.classList.toggle('is-active', isActive);
        panel.hidden = !isActive;
        panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
      });
    };

    const initialTab = tabs.find((tab) => tab.classList.contains('is-active')) || tabs[0];
    if (initialTab) {
      setActive(initialTab.dataset.accountTab);
    }

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => setActive(tab.dataset.accountTab));
    });
  });
});
