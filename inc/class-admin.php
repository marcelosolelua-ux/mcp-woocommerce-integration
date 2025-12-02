<?php
class MCP_WC_Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_mcp_create_token', [$this, 'create_token']);
        add_action('admin_post_mcp_revoke_token', [$this, 'revoke_token']);
        add_action('admin_post_mcp_delete_token', [$this, 'delete_token']);
    }
    
    public function add_menu() {
        add_menu_page(
            'MCP Integração',
            'MCP WooCommerce',
            'manage_woocommerce',
            'mcp-woocommerce',
            [$this, 'admin_page'],
            'dashicons-rest-api',
            56
        );
    }
    
    public function admin_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'mcp_keys';
        $tokens = $wpdb->get_results("SELECT * FROM $table ORDER BY created DESC");
        
        ?>
        <div class="wrap">
            <h1>🤖 MCP Integration for WooCommerce</h1>
            
            <div class="card" style="max-width: 100%;">
                <h2>Informações do Endpoint</h2>
                <table class="widefat">
                    <tr>
                        <th style="width:200px">Endpoint</th>
                        <td><code><?php echo rest_url('mcp/v1/execute'); ?></code></td>
                    </tr>
                    <tr>
                        <th>Protocolo</th>
                        <td>JSON-RPC 2.0</td>
                    </tr>
                    <tr>
                        <th>Método</th>
                        <td>POST</td>
                    </tr>
                    <tr>
                        <th>Header Autenticação</th>
                        <td><code>X-MCP-Key: [seu_token]</code></td>
                    </tr>
                    <tr>
                        <th>Capabilities</th>
                        <td><a href="<?php echo MCP_WC_URL . 'capabilities.json'; ?>" target="_blank">capabilities.json</a></td>
                    </tr>
                </table>
            </div>
            
            <h2>Tokens de Acesso</h2>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin: 20px 0;">
                <input type="hidden" name="action" value="mcp_create_token">
                <?php wp_nonce_field('mcp_create_token'); ?>
                
                <table class="form-table">
                    <tr>
                        <th>Nome do Token</th>
                        <td><input type="text" name="token_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th>Permissões</th>
                        <td>
                            <label><input type="checkbox" name="permissions[]" value="read" checked> Leitura</label><br>
                            <label><input type="checkbox" name="permissions[]" value="write"> Escrita</label><br>
                            <label><input type="checkbox" name="permissions[]" value="admin"> Admin (todas)</label>
                        </td>
                    </tr>
                </table>
                
                <button type="submit" class="button button-primary">Gerar Novo Token</button>
            </form>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Token</th>
                        <th>Status</th>
                        <th>Permissões</th>
                        <th>Criado</th>
                        <th>Último Uso</th>
                        <th>Requisições</th>
                        <th>Erros</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tokens)): ?>
                        <tr><td colspan="10">Nenhum token criado ainda.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tokens as $token): ?>
                            <tr>
                                <td><?php echo $token->id; ?></td>
                                <td><?php echo esc_html($token->name); ?></td>
                                <td>
                                    <code style="font-size:11px"><?php echo esc_html(substr($token->token, 0, 20)); ?>...</code>
                                    <button class="button button-small" onclick="copyToken('<?php echo esc_js($token->token); ?>')">Copiar</button>
                                </td>
                                <td>
                                    <span class="badge-<?php echo $token->status; ?>">
                                        <?php echo $token->status === 'active' ? '🟢 Ativo' : '🔴 Inativo'; ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($token->permissions ?: '["read"]'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($token->created)); ?></td>
                                <td><?php echo $token->last_used ? date('d/m/Y H:i', strtotime($token->last_used)) : 'Nunca'; ?></td>
                                <td><?php echo number_format($token->request_count); ?></td>
                                <td><?php echo $token->error_count; ?></td>
                                <td>
                                    <?php if ($token->status === 'active'): ?>
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
                                            <input type="hidden" name="action" value="mcp_revoke_token">
                                            <input type="hidden" name="token_id" value="<?php echo $token->id; ?>">
                                            <?php wp_nonce_field('mcp_revoke_token'); ?>
                                            <button type="submit" class="button button-small">Revogar</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline" 
                                          onsubmit="return confirm('Confirma exclusão?')">
                                        <input type="hidden" name="action" value="mcp_delete_token">
                                        <input type="hidden" name="token_id" value="<?php echo $token->id; ?>">
                                        <?php wp_nonce_field('mcp_delete_token'); ?>
                                        <button type="submit" class="button button-small button-link-delete">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <h2>Logs Recentes</h2>
            <div class="card">
                <pre style="max-height:300px; overflow:auto; font-size:11px; background:#f0f0f0; padding:10px;"><?php
                    $log_file = MCP_WC_PATH . 'logs/mcp-logs.log';
                    if (file_exists($log_file)) {
                        $lines = file($log_file);
                        echo esc_html(implode('', array_slice($lines, -50)));
                    } else {
                        echo 'Nenhum log disponível.';
                    }
                ?></pre>
            </div>
        </div>
        
        <script>
        function copyToken(token) {
            navigator.clipboard.writeText(token);
            alert('Token copiado!');
        }
        </script>
        
        <style>
        .badge-active { color: green; font-weight: bold; }
        .badge-inactive { color: red; font-weight: bold; }
        </style>
        <?php
    }
    
    public function create_token() {
        check_admin_referer('mcp_create_token');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Sem permissão');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'mcp_keys';
        
        $name = sanitize_text_field($_POST['token_name']);
        $permissions = $_POST['permissions'] ?? ['read'];
        $token = MCP_WC_Utils::generate_token();
        
        $wpdb->insert($table, [
            'token' => $token,
            'name' => $name,
            'created' => current_time('mysql'),
            'permissions' => json_encode($permissions),
            'status' => 'active'
        ]);
        
        wp_redirect(admin_url('admin.php?page=mcp-woocommerce&success=created'));
        exit;
    }
    
    public function revoke_token() {
        check_admin_referer('mcp_revoke_token');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Sem permissão');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'mcp_keys';
        $token_id = absint($_POST['token_id']);
        
        $wpdb->update($table, ['status' => 'inactive'], ['id' => $token_id]);
        
        wp_redirect(admin_url('admin.php?page=mcp-woocommerce&success=revoked'));
        exit;
    }
    
    public function delete_token() {
        check_admin_referer('mcp_delete_token');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Sem permissão');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'mcp_keys';
        $token_id = absint($_POST['token_id']);
        
        $wpdb->delete($table, ['id' => $token_id]);
        
        wp_redirect(admin_url('admin.php?page=mcp-woocommerce&success=deleted'));
        exit;
    }
}