<?php
/**
 * Plugin Name: MCP Integration for WooCommerce
 * Description: Integração MCP (Model Context Protocol) + WooCommerce para controle por IA.
 * Version: 1.0
 * Author: ChatGPT
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) exit;

define('MCP_WC_VERSION', '1.0');
define('MCP_WC_PATH', plugin_dir_path(__FILE__));
define('MCP_WC_URL', plugin_dir_url(__FILE__));

add_action('admin_init', function() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Este plugin requer WooCommerce ativo.');
    }
});

require_once(MCP_WC_PATH . 'inc/utils.php');
require_once(MCP_WC_PATH . 'inc/rest.php');
require_once(MCP_WC_PATH . 'inc/executor.php');
require_once(MCP_WC_PATH . 'inc/class-admin.php');

require_once(MCP_WC_PATH . 'inc/methods/class-product.php');
require_once(MCP_WC_PATH . 'inc/methods/class-order.php');
require_once(MCP_WC_PATH . 'inc/methods/class-customer.php');
require_once(MCP_WC_PATH . 'inc/methods/class-store.php');

register_activation_hook(__FILE__, 'mcp_wc_activate');
function mcp_wc_activate() {
    MCP_WC_Utils::create_keys_table();
    MCP_WC_Utils::create_logs_dir();
    MCP_WC_Utils::create_capabilities_file();
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

add_action('plugins_loaded', function() {
    if (is_admin()) {
        new MCP_WC_Admin();
    }
});