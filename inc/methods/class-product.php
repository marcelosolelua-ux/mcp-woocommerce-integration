<?php
class MCP_WC_Product {
    
    public static function get_product($params) {
        $id = absint($params['id'] ?? 0);
        
        if (!$id) {
            throw new Exception('ID do produto obrigatório');
        }
        
        $product = wc_get_product($id);
        
        if (!$product) {
            throw new Exception("Produto #$id não encontrado");
        }
        
        return self::format_product($product);
    }
    
    public static function search_products($params) {
        $query = sanitize_text_field($params['query'] ?? '');
        $page = absint($params['page'] ?? 1);
        $per_page = min(absint($params['per_page'] ?? 10), 100);
        
        if (empty($query)) {
            throw new Exception('Query de busca obrigatória');
        }
        
        $args = [
            's' => $query,
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish'
        ];
        
        $loop = new WP_Query($args);
        $products = [];
        
        if ($loop->have_posts()) {
            while ($loop->have_posts()) {
                $loop->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $products[] = self::format_product($product);
                }
            }
            wp_reset_postdata();
        }
        
        return [
            'products' => $products,
            'total' => $loop->found_posts,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => $loop->max_num_pages
        ];
    }
    
    public static function list_products($params) {
        $page = absint($params['page'] ?? 1);
        $per_page = min(absint($params['per_page'] ?? 10), 100);
        $status = sanitize_text_field($params['status'] ?? 'publish');
        
        $args = [
            'status' => $status,
            'limit' => $per_page,
            'page' => $page,
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        $products = wc_get_products($args);
        $formatted = array_map([self::class, 'format_product'], $products);
        
        $total_args = array_merge($args, ['limit' => -1]);
        $total = count(wc_get_products($total_args));
        
        return [
            'products' => $formatted,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
    
    public static function update_stock($params) {
        $id = absint($params['id'] ?? 0);
        $quantity = isset($params['quantity']) ? intval($params['quantity']) : null;
        
        if (!$id) throw new Exception('ID obrigatório');
        if ($quantity === null) throw new Exception('Quantidade obrigatória');
        
        $product = wc_get_product($id);
        if (!$product) throw new Exception("Produto #$id não encontrado");
        
        $product->set_stock_quantity($quantity);
        $product->set_manage_stock(true);
        $product->save();
        
        return [
            'id' => $id,
            'stock' => $product->get_stock_quantity(),
            'message' => 'Estoque atualizado com sucesso'
        ];
    }
    
    public static function update_price($params) {
        $id = absint($params['id'] ?? 0);
        $price = isset($params['price']) ? floatval($params['price']) : null;
        
        if (!$id) throw new Exception('ID obrigatório');
        if ($price === null || $price < 0) throw new Exception('Preço inválido');
        
        $product = wc_get_product($id);
        if (!$product) throw new Exception("Produto #$id não encontrado");
        
        $product->set_regular_price($price);
        $product->save();
        
        return [
            'id' => $id,
            'price' => floatval($product->get_price()),
            'message' => 'Preço atualizado com sucesso'
        ];
    }
    
    public static function create_product($params) {
        $name = sanitize_text_field($params['name'] ?? '');
        $type = sanitize_text_field($params['type'] ?? 'simple');
        $price = floatval($params['price'] ?? 0);
        $sku = sanitize_text_field($params['sku'] ?? '');
        $description = wp_kses_post($params['description'] ?? '');
        $short_description = wp_kses_post($params['short_description'] ?? '');
        $stock = isset($params['stock']) ? absint($params['stock']) : null;
        
        if (empty($name)) throw new Exception('Nome do produto obrigatório');
        
        $product = new WC_Product_Simple();
        $product->set_name($name);
        $product->set_status('publish');
        $product->set_regular_price($price);
        
        if ($sku) $product->set_sku($sku);
        if ($description) $product->set_description($description);
        if ($short_description) $product->set_short_description($short_description);
        
        if ($stock !== null) {
            $product->set_manage_stock(true);
            $product->set_stock_quantity($stock);
        }
        
        $product_id = $product->save();
        
        if (!$product_id) throw new Exception('Erro ao criar produto');
        
        return [
            'id' => $product_id,
            'name' => $product->get_name(),
            'message' => 'Produto criado com sucesso'
        ];
    }
    
    private static function format_product($product) {
        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'sku' => $product->get_sku(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'price' => floatval($product->get_price()),
            'regular_price' => floatval($product->get_regular_price()),
            'sale_price' => $product->get_sale_price() ? floatval($product->get_sale_price()) : null,
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => $product->get_stock_status(),
            'manage_stock' => $product->get_manage_stock(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'categories' => array_map(function($term) {
                return ['id' => $term->term_id, 'name' => $term->name];
            }, wp_get_post_terms($product->get_id(), 'product_cat')),
            'permalink' => get_permalink($product->get_id()),
            'date_created' => $product->get_date_created() ? $product->get_date_created()->date('Y-m-d H:i:s') : null,
        ];
    }
}