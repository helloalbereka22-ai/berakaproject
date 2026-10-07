<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Http\Resources\MediaFileResource;
use Illuminate\Http\Request;

class MediaFileController extends Controller
{
    public function index(Request $request)
    {
        return MediaFileResource::collection(MediaFile::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['file_name'=>'required|string|max:255','path'=>'required|string|max:500','mime_type'=>'required|string|max:100','size'=>'required|integer|min:0']);
        $mediaFile = MediaFile::create($data);
        return new MediaFileResource($mediaFile);
    }

    public function show(MediaFile $mediaFile)
    {
        return new MediaFileResource($mediaFile);
    }

    public function update(Request $request, MediaFile $mediaFile)
    {
        $data = $request->validate(['file_name'=>'required|string|max:255','path'=>'required|string|max:500','mime_type'=>'required|string|max:100','size'=>'required|integer|min:0']);
        $mediaFile->update($data);
        return new MediaFileResource($mediaFile);
    }

    public function destroy(MediaFile $mediaFile)
    {
        $mediaFile->delete();
        return response()->json(['message' => 'MediaFile deleted.']);
    }
}
