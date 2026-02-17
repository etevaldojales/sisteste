<?php

namespace App\Services;

use App\Dtos\ProductDto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Repositories\ProductRepositoryInterface;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductService implements ProductServiceInterface
{
    protected ProductRepositoryInterface $productRepository;
    protected ElasticSearchService $elasticSearchService;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ElasticSearchService $elasticSearchService
    ) {
        $this->productRepository = $productRepository;
        $this->elasticSearchService = $elasticSearchService;
    }

    public function getAll(int $perPage)
    {
        return $this->productRepository->getAll($perPage);
    }

    public function create(array $data)
    {
        return $this->productRepository->create($data);
    }

    public function find(int $id)
    {
        return Cache::remember('product_' . $id, 120, function () use ($id) {
            return $this->productRepository->find($id);
        });
    }

    public function update(int $id, array $data)
    {
        $product = $this->productRepository->update($id, $data);
        Cache::forget('product_' . $id);
        // Invalidate only search-related caches by flushing the tag
        // This is necessary since we can't selectively invalidate tagged caches
        Cache::tags(['products_search'])->flush();
        return $product;
    }

    public function delete(int $id)
    {
        $this->productRepository->delete($id);
        Cache::forget('product_' . $id);
        // Invalidate only search-related caches by flushing the tag
        Cache::tags(['products_search'])->flush();
    }

    public function search(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);

        // Don't use cache for large page numbers (performance)
        if ($page > 50) {
            return $this->elasticSearchService->search(
                $request->all(),
                $page,
                $perPage
            );
        }

        $cacheKey = 'search_' . http_build_query($request->all());

        return Cache::tags(['products_search'])->remember($cacheKey, 120, function () use ($request, $page, $perPage) {
            return $this->elasticSearchService->search(
                $request->all(),
                $page,
                $perPage
            );
        });
    }

    public function uploadImage(Request $request, int $id)
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw new ModelNotFoundException("Produto com ID {$id} não encontrado.");
        }

        $path = $request->file('image')->store('products', 'public');

        if (!$path) {
            throw new Exception("Falha no upload do arquivo. Verifique a configuração de armazenamento.");
        }

        return $this->productRepository->update($id, ['image_url' => $path]);
    }
}
