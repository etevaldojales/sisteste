<?php

namespace App\Http\Controllers;

use App\Dtos\ProductDto;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\UploadImageRequest;
use App\Services\ElasticSearchService;
use App\Services\ProductServiceInterface;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductServiceInterface $productService;
    protected ElasticSearchService $elasticSearchService;

    public function __construct(
        ProductServiceInterface $productService,
        ElasticSearchService $elasticSearchService
    ) {
        $this->productService = $productService;
        $this->elasticSearchService = $elasticSearchService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = $this->productService->getAll($request->get('per_page', 15));
        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $productDto = ProductDto::fromRequest($request);
        $product = $this->productService->create($productDto->toArray());
        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $product = $this->productService->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, int $id)
    {
        $productDto = ProductDto::fromRequest($request);
        $product = $this->productService->update($id, $productDto->toArray());
        return response()->json($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->productService->delete($id);
        return response()->json(null, 204);
    }

    public function search(Request $request)
    {
        $results = $this->productService->search($request);
        return response()->json($results);
    }

    public function createSearchIndex()
    {
        if ($this->elasticSearchService->indexExists()) {
            return response()->json(['message' => 'Index already exists.'], 409);
        }

        $this->elasticSearchService->createIndex();

        return response()->json(['message' => 'Index created successfully.']);
    }

    public function deleteSearchIndex()
    {
        $this->elasticSearchService->deleteIndex();

        return response()->json(['message' => 'Index deleted successfully.']);
    }

    public function uploadImage(UploadImageRequest $request, int $id)
    {
        $product = $this->productService->uploadImage($request, $id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }
}
