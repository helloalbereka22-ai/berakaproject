<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Http\Resources\AttributeValueResource;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function index(Request $request)
    {
        return AttributeValueResource::collection(AttributeValue::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['product_id'=>'required|integer|exists:products,id','product_attribute_id'=>'required|integer','value'=>'required|string|max:500']);
        $attributeValue = AttributeValue::create($data);
        return new AttributeValueResource($attributeValue);
    }

    public function show(AttributeValue $attributeValue)
    {
        return new AttributeValueResource($attributeValue);
    }

    public function update(Request $request, AttributeValue $attributeValue)
    {
        $data = $request->validate(['product_id'=>'required|integer|exists:products,id','product_attribute_id'=>'required|integer','value'=>'required|string|max:500']);
        $attributeValue->update($data);
        return new AttributeValueResource($attributeValue);
    }

    public function destroy(AttributeValue $attributeValue)
    {
        $attributeValue->delete();
        return response()->json(['message' => 'AttributeValue deleted.']);
    }
}
