<?php
/**
 * Plugin Name: MelloRise Headless WooPayments Bridge
 * Description: Connects headless checkout orders paid with WooPayments back to the MelloRise frontend.
 */

if (!defined('ABSPATH')) {
    exit;
}

function mellorise_headless_is_woopayments_order($order) {
    return is_a($order, 'WC_Order')
        && 'true' === (string) $order->get_meta('_headless_checkout')
        && 'woopayments' === (string) $order->get_meta('_headless_payment_provider');
}

function mellorise_headless_safe_return_url($url) {
    $url = trim((string) $url);
    if ('' === $url) {
        return '';
    }

    $url = esc_url_raw($url);
    $parts = wp_parse_url($url);
    if (
        empty($parts['scheme']) ||
        empty($parts['host']) ||
        !in_array(strtolower($parts['scheme']), array('http', 'https'), true)
    ) {
        return '';
    }

    return $url;
}

add_filter(
    'woocommerce_get_return_url',
    function ($return_url, $order) {
        if (!mellorise_headless_is_woopayments_order($order)) {
            return $return_url;
        }

        $success_url = mellorise_headless_safe_return_url(
            $order->get_meta('_headless_success_url')
        );
        if (!$success_url) {
            return $return_url;
        }

        return add_query_arg(
            array(
                'provider' => 'woopayments',
                'order_id' => $order->get_id(),
                'order_key' => $order->get_order_key(),
            ),
            $success_url
        );
    },
    10,
    2
);

function mellorise_headless_mark_woopayments_ready($order_id) {
    $order = wc_get_order($order_id);
    if (!mellorise_headless_is_woopayments_order($order)) {
        return;
    }

    $order->update_meta_data('_headless_shipping_pending', 'true');
    $order->update_meta_data('_wiio_ready_for_sync', 'true');
    $order->update_meta_data('_wiio_sync_source', 'woopayments_order_paid');
    $order->update_meta_data('_wiio_dispatch_status', 'queued_in_woocommerce');
    $order->update_meta_data('_wiio_dispatch_attempted_at', gmdate('c'));
    $order->save();
}

add_action('woocommerce_payment_complete', 'mellorise_headless_mark_woopayments_ready', 10, 1);
add_action('woocommerce_order_status_processing', 'mellorise_headless_mark_woopayments_ready', 10, 1);
