<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Http\Resources\OrderItemResource;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function index(Request $request)
    {
        return OrderItemResource::collection(OrderItem::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['order_id'=>'required|integer|exists:orders,id','product_id'=>'required|integer|exists:products,id','quantity'=>'required|numeric|min:0.0001','unit_price'=>'required|numeric|min:0','total'=>'required|numeric|min:0']);
        $orderItem = OrderItem::create($data);
        return new OrderItemResource($orderItem);
    }

    public function show(OrderItem $orderItem)
    {
        return new OrderItemResource($orderItem);
    }

    public function update(Request $request, OrderItem $orderItem)
    {
        $data = $request->validate(['order_id'=>'required|integer|exists:orders,id','product_id'=>'required|integer|exists:products,id','quantity'=>'required|numeric|min:0.0001','unit_price'=>'required|numeric|min:0','total'=>'required|numeric|min:0']);
        $orderItem->update($data);
        return new OrderItemResource($orderItem);
    }

    public function destroy(OrderItem $orderItem)
    {
        $orderItem->delete();
        return response()->json(['message' => 'OrderItem deleted.']);
    }
}
