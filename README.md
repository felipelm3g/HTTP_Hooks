# HTTP Hooks para PrestaShop

Este módulo permite fazer requisições HTTP a uma API externa em vários eventos do PrestaShop, como adição de produtos, validação de pedidos, etc.

## Download
[Versão](https://github.com/felipelm3g/HTTP_Hooks/raw/main/versions/HTTP_Hooks_1.0.0.zip)

## Instalação

1. Faça o upload do módulo para a pasta `modules/` do seu PrestaShop.
2. Vá até o painel de administração do PrestaShop e ative o módulo **HTTP Hooks**.

## Configuração

- Defina a chave da API e a URL da API externa no arquivo `HTTP_Hooks.php`.

## Eventos

O módulo envia requisições para a API nos seguintes eventos:

- Produto Adicionado
- Produto Atualizado
- Produto Excluído
- Pedido Validado
- Status de Pedido Atualizado
- Carrinho Salvo
- Atualização de Quantidade no Carrinho
- Novo Cliente Criado
- Login de Cliente
- Logout de Cliente
- Confirmação de Pagamento

## Licença

Este módulo é distribuído sob a licença [MIT License](LICENSE).