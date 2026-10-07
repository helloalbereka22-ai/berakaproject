<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Http\Resources\TagResource;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        return TagResource::collection(Tag::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:100']);
        $tag = Tag::create($data);
        return new TagResource($tag);
    }

    public function show(Tag $tag)
    {
        return new TagResource($tag);
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate(['name'=>'required|string|max:100']);
        $tag->update($data);
        return new TagResource($tag);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return response()->json(['message' => 'Tag deleted.']);
    }
}
