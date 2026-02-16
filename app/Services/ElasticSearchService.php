<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use App\Models\Product;

class ElasticSearchService
{
    protected Client $client;
    protected string $index = 'products';

    public function __construct()
    {
        $hosts = [config('services.elastic.host')];
        $username = config('services.elastic.username');
        $password = config('services.elastic.password');
        $sslVerification = config('services.elastic.ssl_verification');

        $clientBuilder = ClientBuilder::create()
            ->setHosts($hosts)
            ->setBasicAuthentication($username, $password);

        // Disable SSL verification for development if configured
        if ($sslVerification === 'false' || $sslVerification === false) {
            $clientBuilder->setSSLVerification(false);
        }

        $this->client = $clientBuilder->build();
    }

    /**
     * Check if the index exists in ElasticSearch
     */
    public function indexExists(): bool
    {
        return $this->client->indices()->exists(['index' => $this->index])->asBool();
    }

    /**
     * Create the products index with proper mappings
     */
    public function createIndex(): array
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'analyzer' => [
                            'product_analyzer' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase', 'asciifolding']
                            ]
                        ]
                    ]
                ],
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'sku' => [
                            'type' => 'text',
                            'fields' => [
                                'keyword' => ['type' => 'keyword']
                            ]
                        ],
                        'name' => [
                            'type' => 'text',
                            'analyzer' => 'product_analyzer',
                            'fields' => [
                                'keyword' => ['type' => 'keyword']
                            ]
                        ],
                        'description' => [
                            'type' => 'text',
                            'analyzer' => 'product_analyzer'
                        ],
                        'price' => ['type' => 'float'],
                        'category' => [
                            'type' => 'text',
                            'fields' => [
                                'keyword' => ['type' => 'keyword']
                            ]
                        ],
                        'status' => ['type' => 'keyword'],
                        'created_at' => ['type' => 'date'],
                        'updated_at' => ['type' => 'date'],
                        'deleted_at' => ['type' => 'date']
                    ]
                ]
            ]
        ];

        return $this->client->indices()->create($params)->asArray();
    }

    /**
     * Delete the index
     */
    public function deleteIndex(): void
    {
        if ($this->indexExists()) {
            $this->client->indices()->delete(['index' => $this->index]);
        }
    }

    public function indexProduct(Product $product): void
    {
        // Create index if it doesn't exist
        if (!$this->indexExists()) {
            $this->createIndex();
        }

        $this->client->index([
            'index' => $this->index,
            'id'    => $product->id,
            'body'  => $product->toArray()
        ]);
    }

    public function deleteProduct(int $id): void
    {
        try {
            $this->client->delete([
                'index' => $this->index,
                'id'    => $id
            ]);
        } catch (\Exception $e) {
            // Ignorar se não existir no índice
        }
    }

    public function search(array $filters, int $page = 1, int $perPage = 15): array
    {
        // Check if index exists, return empty results if not
        
        if (!$this->indexExists()) {
            return [
                'hits' => [
                    'total' => ['value' => 0],
                    'hits' => []
                ]
            ];
        }

        $params = [
            'index' => $this->index,
            'body'  => [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => ['bool' => ['must' => []]],
                'sort' => []
            ]
        ];

        // Filtro de Texto (q)
        if (!empty($filters['q'])) {
            $params['body']['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $filters['q'],
                    'fields' => ['name^3', 'description'] // Boost no nome
                ]
            ];
        }

        // Filtros Exatos
        if (!empty($filters['category'])) {
            $params['body']['query']['bool']['filter'][] = ['term' => ['category.keyword' => $filters['category']]];
        }
        if (!empty($filters['status'])) {
            $params['body']['query']['bool']['filter'][] = ['term' => ['status' => $filters['status']]];
        }

        // Range de Preço
        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $range = [];
            if (!empty($filters['min_price'])) $range['gte'] = $filters['min_price'];
            if (!empty($filters['max_price'])) $range['lte'] = $filters['max_price'];
            $params['body']['query']['bool']['filter'][] = ['range' => ['price' => $range]];
        }

        // Ordenação
        if (!empty($filters['sort'])) {
            $order = $filters['order'] ?? 'asc';
            $params['body']['sort'][] = [$filters['sort'] => ['order' => $order]];
        }

        return $this->client->search($params)->asArray();
    }
}
