<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return NotificationResource::collection(Notification::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['user_id'=>'required|integer|exists:users,id','title'=>'required|string|max:255','body'=>'required|string']);
        $notification = Notification::create($data);
        return new NotificationResource($notification);
    }

    public function show(Notification $notification)
    {
        return new NotificationResource($notification);
    }

    public function update(Request $request, Notification $notification)
    {
        $data = $request->validate(['user_id'=>'required|integer|exists:users,id','title'=>'required|string|max:255','body'=>'required|string']);
        $notification->update($data);
        return new NotificationResource($notification);
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response()->json(['message' => 'Notification deleted.']);
    }
}
