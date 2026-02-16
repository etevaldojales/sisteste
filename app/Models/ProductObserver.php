<?php

namespace App\Models;

use App\Jobs\RemoveProductFromElasticsearch;
use App\Jobs\SyncProductToElasticsearch;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        SyncProductToElasticsearch::dispatch($product);
        Cache::tags(['products_search'])->flush();
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        SyncProductToElasticsearch::dispatch($product);
        Cache::forget('product_' . $product->id);
        Cache::tags(['products_search'])->flush();
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        RemoveProductFromElasticsearch::dispatch($product->id);
        Cache::forget('product_' . $product->id);
        Cache::tags(['products_search'])->flush();
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        SyncProductToElasticsearch::dispatch($product);
        Cache::tags(['products_search'])->flush();
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        RemoveProductFromElasticsearch::dispatch($product->id);
        Cache::forget('product_' . $product->id);
        Cache::tags(['products_search'])->flush();
    }
}
