<?php
class MCP_WC_Executor {
    
    private static $method_registry = [];
    
    public static function init() {
        self::$method_registry = [
            'wc.get_product'         => ['MCP_WC_Product', 'get_product', 'read'],
            'wc.search_products'     => ['MCP_WC_Product', 'search_products', 'read'],
            'wc.list_products'       => ['MCP_WC_Product', 'list_products', 'read'],
            'wc.update_stock'        => ['MCP_WC_Product', 'update_stock', 'write'],
            'wc.update_price'        => ['MCP_WC_Product', 'update_price', 'write'],
            'wc.create_product'      => ['MCP_WC_Product', 'create_product', 'write'],
            
            'wc.get_order'           => ['MCP_WC_Order', 'get_order', 'read'],
            'wc.list_orders'         => ['MCP_WC_Order', 'list_orders', 'read'],
            'wc.create_order'        => ['MCP_WC_Order', 'create_order', 'write'],
            'wc.update_order_status' => ['MCP_WC_Order', 'update_order_status', 'write'],
            
            'wc.get_customer'        => ['MCP_WC_Customer', 'get_customer', 'read'],
            'wc.list_customers'      => ['MCP_WC_Customer', 'list_customers', 'read'],
            'wc.create_customer'     => ['MCP_WC_Customer', 'create_customer', 'write'],
            
            'wc.get_store_info'      => ['MCP_WC_Store', 'get_store_info', 'read'],
            'wc.get_categories'      => ['MCP_WC_Store', 'get_categories', 'read'],
            'wc.get_coupons'         => ['MCP_WC_Store', 'get_coupons', 'read'],
        ];
    }
    
    public static function handle($request) {
        self::init();
        
        $headers = $request->get_headers();
        $body = $request->get_body();
        $payload = json_decode($body, true);
        
        MCP_WC_Utils::log('Nova requisição recebida', 'INFO', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'method' => $payload['method'] ?? 'unknown'
        ]);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return MCP_WC_Utils::response_error(
                -32700,
                'JSON inválido: ' . json_last_error_msg(),
                [],
                []
            );
        }
        
        if (!isset($payload['jsonrpc']) || $payload['jsonrpc'] !== '2.0') {
            return MCP_WC_Utils::response_error(-32600, 'Versão JSON-RPC inválida', [], $payload);
        }
        
        if (!isset($payload['method'], $payload['id'])) {
            return MCP_WC_Utils::response_error(-32600, 'Requisição malformada', [], $payload);
        }
        
        $authorization = $headers['authorization'][0] ?? '';
        $bearer = '';

        if (!empty($authorization)) {
            if (preg_match('/^\s*Bearer\s+(.+)$/i', $authorization, $matches)) {
                $bearer = trim($matches[1]);
            }
        }

        $token = $headers['x_mcp_key'][0] ?? $headers['x-mcp-key'][0] ?? $bearer;
        $auth = MCP_WC_Utils::validate_token($token);
        
        if (!$auth['ok']) {
            return MCP_WC_Utils::response_error(-32002, $auth['error'], [], $payload);
        }
        
        if (!MCP_WC_Utils::rate_limit_ok($token)) {
            return MCP_WC_Utils::response_error(
                -32003,
                'Rate limit excedido (60 req/min)',
                [],
                $payload
            );
        }
        
        $method = sanitize_text_field($payload['method']);
        if (!isset(self::$method_registry[$method])) {
            return MCP_WC_Utils::response_error(-32601, "Método '$method' não encontrado", [], $payload);
        }
        
        $required_permission = self::$method_registry[$method][2];
        if (!MCP_WC_Utils::check_permission($auth['permissions'], $required_permission)) {
            return MCP_WC_Utils::response_error(
                -32002,
                "Sem permissão para executar '$method'",
                ['required' => $required_permission],
                $payload
            );
        }
        
        $params = MCP_WC_Utils::sanitize_params($payload['params'] ?? []);
        
        try {
            $class = self::$method_registry[$method][0];
            $function = self::$method_registry[$method][1];
            
            MCP_WC_Utils::log("Executando $method", 'INFO', ['params' => $params]);
            
            $result = call_user_func([$class, $function], $params);
            
            MCP_WC_Utils::log("$method executado com sucesso", 'INFO');
            
            return MCP_WC_Utils::response_success($payload['id'], $result);
            
        } catch (Exception $e) {
            MCP_WC_Utils::log("Erro ao executar $method", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return MCP_WC_Utils::response_error(
                -32001,
                $e->getMessage(),
                ['method' => $method],
                $payload
            );
        }
    }
}