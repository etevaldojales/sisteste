<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Product::paginate($perPage);
    }

    public function find(int $id): ?Product
    {
        return Product::withTrashed()->find($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(int $id, array $data): Product
    {
        $product = $this->find($id);
        if (!$product) {
            throw new ModelNotFoundException("Product with ID {$id} not found.");
        }
        $product->update($data);
        return $product;
    }

    public function delete(int $id): bool
    {
        $product = $this->find($id);
        if (!$product) {
            throw new ModelNotFoundException("Product with ID {$id} not found.");
        }
        return $product->delete();
    }
}
