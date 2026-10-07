<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Http\Resources\SettingResource;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        return SettingResource::collection(Setting::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['key'=>'required|string|max:255','value'=>'nullable']);
        $setting = Setting::create($data);
        return new SettingResource($setting);
    }

    public function show(Setting $setting)
    {
        return new SettingResource($setting);
    }

    public function update(Request $request, Setting $setting)
    {
        $data = $request->validate(['key'=>'required|string|max:255','value'=>'nullable']);
        $setting->update($data);
        return new SettingResource($setting);
    }

    public function destroy(Setting $setting)
    {
        $setting->delete();
        return response()->json(['message' => 'Setting deleted.']);
    }
}
