<?php
add_action('rest_api_init', function() {
    register_rest_route('mcp/v1', '/execute', [
        'methods' => 'POST',
        'callback' => ['MCP_WC_Executor', 'handle'],
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('mcp/v1', '/capabilities', [
        'methods' => 'GET',
        'callback' => function () {
            $capabilities = MCP_WC_Utils::get_capabilities();
            return new WP_REST_Response($capabilities, 200);
        },
        'permission_callback' => '__return_true'
    ]);
});