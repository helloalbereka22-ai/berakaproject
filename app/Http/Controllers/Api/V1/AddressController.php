<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Http\Resources\AddressResource;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        return AddressResource::collection(Address::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['addressable_type'=>'required|string','addressable_id'=>'required|integer','line1'=>'required|string|max:255','city'=>'required|string|max:100','country'=>'required|string|max:100']);
        $address = Address::create($data);
        return new AddressResource($address);
    }

    public function show(Address $address)
    {
        return new AddressResource($address);
    }

    public function update(Request $request, Address $address)
    {
        $data = $request->validate(['addressable_type'=>'required|string','addressable_id'=>'required|integer','line1'=>'required|string|max:255','city'=>'required|string|max:100','country'=>'required|string|max:100']);
        $address->update($data);
        return new AddressResource($address);
    }

    public function destroy(Address $address)
    {
        $address->delete();
        return response()->json(['message' => 'Address deleted.']);
    }
}
