# HTTP Hooks para PrestaShop

![HTTP Hooks Logo](https://img.shields.io/badge/PrestaShop-%5E1.7-blue)  
Este módulo permite fazer requisições HTTP a uma API externa em vários eventos do PrestaShop, como adição de produtos, validação de pedidos, e muito mais. Ideal para integrar o PrestaShop com sistemas de terceiros de forma automatizada e eficiente.

## 🚀 Download

Você pode fazer o download da versão mais recente do módulo clicando no link abaixo:

- [Versão 1.0.0](https://github.com/felipelm3g/HTTP_Hooks/raw/main/versions/HTTP_Hooks_1.0.1.zip)

## 🛠️ Instalação

### Passo 1: Baixar o Módulo

1. Faça o download do módulo no link acima.

### Passo 2: Acessar o Painel de Administração do PrestaShop

1. No painel de administração do PrestaShop, vá até o menu lateral e clique em **Módulos**.
2. Em seguida, clique em **Module Manager**.

### Passo 3: Fazer o Upload do Módulo

1. Dentro de **Module Manager**, clique no botão **Upload a module**.
2. Selecione o arquivo `HTTP_Hooks_1.0.0.zip` que você acabou de baixar e faça o upload.

**Nota:** Caso ocorra algum erro durante o upload, tente novamente. Em alguns casos, pode ser necessário tentar 3 ou 4 vezes para que o processo seja concluído corretamente. Se o problema persistir após várias tentativas, por favor, informe o erro para que possamos investigar e corrigir o problema.

### Passo 4: Ativar o Módulo

1. Após o upload, o módulo será listado na sua página de módulos instalados.
2. Clique em **Ativar** para ativar o módulo **HTTP Hooks**.

### Passo 5: Configuração

1. Após ativar o módulo, vá até a seção **Configuração** dentro do PrestaShop.
2. No painel de configuração do módulo, você poderá definir:
   - **Chave da API**: Insira a chave da API para autenticação nas requisições.
   - **URL da API externa**: Defina a URL base da API que será chamada.
   - **Caminho da URL (Path)**: Insira o caminho específico da URL onde o evento deve ser enviado. Se não for definido um caminho, o evento não fará a requisição HTTP.

Esses parâmetros são essenciais para que o módulo funcione corretamente e envie os dados para a API externa nos eventos configurados.

## ⚙️ Configuração dos Eventos

Este módulo envia requisições HTTP para a API externa em diversos eventos do PrestaShop. Os eventos são:

- **Produto Adicionado**
- **Produto Atualizado**
- **Produto Excluído**
- **Pedido Validado**
- **Status de Pedido Atualizado**
- **Carrinho Salvo**
- **Atualização de Quantidade no Carrinho**
- **Novo Cliente Criado**
- **Login de Cliente**
- **Logout de Cliente**
- **Confirmação de Pagamento**

## 📜 Licença

Este módulo é distribuído sob a licença [MIT License](LICENSE).

## 👥 Contribuindo

Se você deseja contribuir com este módulo, sinta-se à vontade para fazer um **fork** do repositório, fazer as alterações e enviar um **pull request**. Agradecemos qualquer contribuição!

---

**Desenvolvedor**: [FelipeLM3G](https://github.com/felipelm3g)  
**Licença**: MIT  