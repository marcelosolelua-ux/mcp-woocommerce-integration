<?php
class MCP_WC_Store {
    
    public static function get_store_info($params) {
        return [
            'name' => get_bloginfo('name'),
            'url' => get_site_url(),
            'description' => get_bloginfo('description'),
            'email' => get_option('admin_email'),
            'currency' => get_woocommerce_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'country' => WC()->countries->get_base_country(),
            'timezone' => wp_timezone_string(),
            'woocommerce_version' => WC()->version,
            'wordpress_version' => get_bloginfo('version'),
        ];
    }
    
    public static function get_categories($params) {
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);
        
        $categories = [];
        foreach ($terms as $term) {
            $categories[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count,
                'parent' => $term->parent,
            ];
        }
        
        return ['categories' => $categories];
    }
    
    public static function get_coupons($params) {
        $page = absint($params['page'] ?? 1);
        $per_page = min(absint($params['per_page'] ?? 10), 100);
        
        $args = [
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        $coupons_query = new WP_Query($args);
        $coupons = [];
        
        if ($coupons_query->have_posts()) {
            while ($coupons_query->have_posts()) {
                $coupons_query->the_post();
                $coupon = new WC_Coupon(get_the_ID());
                $coupons[] = [
                    'id' => $coupon->get_id(),
                    'code' => $coupon->get_code(),
                    'amount' => floatval($coupon->get_amount()),
                    'discount_type' => $coupon->get_discount_type(),
                    'description' => $coupon->get_description(),
                    'date_expires' => $coupon->get_date_expires() ? $coupon->get_date_expires()->date('Y-m-d') : null,
                    'usage_count' => $coupon->get_usage_count(),
                    'usage_limit' => $coupon->get_usage_limit(),
                ];
            }
            wp_reset_postdata();
        }
        
        return [
            'coupons' => $coupons,
            'total' => $coupons_query->found_posts,
            'page' => $page
        ];
    }
}