<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductMedia;
use App\Http\Resources\ProductMediaResource;
use Illuminate\Http\Request;

class ProductMediaController extends Controller
{
    public function index(Request $request)
    {
        return ProductMediaResource::collection(ProductMedia::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['product_id'=>'required|integer|exists:products,id','media_file_id'=>'required|integer']);
        $productMedia = ProductMedia::create($data);
        return new ProductMediaResource($productMedia);
    }

    public function show(ProductMedia $productMedia)
    {
        return new ProductMediaResource($productMedia);
    }

    public function update(Request $request, ProductMedia $productMedia)
    {
        $data = $request->validate(['product_id'=>'required|integer|exists:products,id','media_file_id'=>'required|integer']);
        $productMedia->update($data);
        return new ProductMediaResource($productMedia);
    }

    public function destroy(ProductMedia $productMedia)
    {
        $productMedia->delete();
        return response()->json(['message' => 'ProductMedia deleted.']);
    }
}
