<?php
class MCP_WC_Utils {
    
    public static function create_keys_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'mcp_keys';
        $charset = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            name VARCHAR(100) DEFAULT '',
            created DATETIME NOT NULL,
            last_used DATETIME,
            request_count BIGINT DEFAULT 0,
            error_count INT DEFAULT 0,
            status ENUM('active','inactive') DEFAULT 'active',
            permissions TEXT,
            INDEX(token),
            INDEX(status)
        ) $charset;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function create_logs_dir() {
        $log_dir = MCP_WC_PATH . 'logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . '/mcp-logs.log';
        if (!file_exists($log_file)) {
            file_put_contents($log_file, "# MCP WooCommerce Logs\n");
        }
        
        $htaccess = $log_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all");
        }
    }

    public static function create_capabilities_file() {
        file_put_contents(
            MCP_WC_PATH . 'capabilities.json',
            json_encode(self::get_capabilities(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    public static function ensure_capabilities_file() {
        $path = MCP_WC_PATH . 'capabilities.json';
        $current = self::get_capabilities();

        if (!file_exists($path)) {
            self::create_capabilities_file();
            return;
        }

        $stored = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            self::create_capabilities_file();
            return;
        }

        $version_mismatch = ($stored['version'] ?? '') !== $current['version'];
        $protocol_mismatch = ($stored['protocol'] ?? '') !== $current['protocol'];

        if ($version_mismatch || $protocol_mismatch) {
            self::create_capabilities_file();
        }
    }

    public static function get_capabilities() {
        return [
            "version" => "1.1",
            "protocol" => "json-rpc-2.0",
            "endpoint" => rest_url('mcp/v1/execute'),
            "authentication" => [
                "headers" => ["X-MCP-Key", "Authorization: Bearer <token>"],
                "token_length" => 64,
                "notes" => "Use Bearer padrão ou X-MCP-Key para compatibilidade total"
            ],
            "methods" => [
                "wc.get_product" => ["description" => "Obter detalhes de um produto", "params" => ["id" => "number"], "permission" => "read"],
                "wc.search_products" => ["description" => "Buscar produtos por nome/SKU/descrição", "params" => ["query" => "string", "page" => "number", "per_page" => "number"], "permission" => "read"],
                "wc.list_products" => ["description" => "Listar produtos com paginação", "params" => ["page" => "number", "per_page" => "number", "status" => "string"], "permission" => "read"],
                "wc.update_stock" => ["description" => "Atualizar quantidade em estoque", "params" => ["id" => "number", "quantity" => "number"], "permission" => "write"],
                "wc.update_price" => ["description" => "Atualizar preço do produto", "params" => ["id" => "number", "price" => "number"], "permission" => "write"],
                "wc.create_product" => ["description" => "Criar novo produto", "params" => ["name" => "string", "type" => "string", "price" => "number"], "permission" => "write"],
                "wc.get_order" => ["description" => "Obter detalhes de um pedido", "params" => ["id" => "number"], "permission" => "read"],
                "wc.list_orders" => ["description" => "Listar pedidos com filtros", "params" => ["page" => "number", "per_page" => "number", "status" => "string"], "permission" => "read"],
                "wc.create_order" => ["description" => "Criar novo pedido", "params" => ["customer_id" => "number", "items" => "array"], "permission" => "write"],
                "wc.update_order_status" => ["description" => "Atualizar status do pedido", "params" => ["id" => "number", "status" => "string"], "permission" => "write"],
                "wc.get_customer" => ["description" => "Obter dados de um cliente", "params" => ["id" => "number", "email" => "string"], "permission" => "read"],
                "wc.list_customers" => ["description" => "Listar clientes", "params" => ["page" => "number", "per_page" => "number"], "permission" => "read"],
                "wc.create_customer" => ["description" => "Criar novo cliente", "params" => ["email" => "string", "first_name" => "string"], "permission" => "write"],
                "wc.get_store_info" => ["description" => "Informações gerais da loja", "params" => [], "permission" => "read"],
                "wc.get_categories" => ["description" => "Listar categorias de produtos", "params" => [], "permission" => "read"],
                "wc.get_coupons" => ["description" => "Listar cupons ativos", "params" => ["page" => "number", "per_page" => "number"], "permission" => "read"]
            ]
        ];
    }

    public static function log($message, $level = 'INFO', $context = []) {
        $log_file = MCP_WC_PATH . 'logs/mcp-logs.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $log_entry = sprintf(
            "[%s] [%s] [IP:%s] %s %s\n",
            $timestamp,
            $level,
            $ip,
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        error_log($log_entry, 3, $log_file);
        
        if (file_exists($log_file) && filesize($log_file) > 10485760) {
            rename($log_file, MCP_WC_PATH . 'logs/mcp-logs-' . date('YmdHis') . '.log');
        }
    }

    public static function validate_token($token) {
        global $wpdb;
        $table = $wpdb->prefix . 'mcp_keys';

        $token = trim((string) $token);
        $token = sanitize_text_field($token);

        if (empty($token) || strlen($token) !== 64) {
            self::log('Token inválido: formato incorreto', 'ERROR');
            return ['ok' => false, 'error' => 'Token inválido'];
        }
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE token=%s",
            $token
        ));
        
        if (!$row) {
            self::log('Token não encontrado', 'ERROR', ['token' => substr($token, 0, 10) . '...']);
            return ['ok' => false, 'error' => 'Token não encontrado'];
        }
        
        if ($row->status !== 'active') {
            self::log('Token inativo', 'ERROR', ['token_id' => $row->id]);
            return ['ok' => false, 'error' => 'Token inativo'];
        }
        
        if ($row->error_count >= 5) {
            $wpdb->update($table, ['status' => 'inactive'], ['id' => $row->id]);
            self::log('Token bloqueado por excesso de erros', 'CRITICAL', ['token_id' => $row->id]);
            return ['ok' => false, 'error' => 'Token bloqueado'];
        }
        
        $wpdb->update($table, [
            'last_used' => current_time('mysql'),
            'request_count' => $row->request_count + 1,
            'error_count' => 0
        ], ['id' => $row->id]);
        
        return [
            'ok' => true,
            'token_row' => $row,
            'permissions' => json_decode($row->permissions ?? '["read"]', true)
        ];
    }

    public static function rate_limit_ok($token) {
        $transient_key = 'mcp_rate_' . md5($token . floor(time() / 60));
        $count = get_transient($transient_key);
        
        if ($count === false) {
            set_transient($transient_key, 1, 60);
            return true;
        }
        
        if ($count >= 60) {
            self::log('Rate limit excedido', 'WARNING', ['token' => substr($token, 0, 10) . '...']);
            return false;
        }
        
        set_transient($transient_key, $count + 1, 60);
        return true;
    }

    public static function check_permission($permissions, $required) {
        if (in_array('admin', $permissions)) return true;
        return in_array($required, $permissions);
    }

    public static function response_error($code, $message, $data = [], $payload = []) {
        self::log('Erro resposta', 'ERROR', ['code' => $code, 'message' => $message]);
        return new WP_REST_Response([
            'jsonrpc' => '2.0',
            'id' => $payload['id'] ?? null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'data' => $data
            ]
        ], 200);
    }

    public static function response_success($id, $result) {
        return new WP_REST_Response([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result
        ], 200);
    }

    public static function generate_token() {
        return bin2hex(random_bytes(32));
    }

    public static function sanitize_params($params) {
        if (!is_array($params)) return [];
        
        $sanitized = [];
        foreach ($params as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize_params($value);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = is_float($value) ? floatval($value) : absint($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = sanitize_text_field($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}