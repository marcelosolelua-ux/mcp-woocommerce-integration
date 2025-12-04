# MCP Integration for WooCommerce

## 🚀 Descrição

Plugin WordPress/WooCommerce que implementa o protocolo MCP (Model Context Protocol) para permitir controle completo da loja por agentes de IA.

## 📍 Repositório

[https://github.com/marcelosolelua-ux/mcp-woocommerce-integration](https://github.com/marcelosolelua-ux/mcp-woocommerce-integration)

## 📦 Instalação

### ⚠️ IMPORTANTE: Como Instalar Corretamente

O GitHub adiciona o nome do branch ao ZIP (`-main`), então você precisa renomear a pasta:

**Método 1: Renomear e Recompactar (Recomendado)**

1. Clique em **Code → Download ZIP** no GitHub
2. Extraia o arquivo `mcp-woocommerce-integration-main.zip`
3. **Renomeie** a pasta extraída de `mcp-woocommerce-integration-main` para `mcp-woocommerce`
4. **Compacte** novamente a pasta `mcp-woocommerce` (clique com botão direito → Enviar para → Pasta compactada)
5. No WordPress: **Plugins → Adicionar Novo → Enviar Plugin**
6. Faça upload do novo ZIP e clique em **Instalar Agora**
7. Ative o plugin

**Método 2: Via FTP (Mais Rápido)**

1. Baixe e extraia o ZIP do GitHub
2. Renomeie a pasta para `mcp-woocommerce`
3. Envie via FTP para `/wp-content/plugins/mcp-woocommerce/`
4. Ative no painel do WordPress

**Método 3: Clone via Git (Avançado)**

```bash
cd /caminho/para/wp-content/plugins/
git clone https://github.com/marcelosolelua-ux/mcp-woocommerce-integration.git mcp-woocommerce
```

### Estrutura Esperada Após Instalação

```
/wp-content/plugins/mcp-woocommerce/
├── mcp-woocommerce.php          (arquivo principal)
├── README.md
├── inc/
│   ├── rest.php
│   ├── utils.php
│   ├── executor.php
│   ├── class-admin.php
│   └── methods/
│       ├── class-product.php
│       ├── class-order.php
│       ├── class-customer.php
│       └── class-store.php
└── logs/
```

### Requisitos

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+

## 🛠️ Configuração

1. Após ativar, vá em **MCP WooCommerce** no menu lateral
2. Clique em **Gerar Novo Token**
3. Dê um nome e selecione as permissões (Read/Write/Admin)
4. Copie o token gerado
5. Use o endpoint: `https://seusite.com/wp-json/mcp/v1/execute`

### Configuração rápida no GPTMaker (Terminal MCP)

- **URL do servidor MCP:** `https://seusite.com/wp-json/mcp/v1/execute`
- **Autenticação:** envie o token no header `X-MCP-Key: <seu_token>` ou `Authorization: Bearer <seu_token>` (o GPTMaker costuma usar o padrão Bearer).
- **Capacidades automáticas:** `https://seusite.com/wp-json/mcp/v1/capabilities` retorna o manifesto JSON pronto para ser importado no painel do GPTMaker (inclui suporte a `Authorization: Bearer`).
- **Formato:** JSON-RPC 2.0 (campos `jsonrpc`, `id`, `method`, `params`).

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

### Exemplo com cURL

```bash
curl -X POST https://seusite.com/wp-json/mcp/v1/execute \
  -H "Content-Type: application/json" \
  -H "X-MCP-Key: seu_token_aqui" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "wc.get_product",
    "params": {"id": 123}
  }'
```

## 📚 Métodos Disponíveis

### Produtos
- `wc.get_product` - Obter produto por ID
- `wc.search_products` - Buscar produtos por nome/SKU
- `wc.list_products` - Listar produtos com paginação
- `wc.update_stock` - Atualizar estoque
- `wc.update_price` - Atualizar preço
- `wc.create_product` - Criar novo produto

### Pedidos
- `wc.get_order` - Obter pedido por ID
- `wc.list_orders` - Listar pedidos
- `wc.create_order` - Criar novo pedido
- `wc.update_order_status` - Atualizar status do pedido

### Clientes
- `wc.get_customer` - Obter cliente por ID ou email
- `wc.list_customers` - Listar clientes
- `wc.create_customer` - Criar novo cliente

### Loja
- `wc.get_store_info` - Informações gerais da loja
- `wc.get_categories` - Listar categorias de produtos
- `wc.get_coupons` - Listar cupons ativos

## 🛡️ Segurança

- ✅ Rate limiting: 60 requisições/minuto por token
- ✅ Bloqueio automático após 5 erros consecutivos
- ✅ Logs detalhados de todas operações
- ✅ Sanitização completa de inputs
- ✅ Sistema de permissões granular (read/write/admin)
- ✅ Tokens de 64 caracteres gerados com random_bytes()

## 📄 Capabilities JSON

O plugin gera o manifesto automático para agentes de IA:

- Endpoint REST: `https://seusite.com/wp-json/mcp/v1/capabilities`
- Arquivo local (opcional): `https://seusite.com/wp-content/plugins/mcp-woocommerce/capabilities.json`

## 🐛 Solução de Problemas

### Plugin não aparece na lista
- Verifique se a pasta está nomeada como `mcp-woocommerce` (sem `-main`)
- Confirme que o arquivo `mcp-woocommerce.php` está na raiz da pasta do plugin
- Verifique se WooCommerce está ativo

### Erro ao ativar
- Certifique-se que WooCommerce está instalado
- Verifique versão do PHP (mínimo 7.4)
- Confira logs em `/wp-content/plugins/mcp-woocommerce/logs/`

### Token não funciona
- Verifique se o header é `X-MCP-Key` (com hífen)
- Confirme que o token está ativo no painel admin
- Veja os logs para detalhes do erro

## 📝 Logs

Todos os logs são salvos em:
```
/wp-content/plugins/mcp-woocommerce/logs/mcp-logs.log
```

Você pode visualizá-los também no painel **MCP WooCommerce → Logs Recentes**

## ✅ Testes rápidos locais

Para validar a sintaxe PHP antes de instalar no WordPress, execute na raiz do repositório:

```bash
for f in mcp-woocommerce.php $(find inc -name '*.php'); do php -l "$f"; done
```

O comando deve retornar "No syntax errors detected" para todos os arquivos.

O arquivo `capabilities.json` é regenerado automaticamente em cada carregamento do plugin se a versão ou o protocolo mudarem, garantindo que o manifesto usado pelo GPTMaker esteja sempre sincronizado.

## 📄 Licença

MIT License

## 👨‍💻 Autor

Desenvolvido por ChatGPT conforme especificações RPD v1.0

## 🔗 Links Úteis

- [Documentação WooCommerce REST API](https://woocommerce.github.io/woocommerce-rest-api-docs/)
- [JSON-RPC 2.0 Specification](https://www.jsonrpc.org/specification)
- [Model Context Protocol](https://modelcontextprotocol.io/)