<?php
add_action('rest_api_init', function() {
    register_rest_route('mcp/v1', '/execute', [
        'methods' => 'POST',
        'callback' => ['MCP_WC_Executor', 'handle'],
        'permission_callback' => '__return_true'
    ]);
});