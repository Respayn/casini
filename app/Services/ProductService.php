<?php

namespace App\Services;

use App\Data\ProductData;
use App\Repositories\ProductRepository;
use Illuminate\Support\Arr;

class ProductService
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getProducts()
    {
        return collect($this->productRepository->all());
    }

    public function updateProduct(ProductData $product)
    {
        $this->productRepository->save(Arr::snake($product->toArray()));
    }
}
