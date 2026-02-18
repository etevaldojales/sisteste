# Resumo: Como a Indexação de Produtos é Acionada

Este documento resume o mecanismo pelo qual as atualizações de produtos são enviadas para o Elasticsearch no sistema `sisteste`.

## O Padrão Observer do Laravel

O sistema não chama os jobs de indexação diretamente de controllers ou services. Em vez disso, ele utiliza o **Padrão de Projeto Observer**, uma funcionalidade nativa do framework Laravel, para manter o código desacoplado e mais fácil de manter.

## Fluxo de Execução

1.  **Ação no Modelo**: Uma operação de `create`, `update` ou `delete` é executada em uma instância do Eloquent Model `App\Models\Product`.

2.  **Gatilho Automático**: O Laravel detecta essa alteração no modelo e automaticamente aciona um "observador" que está registrado para "escutar" eventos nesse modelo específico.

3.  **Execução do Observador**: A classe `App\Models\ProductObserver` é executada. Dentro dela, métodos específicos correspondem a cada evento do modelo:
    *   `created()`: Dispara `SyncProductToElasticsearch::dispatch($product)` quando um novo produto é salvo.
    *   `updated()`: Dispara `SyncProductToElasticsearch::dispatch($product)` quando um produto existente é alterado.
    *   `deleted()`: Dispara `RemoveProductFromElasticsearch::dispatch($product->id)` quando um produto é excluído.

4.  **Processamento em Fila (Jobs)**: Os `jobs` (`SyncProductToElasticsearch` e `RemoveProductFromElasticsearch`) são colocados em uma fila para serem executados em segundo plano (background). Isso garante que a resposta da requisição ao usuário seja rápida e não precise esperar o término da sincronização com o Elasticsearch.

## Registro do Observador

A conexão entre o modelo `Product` e o `ProductObserver` é definida no método `boot()` do provedor de serviços `App\Providers\AppServiceProvider`, através da linha:

```php
Product::observe(ProductObserver::class);
```

Este mecanismo garante que qualquer modificação no modelo `Product`, não importa onde ela ocorra no código, será interceptada e devidamente sincronizada com o Elasticsearch.
