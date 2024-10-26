// Add custom image sizes attribute to enhance responsive image functionality for content images
document.addEventListener("DOMContentLoaded", function () {
  const lazyImages = document.querySelectorAll(".full-image");

  const lazyLoad = (entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        img.src = img.dataset.src;
        img.onload = () => {
          img.style.opacity = "1"; // Fade-in effect for full image
          img.previousElementSibling.style.opacity = "0"; // Fade-out placeholder
        };
        observer.unobserve(img); // Stop observing after loading
      }
    });
  };

  const observer = new IntersectionObserver(lazyLoad, {
    rootMargin: "0px 0px 50px 0px",
    threshold: 0.1
  });

  lazyImages.forEach(img => {
    observer.observe(img);
  });
});
