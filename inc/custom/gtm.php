<?php
// GTM u <head>
add_action('wp_head', 'zaher_add_gtm_head', 1);
function zaher_add_gtm_head() {
  ?>
  <!-- Google Tag Manager -->
  <script>
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-TZGV2HD6');
  </script>
  <!-- End Google Tag Manager -->
  <?php
}
