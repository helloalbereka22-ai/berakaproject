<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Http\Resources\CartItemResource;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function index(Request $request)
    {
        return CartItemResource::collection(CartItem::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['cart_id'=>'required|integer|exists:carts,id','product_id'=>'required|integer|exists:products,id','quantity'=>'required|numeric|min:0.0001','unit_price'=>'required|numeric|min:0','final_price'=>'required|numeric|min:0']);
        $cartItem = CartItem::create($data);
        return new CartItemResource($cartItem);
    }

    public function show(CartItem $cartItem)
    {
        return new CartItemResource($cartItem);
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $data = $request->validate(['cart_id'=>'required|integer|exists:carts,id','product_id'=>'required|integer|exists:products,id','quantity'=>'required|numeric|min:0.0001','unit_price'=>'required|numeric|min:0','final_price'=>'required|numeric|min:0']);
        $cartItem->update($data);
        return new CartItemResource($cartItem);
    }

    public function destroy(CartItem $cartItem)
    {
        $cartItem->delete();
        return response()->json(['message' => 'CartItem deleted.']);
    }
}
