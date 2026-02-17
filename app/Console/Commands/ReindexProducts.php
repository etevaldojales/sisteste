<?php

namespace App\Console\Commands;

use App\Jobs\SyncProductToElasticsearch;
use App\Models\Product;
use Illuminate\Console\Command;

class ReindexProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex all products in Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Reindexing all products...');

        $products = Product::all();
        $this->output->progressStart(count($products));

        foreach ($products as $product) {
            SyncProductToElasticsearch::dispatch($product);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->info('All products have been queued for reindexing.');
    }
}
