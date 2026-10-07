<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use App\Http\Resources\TranslationResource;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function index(Request $request)
    {
        return TranslationResource::collection(Translation::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['translatable_type'=>'required|string','translatable_id'=>'required|integer','language_id'=>'required|integer|exists:languages,id','field'=>'required|string|max:100','value'=>'required|string']);
        $translation = Translation::create($data);
        return new TranslationResource($translation);
    }

    public function show(Translation $translation)
    {
        return new TranslationResource($translation);
    }

    public function update(Request $request, Translation $translation)
    {
        $data = $request->validate(['translatable_type'=>'required|string','translatable_id'=>'required|integer','language_id'=>'required|integer|exists:languages,id','field'=>'required|string|max:100','value'=>'required|string']);
        $translation->update($data);
        return new TranslationResource($translation);
    }

    public function destroy(Translation $translation)
    {
        $translation->delete();
        return response()->json(['message' => 'Translation deleted.']);
    }
}
