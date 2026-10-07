<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Http\Resources\PromotionResource;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        return PromotionResource::collection(Promotion::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:255','scope_type'=>'required|in:product,category,order','discount_id'=>'required|integer|exists:discounts,id']);
        $promotion = Promotion::create($data);
        return new PromotionResource($promotion);
    }

    public function show(Promotion $promotion)
    {
        return new PromotionResource($promotion);
    }

    public function update(Request $request, Promotion $promotion)
    {
        $data = $request->validate(['name'=>'required|string|max:255','scope_type'=>'required|in:product,category,order','discount_id'=>'required|integer|exists:discounts,id']);
        $promotion->update($data);
        return new PromotionResource($promotion);
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();
        return response()->json(['message' => 'Promotion deleted.']);
    }
}
