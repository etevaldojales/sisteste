<?php

namespace App\Services;

use App\Dtos\ProductDto;
use Illuminate\Http\Request;

interface ProductServiceInterface
{
    public function getAll(int $perPage);
    public function create(array $data);
    public function find(int $id);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function search(Request $request);
    public function uploadImage(Request $request, int $id);
}
