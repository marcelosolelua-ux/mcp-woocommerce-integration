<?php
class MCP_WC_Customer {
    
    public static function get_customer($params) {
        $id = absint($params['id'] ?? 0);
        $email = sanitize_email($params['email'] ?? '');
        
        $customer = null;
        
        if ($id) {
            $customer = new WC_Customer($id);
        } elseif ($email) {
            $user = get_user_by('email', $email);
            if ($user) $customer = new WC_Customer($user->ID);
        }
        
        if (!$customer || !$customer->get_id()) {
            throw new Exception('Cliente não encontrado');
        }
        
        return self::format_customer($customer);
    }
    
    public static function list_customers($params) {
        $page = absint($params['page'] ?? 1);
        $per_page = min(absint($params['per_page'] ?? 10), 100);
        
        $args = [
            'role' => 'customer',
            'number' => $per_page,
            'paged' => $page,
            'orderby' => 'registered',
            'order' => 'DESC'
        ];
        
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        
        $customers = [];
        foreach ($users as $user) {
            $customer = new WC_Customer($user->ID);
            $customers[] = self::format_customer($customer);
        }
        
        return [
            'customers' => $customers,
            'total' => $user_query->get_total(),
            'page' => $page,
            'per_page' => $per_page
        ];
    }
    
    public static function create_customer($params) {
        $email = sanitize_email($params['email'] ?? '');
        $first_name = sanitize_text_field($params['first_name'] ?? '');
        $last_name = sanitize_text_field($params['last_name'] ?? '');
        $username = sanitize_user($params['username'] ?? $email);
        $password = $params['password'] ?? wp_generate_password();
        
        if (!is_email($email)) throw new Exception('Email inválido');
        if (email_exists($email)) throw new Exception('Email já cadastrado');
        
        $customer = new WC_Customer();
        $customer->set_email($email);
        $customer->set_first_name($first_name);
        $customer->set_last_name($last_name);
        $customer->set_username($username);
        $customer->set_password($password);
        
        $customer_id = $customer->save();
        
        if (!$customer_id) throw new Exception('Erro ao criar cliente');
        
        return [
            'id' => $customer_id,
            'email' => $email,
            'message' => 'Cliente criado com sucesso'
        ];
    }
    
    private static function format_customer($customer) {
        return [
            'id' => $customer->get_id(),
            'email' => $customer->get_email(),
            'first_name' => $customer->get_first_name(),
            'last_name' => $customer->get_last_name(),
            'username' => $customer->get_username(),
            'billing' => [
                'first_name' => $customer->get_billing_first_name(),
                'last_name' => $customer->get_billing_last_name(),
                'company' => $customer->get_billing_company(),
                'address_1' => $customer->get_billing_address_1(),
                'city' => $customer->get_billing_city(),
                'postcode' => $customer->get_billing_postcode(),
                'country' => $customer->get_billing_country(),
                'phone' => $customer->get_billing_phone(),
            ],
            'orders_count' => $customer->get_order_count(),
            'total_spent' => floatval($customer->get_total_spent()),
            'date_created' => $customer->get_date_created() ? $customer->get_date_created()->date('Y-m-d H:i:s') : null,
        ];
    }
}