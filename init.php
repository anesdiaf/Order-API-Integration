<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

// Register hooks
fn_register_hooks(
    'place_order',
    'change_order_status'
);

Registry::set('addons.order_whatsapp_integration.whatsapp_token', 'YOUR_WHATSAPP_API_TOKEN');
Registry::set('addons.order_whatsapp_integration.external_api_url', 'https://your-api.com/orders');
Registry::set('addons.order_whatsapp_integration.marketing_api_url', 'https://your-crm.com/cancelled-orders');