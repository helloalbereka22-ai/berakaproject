<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Http\Resources\RoleResource;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        return RoleResource::collection(Role::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:255','description'=>'nullable|string|max:500']);
        $role = Role::create($data);
        return new RoleResource($role);
    }

    public function show(Role $role)
    {
        return new RoleResource($role);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate(['name'=>'required|string|max:255','description'=>'nullable|string|max:500']);
        $role->update($data);
        return new RoleResource($role);
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['message' => 'Role deleted.']);
    }
}
