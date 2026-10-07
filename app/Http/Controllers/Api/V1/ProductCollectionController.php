<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductCollection;
use App\Http\Resources\ProductCollectionResource;
use Illuminate\Http\Request;

class ProductCollectionController extends Controller
{
    public function index(Request $request)
    {
        return ProductCollectionResource::collection(ProductCollection::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['business_id'=>'required|integer|exists:businesses,id','name'=>'required|string|max:255','is_active'=>'nullable|boolean']);
        $productCollection = ProductCollection::create($data);
        return new ProductCollectionResource($productCollection);
    }

    public function show(ProductCollection $productCollection)
    {
        return new ProductCollectionResource($productCollection);
    }

    public function update(Request $request, ProductCollection $productCollection)
    {
        $data = $request->validate(['business_id'=>'required|integer|exists:businesses,id','name'=>'required|string|max:255','is_active'=>'nullable|boolean']);
        $productCollection->update($data);
        return new ProductCollectionResource($productCollection);
    }

    public function destroy(ProductCollection $productCollection)
    {
        $productCollection->delete();
        return response()->json(['message' => 'ProductCollection deleted.']);
    }
}
