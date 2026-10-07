<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        return PermissionResource::collection(Permission::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:255','description'=>'nullable|string|max:500','group'=>'nullable|string|max:100']);
        $permission = Permission::create($data);
        return new PermissionResource($permission);
    }

    public function show(Permission $permission)
    {
        return new PermissionResource($permission);
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate(['name'=>'required|string|max:255','description'=>'nullable|string|max:500','group'=>'nullable|string|max:100']);
        $permission->update($data);
        return new PermissionResource($permission);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json(['message' => 'Permission deleted.']);
    }
}
