<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Http\Resources\DiscountResource;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        return DiscountResource::collection(Discount::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:255','type'=>'required|in:percentage,fixed_amount','value'=>'required|numeric|min:0','status'=>'nullable|in:active,inactive']);
        $discount = Discount::create($data);
        return new DiscountResource($discount);
    }

    public function show(Discount $discount)
    {
        return new DiscountResource($discount);
    }

    public function update(Request $request, Discount $discount)
    {
        $data = $request->validate(['name'=>'required|string|max:255','type'=>'required|in:percentage,fixed_amount','value'=>'required|numeric|min:0','status'=>'nullable|in:active,inactive']);
        $discount->update($data);
        return new DiscountResource($discount);
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();
        return response()->json(['message' => 'Discount deleted.']);
    }
}
