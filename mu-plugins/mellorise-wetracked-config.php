<?php
/**
 * Plugin Name: MelloRise wetracked.io Config
 * Description: Configures the official wetracked.io WooCommerce plugin from environment variables.
 */

if (!defined('ABSPATH')) {
    exit;
}

function mellorise_wetracked_env(string $name): string
{
    $value = getenv($name);
    if (false === $value) {
        return '';
    }

    return trim((string) $value);
}

function mellorise_wetracked_bool_env(string $name, bool $default = true): bool
{
    $value = mellorise_wetracked_env($name);
    if ('' === $value) {
        return $default;
    }

    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
}

function mellorise_wetracked_api_key_option_name(): string
{
    return defined('WT_PIXEL_BRIDGE_API_KEY_OPTION_NAME')
        ? WT_PIXEL_BRIDGE_API_KEY_OPTION_NAME
        : 'wt_api_key';
}

function mellorise_wetracked_configure_api_key(): void
{
    $api_key = mellorise_wetracked_env('WETRACKED_API_KEY');
    if ('' === $api_key) {
        return;
    }

    $option_name = mellorise_wetracked_api_key_option_name();
    $sanitized_key = sanitize_text_field($api_key);

    if (get_option($option_name) !== $sanitized_key) {
        update_option($option_name, $sanitized_key, false);
        delete_transient('wt_pixel_bridge_api_key_cache_key');
    }
}

function mellorise_wetracked_activate_official_plugin(): void
{
    if (!mellorise_wetracked_bool_env('WETRACKED_AUTO_ACTIVATE', true)) {
        return;
    }

    $plugin_file = 'wt-for-woocommerce/wt-for-woocommerce.php';
    $absolute_plugin_file = WP_PLUGIN_DIR . '/' . $plugin_file;

    if (!file_exists($absolute_plugin_file)) {
        return;
    }

    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if (is_plugin_active($plugin_file)) {
        return;
    }

    $result = activate_plugin($plugin_file, '', false, true);
    if (is_wp_error($result)) {
        error_log('MelloRise wetracked.io activation failed: ' . $result->get_error_message());
    }
}

function mellorise_wetracked_admin_notice(): void
{
    if (!current_user_can('activate_plugins')) {
        return;
    }

    if ('' === mellorise_wetracked_env('WETRACKED_API_KEY')) {
        return;
    }

    $plugin_file = WP_PLUGIN_DIR . '/wt-for-woocommerce/wt-for-woocommerce.php';
    if (file_exists($plugin_file)) {
        return;
    }

    echo '<div class="notice notice-warning"><p>';
    echo esc_html__('MelloRise wetracked.io is configured, but the official wt-for-woocommerce plugin is not installed yet.', 'mellorise');
    echo '</p></div>';
}

mellorise_wetracked_configure_api_key();

add_action('plugins_loaded', 'mellorise_wetracked_configure_api_key', 1);
add_action('admin_init', 'mellorise_wetracked_activate_official_plugin', 1);
add_action('admin_notices', 'mellorise_wetracked_admin_notice');
