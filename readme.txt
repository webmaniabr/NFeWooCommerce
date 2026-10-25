=== Nota Fiscal Eletrônica Webmania ===
Contributors: webmaniabr
Tags: nota fiscal, nfe, nfse, woocommerce, webmania
Requires at least: 4.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.4.6
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Issue Brazilian electronic invoices (NF-e and NFS-e) from WooCommerce using the Webmania REST API.

== Description ==

Issue and store Brazilian electronic invoices with the Webmania REST API. The plugin connects WooCommerce orders to Webmania for NF-e and NFS-e issuance, automatic tax calculations, DANFE PDF printing and invoice delivery by email.

Install the plugin and subscribe to a [Webmania invoice service plan](https://webmania.com.br/nota-fiscal-eletronica/). An active plan and API credentials are required. Requests containing order, customer, product and fiscal data are sent to Webmania when invoices are issued or consulted. See the [Webmania terms and privacy policies](https://webmania.com.br/contratos/).

Brazilian Portuguese (pt_BR) is bundled. The plugin supports classic checkout, native fiscal fields in recent WooCommerce Checkout Blocks, traditional order storage and HPOS. Older WooCommerce versions keep the classic checkout flow.

= For businesses =

Connect your store to Brazilian state and municipal tax authorities through Webmania. The service provides invoice storage, reports and tax calculations for ICMS, IPI, PIS, COFINS, ISSQN and other applicable taxes.

* Print several DANFEs in a single PDF.
* Find invoices using reports, filters and searches.
* Integrate NFC-e issuance with a point of sale through the plugin hooks.

= For accountants =

Manage invoices issued, canceled or voided by your clients through the Webmania dashboard. Export invoice reports as CSV, print DANFEs and download XML documents for accounting records and monthly closing.

== Installation ==

* Ative o plugin e acesse **WooCommerce > Configurações > Nota Fiscal** para configurar os detalhes para a emissão correta da sua Nota Fiscal.
* Consulte o nosso guia de emissão de Nota Fiscal para Loja Virtual: [Clique aqui para visualizar](https://webmania.com.br/blog/guia-de-emissao-de-nota-fiscal-para-loja-virtual/).

*Requisitos:* WordPress 4.7 ou superior, PHP 7.4 ou superior e WooCommerce 3.0.0 ou superior. As versões recentes do WooCommerce podem exigir versões mais recentes de WordPress e PHP.

*Testado com:* WordPress 7.1.3 e WooCommerce 11.2.1, com armazenamento tradicional e HPOS.

*Idioma:* Português do Brasil (pt_BR) incluído no plugin. Selecione Português do Brasil em Configurações > Geral > Idioma do site.

O serviço de emissão fiscal requer um plano ativo da Webmania.

== Frequently Asked Questions ==

= Como funciona a emissão de Notas Fiscais no WooCommerce? =

Após instalar, ativar e configurar o plugin você poderá emitir as suas notas fiscais com um clique ou automaticamente após a confirmação de um pagamento. É necessário contratar um dos planos de Nota Fiscal Eletrônica da Webmania® para realizar a emissão da NF-e e NFS-e: [Conheça os planos](https://webmania.com.br/nota-fiscal-eletronica/).

= Como é realizado administração das Notas Fiscais? =

Através do painel da Webmania® você pode realizar o cancelamento da NF-e ou emitir uma Nota Fiscal de Devolução, tudo com apenas um clique. O painel da Webmania também conta com relatórios em tempo real e ferramentas para facilitar o gerenciamento contábil da sua empresa e do contador.

= Posso emitir NFC-e em meu Ponto de Venda? =

Sim, através do plugin de Nota Fiscal da Webmania® você pode fazer as integrações necessárias para emissão e envio para impressora térmica. Solicite ao seu programador de confiança para realizar a integração com o Ponto de Venda através dos Hooks do Wordpress, mais informações: [Hooks no módulo de Nota Fiscal WooCommerce](https://ajuda.webmania.com.br/pt-BR/articles/12681330-hooks-no-modulo-de-nota-fiscal-woocommerce-nfce-e-outros).

= Posso emitir Nota Fiscal de Serviço? (NFS-e) =

Sim, já está disponível no plugin da Webmania a emissão de NFS-e para mais de 5.000 municípios. Você pode ver a lista completa de municípios atendidos aqui: [Municípios homologados para NFS-e](https://ajuda.webmania.com.br/pt-BR/articles/12680670-municipios-homologados-nota-fiscal-de-servico-webmania-nfs-e). Caso não tenha encontrado o seu município, nos informe que realizamos a integração para você.

= Possuo um Marketplace, posso emitir Nota Fiscal para cada vendedor? =

Sim, no emissor da Webmania cada empresa possui as suas próprias credenciais de acesso. Você pode realizar a integração para manipular as credenciais e assim emitir a Nota Fiscal de cada pedido para empresas diferentes. Solicite ao seu programador de confiança para realizar a integração através dos Hooks do Wordpress, mais informações: [Hooks no módulo de Nota Fiscal WooCommerce](https://ajuda.webmania.com.br/pt-BR/articles/12681330-hooks-no-modulo-de-nota-fiscal-woocommerce-nfce-e-outros).

== Screenshots ==

1. Controle todas as Notas Fiscais no painel da Webmania®.
2. Veja se a NF-e/NFS-e foi emitida na página de pedidos do WooCommerce.
3. Emissão de NF-e/NFS-e em massa no WooCommerce.
4. Emissão de NF-e/NFS-e individual na página do pedido do WooCommerce.
5. Visualização das notas emitidas dentro do pedido.
6. Faça a impressão do Danfe.

== Changelog ==

= 3.4.6 =
* Corrigidas regressões na emissão automática com transições aninhadas, nas respostas fiscais com lista de erros e na geração de códigos PDF417 e imagens PNG com Imagick.
* Preservação de status fiscais, séries alfanuméricas de RPS, operações do SDK e integrações de pagamento existentes após as melhorias de segurança.
* Correção da identificação do IP do visitante em lojas que utilizam Cloudflare, com suporte a IPv4, IPv6 e Pseudo IPv4 e validação dos proxies de confiança.
* Isolamento das bibliotecas de PDF e dos dados de fontes, com proteção nas operações de arquivos e nas requisições HTTP, preservando a compatibilidade com PHP 7.4.
* Validação de segurança ao salvar categorias, variações e dados dos pedidos, com uso dos recursos nativos de tradução e das APIs de zonas de entrega do WooCommerce.
* Campos fiscais nativos no checkout em blocos, compatibilidade do nonce no checkout antigo e persistência das informações fiscais do pedido.
* Suporte ao CNPJ alfanumérico: validação, máscaras, envio fiscal e preservação de chaves de acesso com letras.
* Proteção de credenciais, permissões administrativas, callbacks e arquivos de impressão.
* Tratamento de respostas incertas da API para evitar repetição indevida de emissão.
* Correções de compatibilidade com PHP e validação com WordPress 7.1.3 e WooCommerce 11.2.1.
* Tradução pt_BR incluída e carregamento das traduções ajustado.
* Atualização da descrição do serviço e dos requisitos de instalação.

= 3.4.5 =
* Correção de erro na impressão em massa dos DANFEs das notas fiscais.

= 3.4.4 =
* Correção de erro na emissão de NF-e em lote no PHP 8.0+ quando uma taxa do pedido possui valor não numérico.

= 3.4.3 =
* Correção de mensagem de erro indevida do PHP.

= 3.4.2 =
* Correção no salvamento das informações ao editar pedidos.
* Correção no funcionamento do botão de ignorar pedido quando utilizado com HPOS (High-Performance Order Storage).
* Adicionado opção para fechamento dos boxes de mensagens de erro.

= 3.4.1.1 =
* Correção no salvamento das informações fiscais dos produtos.

= 3.4.1.0 =
* Melhorias de segurança no plugin.

= 3.4.0.9 =
* Atualizado a descrição do plugin.

= 3.4.0.7 =
* Melhorias de segurança.
* Correções em mensagens administrativas e registros de erros.

= 3.4.0.6 =
* Melhorias de compatibilidade com a versão 6.8 do Wordpress ou superior e com a versão 10.0 do plugin Woocommerce.
* Correções de bugs e melhorias.

= 3.4.0.5 =
* Hotfix de Compatibilidade com as versões do Wordpress 6.8 ou superior.

= 3.4.0.3 =
* Compatibilidade com as versões do Wordpress 6.7 ou superior.
* Correções de bugs e melhorias de performance. 

= 3.3.9.2 =
* Implementado melhorias de visualização, em relação às novas medidas de segurança do XML e PDF.
* Correções de bugs e melhorias. 

= 3.3.8 =
* Melhorias no envio de e-mails dos erros de emissão. 
* Correção em obter o valor total do frete.

= 3.3.6 =
* Compatibilidade com WooCommerce HPOS (High-Performance Order Storage).
* Correções de bugs e melhorias. 

= 3.3.4 =
* Melhorias no cálculo de taxas do pedido no valor total da NFS-e.
* Correção no cálculo de desconto em pedidos envolvendo o plugin Bundled Products da WooCommerce.
* Melhorias nas validações da emissão automática de notas.

= 3.3.3 =
* Correções de bugs e melhorias relacionados ao PDF das notas fiscais.

= 3.3.2 =
* Adicionado uma opção nas configurações, para inserir o valor das taxas na emissão de NFS-e.

= 3.3.1 =
* Diversas correções de bugs e melhorias. Atualização recomendada.

= 3.3.0 =
* Melhorias nos tipos de desconto para emissão da NFS-e.

= 3.2.10 =
* Correção de bugs e melhorias.

= 3.2.9 =
* Adicionado opção para escolher tipo de desconto (Incondicional ou Condicional) para emissão da NFS-e.

= 3.2.8 =
* Adicionado a funcionalidade para emissão de tomador estrangeiro para NFS-e.
* Correção de bugs e melhorias.

= 3.2.7 =
* Adicionado novo parâmetro para emissão de desconto incondicionado na NFS-e.

= 3.2.6 =
* Adicionado novo campo no pedido para complementar a descrição do serviço na NFS-e.

= 3.2.5.4 =
* Correção de bugs e melhorias.
* Correção da aplicação do desconto na emissão de NFS-e.

= 3.2.5 =
* Compatibilidade para emissão de Nota Fiscal de Serviço (NFS-e).

= 3.2.4.1 =
* Correção de bugs e melhorias.
* Melhorias para compatibilidade com versão do PHP 8.0 e 8.1.

= 3.2.3.1 =
* Melhoria para emissão de nota fiscal nas unidades de peso Gramas.
* Correção na definição da transportadora na nota fiscal.

= 3.2.2 =
* Melhorias na integração com plugin Frenet.
* Compatibilidade com plugin Redis Cache.

= 3.2.1.3 =
* Adicionado o campo para identificar o tipo de contribuinte no pedido do WooCommerce, antes de emitir a nota fiscal, caso necessário.

= 3.2.1.2 =
* Correção de bugs e melhorias.

= 3.2.1 =
* Geração de Danfe em massa com abertura em nova janela.
* Diversas correções de bugs e melhorias.

= 3.2.0 =
* Adicionado nas ações em massa a opção de gerar relatório PDF do Danfe, Danfe Simples e Danfe Etiqueta dos pedidos selecionados.
* Visualização do Danfe, Danfe Simples e Danfe Etiqueta na página do pedido.
* Possibilidade de informar o indicador de Intermediador/Marketplace nos pedidos e configuração global.
* Adicionado as variações do produto como descrição nas informações adicionais da nota fiscal.