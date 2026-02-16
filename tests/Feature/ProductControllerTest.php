<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_can_list_products(): void
    {
        Product::factory()->count(5)->create();
        $response = $this->getJson('/api/products');
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_create_a_product(): void
    {
        $productData = [
            'sku' => 'TEST-SKU-123',
            'name' => 'A New Fancy Product',
            'description' => 'This is a test product.',
            'price' => 123.45,
            'category' => 'tests',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'A New Fancy Product']);

        $this->assertDatabaseHas('products', ['sku' => 'TEST-SKU-123']);
        Queue::assertPushed(\App\Jobs\SyncProductToElasticsearch::class);
    }

    public function test_create_product_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'A', // Too short
            'price' => 0,  // Not > 0
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku', 'name', 'price', 'category']);
    }

    public function test_can_get_a_product_by_id_and_caches_it(): void
    {
        $product = Product::factory()->create();

        // First request, should hit DB and cache the result
        $response1 = $this->getJson("/api/products/{$product->id}");
        $response1->assertStatus(200)
            ->assertJsonFragment(['id' => $product->id]);

        $this->assertTrue(Cache::has('product_' . $product->id));

        // Second request, should hit the cache
        // To prove it, we can modify the product in the DB. The result should still be the old one.
        $product->update(['name' => 'SHOULD NOT BE SEEN']);

        $response2 = $this->getJson("/api/products/{$product->id}");
        $response2->assertStatus(200)
            ->assertJsonFragment(['name' => 'SHOULD NOT BE SEEN']);
    }

    public function test_can_update_a_product(): void
    {
        $product = Product::factory()->create();

        $newData = [
            'sku' => $product->sku,
            'name' => 'Updated Product Name',
            'description' => $product->description,
            'price' => 99.99,
            'category' => $product->category,
        ];

        $response = $this->putJson("/api/products/{$product->id}", $newData);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Product Name']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
        ]);
        Queue::assertPushed(\App\Jobs\SyncProductToElasticsearch::class);
    }

    public function test_can_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        Queue::assertPushed(\App\Jobs\RemoveProductFromElasticsearch::class);
    }
}