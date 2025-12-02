# MCP Integration for WooCommerce

## 🚀 Descrição

Plugin WordPress/WooCommerce que implementa o protocolo MCP (Model Context Protocol) para permitir controle completo da loja por agentes de IA.

## 📋 Recursos

### Produtos
- ✅ Buscar, listar e obter detalhes de produtos
- ✅ Criar novos produtos
- ✅ Atualizar estoque e preços

### Pedidos
- ✅ Listar e consultar pedidos
- ✅ Criar novos pedidos
- ✅ Atualizar status de pedidos

### Clientes
- ✅ Buscar e listar clientes
- ✅ Criar novos clientes
- ✅ Consultar histórico de compras

### Loja
- ✅ Informações gerais da loja
- ✅ Categorias de produtos
- ✅ Cupons de desconto

## 🔧 Instalação

1. Faça download do repositório
2. Comprima a pasta em `mcp-woocommerce-integration.zip`
3. No WordPress, vá em **Plugins → Adicionar Novo → Enviar Plugin**
4. Faça upload do ZIP e ative
5. Acesse **MCP WooCommerce** no menu admin
6. Gere um token de acesso

## 🔐 Autenticação

Todas as requisições devem incluir o header:
```
X-MCP-Key: seu_token_de_64_caracteres
```

## 📡 Endpoint

```
POST /wp-json/mcp/v1/execute
Content-Type: application/json
```

## 📝 Exemplo de Requisição

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "wc.list_products",
  "params": {
    "page": 1,
    "per_page": 10
  }
}
```

## 📚 Métodos Disponíveis

### Produtos
- `wc.get_product` - Obter produto por ID
- `wc.search_products` - Buscar produtos
- `wc.list_products` - Listar produtos
- `wc.update_stock` - Atualizar estoque
- `wc.update_price` - Atualizar preço
- `wc.create_product` - Criar produto

### Pedidos
- `wc.get_order` - Obter pedido
- `wc.list_orders` - Listar pedidos
- `wc.create_order` - Criar pedido
- `wc.update_order_status` - Atualizar status

### Clientes
- `wc.get_customer` - Obter cliente
- `wc.list_customers` - Listar clientes
- `wc.create_customer` - Criar cliente

### Loja
- `wc.get_store_info` - Informações da loja
- `wc.get_categories` - Listar categorias
- `wc.get_coupons` - Listar cupons

## 🛡️ Segurança

- ✅ Rate limiting: 60 requisições/minuto
- ✅ Bloqueio automático após 5 erros
- ✅ Logs detalhados de todas operações
- ✅ Sanitização de inputs
- ✅ Sistema de permissões (read/write/admin)

## 📄 Licença

MIT License

## 👨‍💻 Autor

Desenvolvido por ChatGPT conforme especificações RPD v1.0