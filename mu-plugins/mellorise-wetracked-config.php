<?php
/**
 * Plugin Name: MelloRise WeTracked Runtime Config
 * Description: Keeps the official wetracked.io WooCommerce plugin configured from container environment.
 */

defined('ABSPATH') || exit;

add_action('init', function () {
    $api_key = defined('MELLORISE_WETRACKED_API_KEY')
        ? MELLORISE_WETRACKED_API_KEY
        : getenv('WETRACKED_API_KEY');

    $api_key = is_string($api_key) ? trim($api_key) : '';

    if ($api_key === '') {
        return;
    }

    if (get_option('wt_api_key') !== $api_key) {
        update_option('wt_api_key', $api_key, false);
    }

    set_transient('wt_pixel_bridge_api_key_cache_key', $api_key, 12 * HOUR_IN_SECONDS);
}, 5);
