<?php
/**
 * Disable WooCommerce block styles (front-end).
 */
function themesharbor_disable_woocommerce_block_styles() {
  wp_dequeue_style( 'wc-blocks-style' );
}
add_action( 'wp_enqueue_scripts', 'themesharbor_disable_woocommerce_block_styles' );

/**
 * Disable WooCommerce block styles (back-end).
 */
function themesharbor_disable_woocommerce_block_editor_styles() {
  wp_deregister_style( 'wc-block-editor' );
  wp_deregister_style( 'wc-blocks-style' );
}
add_action( 'enqueue_block_assets', 'themesharbor_disable_woocommerce_block_editor_styles', 1, 1 );
