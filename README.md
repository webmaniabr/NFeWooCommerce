# NFeWooCommerce - Nota Fiscal para WooCommerce

Emissão automática ou Manual de Nota Fiscal Eletrônica para WooCommerce através da REST API da WebmaniaBR®. Emita as suas Nota Fiscais sempre que receber um pagamento ou somente no momento em que for enviar os produtos.

- Plugin no Wordpress: https://wordpress.org/plugins/nota-fiscal-eletronica-woocommerce/
- Documentação: https://webmaniabr.com/docs/rest-api-nfe/ 

## Requisitos

- Escolha um plano que se adeque as necessidades da sua empresa. Para saber mais: https://webmaniabr.com/start/nota-fiscal-eletronica/
- Obtenha as credenciais de acesso da sua aplicação.
- Instale o módulo da WebmaniaBR® e configure conforme instruções.

## Instalação do Módulo

Copie todos os arquivos na pasta ```/wp-content/plugins/nota-fiscal-eletronica/```, logo em seguida ative no painel do Wordpress.

<p align="center">
<img src="https://webmaniabr.com/wp-content/uploads/2016/03/FDD69828-39D4-4EEE-B4E8-C0CE4B2F5899.png">
</p>

## Configuração do Módulo

Acesse ```WooCommerce > Configurações > Nota Fiscal``` para configurar os detalhes para a emissão correta da sua Nota Fiscal:

- **Credenciais de Acesso:** Copie e cole as credenciais de acesso enviada pela WebmaniaBR.

<p align="center">
<img src="https://webmaniabr.com/wp-content/uploads/2016/03/71184FE6-259D-4A97-AAE3-5819CF17FB9F.png" height="300">
</p>

- **Configuração Padrão:** Defina se deseja a emissão automática sempre que confirmado um pagamento e informações importantes para a emissão correta da Nota Fiscal. Caso deseje, as informações de Classe de Imposto, NCM, EAN e Origem pode ser definido em cada produto de forma separada.

<p align="center">
<img src="https://webmaniabr.com/atendimento/wp-content/uploads/sites/4/2016/03/img_56fa82f21138c.png" height="300">
<img src="https://webmaniabr.com/atendimento/wp-content/uploads/sites/4/2016/03/img_56fa82c1ed0c1.png" height="300">
</p>

- **Campos Personalizados no Checkout**: Caso a página de Finalizar Compra da sua loja virtual não possua os campos CPF, CNPJ e preenchimento automático do endereço através do CEP, você pode ativar facilmente nessas opções.

<p align="center">
<img src="https://webmaniabr.com/wp-content/uploads/2016/03/1BF38BAC-5D36-4607-90B9-03C5BE99C54F.png" height="200">
</p>

## Emissão das Notas Fiscais

Acompanhe a emissão das Notas Fiscais em ```WooCommerce > Pedidos```. As notas fiscais também podem ser emitidas manualmente em **Ações em Massa** ou na página do Pedido em **Ações do Pedido**.

<p align="center">
<img src="https://webmaniabr.com/wp-content/uploads/2016/03/DAFB5A59-1DB8-4F73-B0BA-14E126A5C17B.png">
<img src="https://webmaniabr.com/wp-content/uploads/2016/03/F10E3E67-9FC0-4D10-BE61-13C0D31C1BE8.png" height="200">
<img src="https://webmaniabr.com/wp-content/uploads/2016/03/5999F78B-C812-4294-9C56-1CDF37A41D94.png" height="200">
</p>

## Desenvolvedores

- **emitir_nfe_produto**: No momento da emissão da NF-e é verificado o filtro para definir se o produto será incluso na Nota Fiscal. ```Exemplo de utilização: Não emitir Nota Fiscal com produtos de revendedores do marketplace.```

```php
function filter_function_name( $response, $post_id ) {
  // Process content here
  return $response;
}
add_filter( 'emitir_nfe_produto', 'filter_function_name', 10, 2 ); 
```

- **nfe**: Post meta onde registra todas as NF-e emitidas no pedido. ```Exemplo de utilização: Possibilidade de integrar com o seu sistema ou fazer aprimoramentos no plugin adicionando maiores funcionalidades. ```

```php
get_post_meta( $order_id, 'nfe', true ); // Array
```

## Controle das Notas Fiscais

Você pode gerenciar todas as Notas Fiscais e realizar a impressão do Danfe no painel da WebmaniaBR®. Simples e fácil.

<p align="center">
<img src="https://webmaniabr.com/wp-content/themes/wmbr/img/nf07.jpg">
</p>


## Suporte

Qualquer dúvida estamos à disposição e abertos para melhorias e sugestões, em breve teremos um fórum para discussões. Qualquer dúvida entre em contato na nossa Central de Atendimento: https://webmaniabr.com/atendimento/.
