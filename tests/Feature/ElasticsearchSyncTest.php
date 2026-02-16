<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\ElasticSearchService;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;

class ElasticsearchSyncTest extends TestCase
{
    private ElasticSearchService $elasticSearchService;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--seed' => true]);
        $this->elasticSearchService = $this->app->make(ElasticSearchService::class);
        $this->elasticSearchService->deleteIndex();
        $this->elasticSearchService->createIndex();
    }

    protected function tearDown(): void
    {
        $this->elasticSearchService->deleteIndex();
        Artisan::call('migrate:reset');
        parent::tearDown();
    }

    public function test_product_creation_is_synced_to_elasticsearch()
    {
        $product = Product::factory()->create();

        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        $result = $this->elasticSearchService->search(['q' => $product->name], 1, 1);

        $this->assertEquals(1, $result['hits']['total']['value']);
        $this->assertEquals($product->name, $result['hits']['hits'][0]['_source']['name']);
    }

    public function test_product_update_is_synced_to_elasticsearch()
    {
        $product = Product::factory()->create();
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        $newName = 'Updated Product Name';
        $product->update(['name' => $newName]);
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        $result = $this->elasticSearchService->search(['q' => $newName], 1, 1);

        $this->assertEquals(1, $result['hits']['total']['value']);
        $this->assertEquals($newName, $result['hits']['hits'][0]['_source']['name']);
    }

    public function test_product_deletion_is_synced_to_elasticsearch()
    {
        $product = Product::factory()->create();
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        $product->delete();
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        $result = $this->elasticSearchService->search(['q' => $product->name], 1, 1);

        $this->assertEquals(0, $result['hits']['total']['value']);
    }
}
