<?php
class MCP_WC_Order {
    
    public static function get_order($params) {
        $id = absint($params['id'] ?? 0);
        
        if (!$id) throw new Exception('ID do pedido obrigatório');
        
        $order = wc_get_order($id);
        if (!$order) throw new Exception("Pedido #$id não encontrado");
        
        return self::format_order($order);
    }
    
    public static function list_orders($params) {
        $page = absint($params['page'] ?? 1);
        $per_page = min(absint($params['per_page'] ?? 10), 100);
        $status = sanitize_text_field($params['status'] ?? 'any');
        
        $args = [
            'limit' => $per_page,
            'page' => $page,
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        if ($status !== 'any') {
            $args['status'] = 'wc-' . $status;
        }
        
        $orders = wc_get_orders($args);
        $formatted = array_map([self::class, 'format_order'], $orders);
        
        $total_args = array_merge($args, ['limit' => -1]);
        $total = count(wc_get_orders($total_args));
        
        return [
            'orders' => $formatted,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
    
    public static function create_order($params) {
        $customer_id = absint($params['customer_id'] ?? 0);
        $items = $params['items'] ?? [];
        $status = sanitize_text_field($params['status'] ?? 'pending');
        
        if (empty($items)) throw new Exception('Items obrigatórios');
        
        $order = wc_create_order(['customer_id' => $customer_id]);
        
        foreach ($items as $item) {
            $product_id = absint($item['product_id'] ?? 0);
            $quantity = absint($item['quantity'] ?? 1);
            
            if (!$product_id) continue;
            
            $product = wc_get_product($product_id);
            if (!$product) continue;
            
            $order->add_product($product, $quantity);
        }
        
        $order->calculate_totals();
        $order->set_status($status);
        $order->save();
        
        return [
            'id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'total' => floatval($order->get_total()),
            'message' => 'Pedido criado com sucesso'
        ];
    }
    
    public static function update_order_status($params) {
        $id = absint($params['id'] ?? 0);
        $status = sanitize_text_field($params['status'] ?? '');
        $note = sanitize_textarea_field($params['note'] ?? '');
        
        if (!$id) throw new Exception('ID obrigatório');
        if (!$status) throw new Exception('Status obrigatório');
        
        $order = wc_get_order($id);
        if (!$order) throw new Exception("Pedido #$id não encontrado");
        
        $valid_statuses = wc_get_order_statuses();
        $status_key = 'wc-' . $status;
        
        if (!isset($valid_statuses[$status_key])) {
            throw new Exception("Status '$status' inválido");
        }
        
        $order->update_status($status, $note);
        
        return [
            'id' => $id,
            'status' => $order->get_status(),
            'message' => 'Status atualizado com sucesso'
        ];
    }
    
    private static function format_order($order) {
        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = [
                'id' => $item->get_id(),
                'product_id' => $item->get_product_id(),
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'subtotal' => floatval($item->get_subtotal()),
                'total' => floatval($item->get_total()),
            ];
        }
        
        return [
            'id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'currency' => $order->get_currency(),
            'total' => floatval($order->get_total()),
            'subtotal' => floatval($order->get_subtotal()),
            'tax_total' => floatval($order->get_total_tax()),
            'shipping_total' => floatval($order->get_shipping_total()),
            'discount_total' => floatval($order->get_discount_total()),
            'customer_id' => $order->get_customer_id(),
            'billing' => [
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            ],
            'items' => $items,
            'date_created' => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : null,
            'date_modified' => $order->get_date_modified() ? $order->get_date_modified()->date('Y-m-d H:i:s') : null,
        ];
    }
}