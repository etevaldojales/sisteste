<?php

namespace Tests\Feature;

use App\Services\ElasticSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_products_with_filters(): void
    {
        // We mock the service to avoid a real dependency on Elasticsearch in this test.
        // We are testing that the controller calls the service correctly.
        $this->mock(ElasticSearchService::class, function (MockInterface $mock) {
            $mock->shouldReceive('search')
                ->once()
                ->with(
                    \Mockery::on(function ($arg) {
                        return is_array($arg) && $arg['q'] === 'test' && $arg['category'] === 'cat1';
                    }),
                    1,
                    15
                )
                ->andReturn([
                    'hits' => [
                        'total' => ['value' => 0],
                        'hits' => []
                    ]
                ]);
        });

        $response = $this->getJson('/api/search/products?q=test&category=cat1');

        $response->assertStatus(200);
    }
}
