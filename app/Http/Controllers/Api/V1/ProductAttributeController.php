<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Http\Resources\ProductAttributeResource;
use Illuminate\Http\Request;

class ProductAttributeController extends Controller
{
    public function index(Request $request)
    {
        return ProductAttributeResource::collection(ProductAttribute::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['category_id'=>'required|integer|exists:categories,id','name'=>'required|string|max:255','type'=>'nullable|in:text,select,boolean,number']);
        $productAttribute = ProductAttribute::create($data);
        return new ProductAttributeResource($productAttribute);
    }

    public function show(ProductAttribute $productAttribute)
    {
        return new ProductAttributeResource($productAttribute);
    }

    public function update(Request $request, ProductAttribute $productAttribute)
    {
        $data = $request->validate(['category_id'=>'required|integer|exists:categories,id','name'=>'required|string|max:255','type'=>'nullable|in:text,select,boolean,number']);
        $productAttribute->update($data);
        return new ProductAttributeResource($productAttribute);
    }

    public function destroy(ProductAttribute $productAttribute)
    {
        $productAttribute->delete();
        return response()->json(['message' => 'ProductAttribute deleted.']);
    }
}
