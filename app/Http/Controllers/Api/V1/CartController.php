<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Http\Resources\CartResource;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        return CartResource::collection(Cart::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['customer_id'=>'required|integer|exists:customers,id','status'=>'nullable|in:active,checked_out,abandoned']);
        $cart = Cart::create($data);
        return new CartResource($cart);
    }

    public function show(Cart $cart)
    {
        return new CartResource($cart);
    }

    public function update(Request $request, Cart $cart)
    {
        $data = $request->validate(['customer_id'=>'required|integer|exists:customers,id','status'=>'nullable|in:active,checked_out,abandoned']);
        $cart->update($data);
        return new CartResource($cart);
    }

    public function destroy(Cart $cart)
    {
        $cart->delete();
        return response()->json(['message' => 'Cart deleted.']);
    }
}
