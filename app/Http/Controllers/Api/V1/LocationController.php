<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Http\Resources\LocationResource;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        return LocationResource::collection(Location::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['branch_id'=>'required|integer|exists:branches,id','name'=>'required|string|max:255','code'=>'nullable|string|max:50','type'=>'nullable|string','status'=>'nullable|in:active,inactive']);
        $location = Location::create($data);
        return new LocationResource($location);
    }

    public function show(Location $location)
    {
        return new LocationResource($location);
    }

    public function update(Request $request, Location $location)
    {
        $data = $request->validate(['branch_id'=>'required|integer|exists:branches,id','name'=>'required|string|max:255','code'=>'nullable|string|max:50','type'=>'nullable|string','status'=>'nullable|in:active,inactive']);
        $location->update($data);
        return new LocationResource($location);
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return response()->json(['message' => 'Location deleted.']);
    }
}
