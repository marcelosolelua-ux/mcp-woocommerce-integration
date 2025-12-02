# 🚀 Instruções de Instalação

## Problema Comum

Quando você baixa o ZIP diretamente do GitHub via botão "Download ZIP", o arquivo vem com o nome `mcp-woocommerce-integration-main.zip` e cria uma pasta `mcp-woocommerce-integration-main/` que **NÃO é reconhecida** pelo WordPress.

## Solução: Renomear a Pasta

### Método 1: Renomear Antes de Compactar (Recomendado)

1. Baixe o ZIP do GitHub
2. **Extraia** o arquivo baixado
3. **Renomeie** a pasta de `mcp-woocommerce-integration-main` para `mcp-woocommerce`
4. **Compacte** novamente a pasta `mcp-woocommerce` em um novo ZIP
5. Faça upload no WordPress: **Plugins → Adicionar Novo → Enviar Plugin**
6. Ative o plugin

### Método 2: Via FTP (Direto)

1. Baixe e extraia o ZIP do GitHub
2. Renomeie a pasta para `mcp-woocommerce`
3. Faça upload via FTP para `/wp-content/plugins/mcp-woocommerce/`
4. Vá em **Plugins** no WordPress e ative

### Método 3: Download das Releases

1. Vá em **Releases** no repositório GitHub
2. Baixe o arquivo `mcp-woocommerce.zip` (já no formato correto)
3. Instale direto no WordPress

## Estrutura Correta

Depois de instalado, a estrutura deve ficar assim:

```
/wp-content/plugins/mcp-woocommerce/
├── mcp-woocommerce.php
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

## Verificação

Se o plugin não aparecer na lista de plugins, verifique:

1. A pasta está em `/wp-content/plugins/mcp-woocommerce/` (não em subpasta)
2. O arquivo `mcp-woocommerce.php` existe na raiz da pasta do plugin
3. WooCommerce está instalado e ativo

## Após Ativação

1. Vá em **MCP WooCommerce** no menu lateral
2. Gere um token de acesso
3. Use o endpoint: `https://seusite.com/wp-json/mcp/v1/execute`