<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/**
 * Send API call to your endpoint format
 */
function fn_send_to_api_endpoint($phone, $status_code, $order_data = array())
{
    // Clean phone number (remove all non-digits)
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);

    // Get API URL from addon settings
    $api_base_url = Registry::get('addons.order_api_integration.api_base_url');

    // Use default if not configured
    if (empty($api_base_url)) {
        $api_base_url = 'https://b871-41-201-108-209.ngrok-free.app/sms';
    }

    // Remove trailing slash if present
    $api_base_url = rtrim($api_base_url, '/');

    // Build URL with phone and status in path
    $api_url = $api_base_url . '/' . $clean_phone . '/' . $status_code;

    // Get timeout from settings
    $timeout = Registry::get('addons.order_api_integration.timeout_seconds');
    if (empty($timeout) || !is_numeric($timeout)) {
        $timeout = 30;
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'ngrok-skip-browser-warning: true' // Skip ngrok browser warning
    ));

    // Send as GET request (no body needed since data is in URL)
    curl_setopt($ch, CURLOPT_HTTPGET, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Log the API call if logging is enabled
    $enable_logging = Registry::get('addons.order_api_integration.enable_logging');
    if ($enable_logging === 'Y') {
        fn_log_event('api_integration', 'send', array(
            'url' => $api_url,
            'phone' => $clean_phone,
            'status_code' => $status_code,
            'response' => $response,
            'http_code' => $http_code,
            'error' => $error,
            'order_id' => !empty($order_data['order_id']) ? $order_data['order_id'] : null,
            'timestamp' => date('Y-m-d H:i:s')
        ));
    }

    return array('response' => $response, 'http_code' => $http_code, 'error' => $error);
}

/**
 * Handle order creation event
 */
function fn_order_api_integration_place_order($order_id, $action, $order_status, $cart, $auth)
{
    if ($action !== 'save') {
        return;
    }

    // Get order details
    $order_info = fn_get_order_info($order_id);
    if (empty($order_info) || empty($order_info['phone'])) {
        return;
    }

    // Send to your API endpoint
    fn_send_to_api_endpoint($order_info['phone'], $order_status, $order_info);
}

/**
 * Handle order status change event
 */
function fn_order_api_integration_change_order_status($status_to, $status_from, $order_info, $force_notification, $order_statuses, $place_order)
{
    if (empty($order_info) || $status_to === $status_from || empty($order_info['phone'])) {
        return;
    }

    // Send to your API endpoint
    fn_send_to_api_endpoint($order_info['phone'], $status_to, $order_info);
}