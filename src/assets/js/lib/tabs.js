document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', function() {
    // Remove active class from current active tab and content
    const activeTab = document.querySelector('.tab.active');

    if (activeTab) {
      activeTab.classList.remove('active');

      const activeContent = document.querySelector(activeTab.dataset.tabTarget);
      if (activeContent) {
        activeContent.classList.remove('active');
      }
    }

    // Add active class to clicked tab and its content
    this.classList.add('active');

    const newContent = document.querySelector(this.dataset.tabTarget);
    if (newContent) {
      newContent.classList.add('active');
    }
  });
});
