<?php
/**
 * Plugin Name: MCP Integration for WooCommerce
 * Description: Integração MCP (Model Context Protocol) + WooCommerce para controle por IA.
 * Version: 1.0.1
 * Author: ChatGPT
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Text Domain: mcp-woocommerce
 */

if (!defined('ABSPATH')) exit;

// Definir constantes
define('MCP_WC_VERSION', '1.0.1');
define('MCP_WC_PATH', plugin_dir_path(__FILE__));
define('MCP_WC_URL', plugin_dir_url(__FILE__));

// Verificar WooCommerce na ativação
register_activation_hook(__FILE__, 'mcp_wc_check_requirements');
function mcp_wc_check_requirements() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>Erro de Ativação</h1>' .
            '<p>O plugin <strong>MCP Integration for WooCommerce</strong> requer o WooCommerce ativo.</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">&larr; Voltar para Plugins</a></p>',
            'Plugin requer WooCommerce',
            ['back_link' => true]
        );
    }
}

// Carregar arquivos apenas se WooCommerce estiver ativo
add_action('plugins_loaded', 'mcp_wc_init', 20);
function mcp_wc_init() {
    // Verificar WooCommerce novamente em tempo de execução
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p><strong>MCP WooCommerce:</strong> Plugin requer WooCommerce ativo.</p></div>';
        });
        return;
    }
    
    // Carregar arquivos core
    require_once(MCP_WC_PATH . 'inc/utils.php');
    require_once(MCP_WC_PATH . 'inc/rest.php');
    require_once(MCP_WC_PATH . 'inc/executor.php');
    
    // Carregar admin apenas no painel
    if (is_admin()) {
        require_once(MCP_WC_PATH . 'inc/class-admin.php');
        new MCP_WC_Admin();
    }
    
    // Carregar métodos
    require_once(MCP_WC_PATH . 'inc/methods/class-product.php');
    require_once(MCP_WC_PATH . 'inc/methods/class-order.php');
    require_once(MCP_WC_PATH . 'inc/methods/class-customer.php');
    require_once(MCP_WC_PATH . 'inc/methods/class-store.php');
}

// Hook de ativação
register_activation_hook(__FILE__, 'mcp_wc_activate');
function mcp_wc_activate() {
    // Verificar WooCommerce antes de ativar
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>Erro: WooCommerce não encontrado</h1>' .
            '<p>Por favor, instale e ative o WooCommerce antes de ativar este plugin.</p>' .
            '<p><a href="' . admin_url('plugin-install.php?s=woocommerce&tab=search&type=term') . '">Instalar WooCommerce</a> | ' .
            '<a href="' . admin_url('plugins.php') . '">Voltar para Plugins</a></p>',
            'WooCommerce Necessário'
        );
    }
    
    // Carregar utils para criar estruturas
    require_once(MCP_WC_PATH . 'inc/utils.php');
    
    // Criar tabela de tokens
    MCP_WC_Utils::create_keys_table();
    
    // Criar diretório de logs
    MCP_WC_Utils::create_logs_dir();
    
    // Criar arquivo capabilities.json
    MCP_WC_Utils::create_capabilities_file();
    
    // Registrar rotas REST
    flush_rewrite_rules();
}

// Hook de desativação
register_deactivation_hook(__FILE__, 'mcp_wc_deactivate');
function mcp_wc_deactivate() {
    flush_rewrite_rules();
}