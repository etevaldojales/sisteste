<?php

namespace App\Dtos;

use App\Http\Requests\ProductRequest;

class ProductDto
{
    public function __construct(
        public string $sku,
        public string $name,
        public ?string $description,
        public float $price,
        public string $category,
        public ?string $status
    ) {
    }

    public static function fromRequest(ProductRequest $request): self
    {
        return new self(
            $request->validated('sku'),
            $request->validated('name'),
            $request->validated('description'),
            $request->validated('price'),
            $request->validated('category'),
            $request->validated('status', 'active')
        );
    }

    public function toArray(): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category' => $this->category,
            'status' => $this->status,
        ];
    }
}
