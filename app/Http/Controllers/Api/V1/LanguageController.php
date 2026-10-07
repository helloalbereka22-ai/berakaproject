<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Http\Resources\LanguageResource;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index(Request $request)
    {
        return LanguageResource::collection(Language::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['code'=>'required|string|max:10','name'=>'required|string|max:100','direction'=>'nullable|in:ltr,rtl','is_active'=>'nullable|boolean']);
        $language = Language::create($data);
        return new LanguageResource($language);
    }

    public function show(Language $language)
    {
        return new LanguageResource($language);
    }

    public function update(Request $request, Language $language)
    {
        $data = $request->validate(['code'=>'required|string|max:10','name'=>'required|string|max:100','direction'=>'nullable|in:ltr,rtl','is_active'=>'nullable|boolean']);
        $language->update($data);
        return new LanguageResource($language);
    }

    public function destroy(Language $language)
    {
        $language->delete();
        return response()->json(['message' => 'Language deleted.']);
    }
}
