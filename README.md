<p align="center">
  <img src="docs/assets/webmania-logo.png" width="396" alt="Webmania">
</p>

# Nota Fiscal Eletrônica para WooCommerce

Através do emissor de Nota Fiscal da Webmania®, você conta com a emissão e arquivamento das suas notas fiscais, cálculo automático de impostos, geração do Danfe para impressão e envio automático de e-mails para os clientes.

- **Plugin Oficial no WordPress: [Instalar plugin](https://wordpress.org/plugins/nota-fiscal-eletronica-woocommerce/)**
- Emissor de Nota Fiscal Webmania®: [Saiba mais](https://webmaniabr.com/nota-fiscal-eletronica/)
- Documentação REST API: [Visualizar](https://webmaniabr.com/docs/rest-api-nfe/)

## Compatibilidade verificada

| WordPress | WooCommerce | PHP |
| --- | --- | --- |
| 4.7.38 | 3.0.9 | 7.4 |
| 7.1.3 | 11.2.1 | 8.3 e 8.5 |

O plugin requer PHP 7.4 ou superior, WordPress 4.7 ou superior e WooCommerce 3.0 ou superior. Os testes incluem armazenamento tradicional de pedidos e HPOS, campos clássicos e Checkout Blocks. A interface está disponível em Português (Brasil).

A tabela registra os ambientes exercitados. Use o PHP exigido pela versão da sua plataforma e valide as integrações da loja em homologação antes de atualizar.

## Requisitos

- Contrate um dos planos de Nota Fiscal da Webmania®: [Assine agora mesmo](https://webmaniabr.com/nota-fiscal-eletronica/).
- Instale o módulo do WooCommerce da Webmania® e configure conforme instruções.

## Instalação do Módulo

[Instale o plugin com apenas um clique](https://wordpress.org/plugins/nota-fiscal-eletronica-woocommerce/), logo em seguida ative no painel do WordPress.

Para instalar manualmente o código deste repositório (**3.4.6**), baixe o ZIP em **Code > Download ZIP**, extraia-o e copie os arquivos para `wp-content/plugins/nota-fiscal-eletronica-woocommerce/`. Depois, ative o plugin no WordPress. A publicação no diretório WordPress é independente da atualização deste repositório.

<p align="center">
<img src="https://webmaniabr.com/wp-content/uploads/2016/03/FDD69828-39D4-4EEE-B4E8-C0CE4B2F5899.png">
</p>

## Instruções

- [Configurar credenciais](https://ajuda.webmaniabr.com/hc/pt-br/articles/360013107872-Configurar-credenciais-no-WooCommerce)
- [Configurar Impostos](https://ajuda.webmaniabr.com/hc/pt-br/articles/360013334051-Configurar-impostos-no-WooCommerce)
- [Emitir Nota Fiscal](https://ajuda.webmaniabr.com/hc/pt-br/articles/360013106692-Emiss%C3%A3o-de-NF-e-no-WooCommerce)

## Hooks no módulo de Nota Fiscal WooCommerce

Hooks do WordPress são recursos que permitem que você manipule processos sem modificar o arquivo no núcleo do WordPress e do módulo de Nota Fiscal WooCommerce. Dessa forma é possível realizar diversas integrações, como a emissão de NFC-e no ponto de venda, de forma simplificada e sem interferir nos arquivos do módulo.

[Visualizar lista de Actions e Filters](https://ajuda.webmaniabr.com/hc/pt-br/articles/360052999091)

## Controle das Notas Fiscais

Você pode gerenciar todas as Notas Fiscais e realizar a impressão do Danfe no painel da Webmania®. Simples e fácil.

<p align="center">
<img src="https://wmbr.s3.amazonaws.com/img/dashboard_webmaniabr_01.jpg">
</p>

## Atualizações desta versão

- Aceita CNPJ numérico e alfanumérico nos campos fiscais e na integração.
- Protege credenciais, operações administrativas, notificações e documentos fiscais.
- Preserva a identificação do IP do cliente quando a loja usa Cloudflare, com validação dos proxies confiáveis.
- Atualiza a geração de PDFs e preserva campos, traduções, HPOS e Checkout Blocks.

## Suporte

Qualquer dúvida acesse o [Painel Webmania®](https://webmaniabr.com/painel/) para *abrir um chamado* ou conversar em tempo real no *chat*.

## Contribuições

Agradecemos a todos que contribuem para o emissor de Nota Fiscal ser cada vez melhor.

- @Shirkit - Adaptações para NFC-e no ponto de venda
