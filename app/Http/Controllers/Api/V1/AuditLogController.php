<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Http\Resources\AuditLogResource;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        return AuditLogResource::collection(AuditLog::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['action'=>'required|string','auditable_type'=>'required|string','auditable_id'=>'required|integer']);
        $auditLog = AuditLog::create($data);
        return new AuditLogResource($auditLog);
    }

    public function show(AuditLog $auditLog)
    {
        return new AuditLogResource($auditLog);
    }

    public function update(Request $request, AuditLog $auditLog)
    {
        $data = $request->validate(['action'=>'required|string','auditable_type'=>'required|string','auditable_id'=>'required|integer']);
        $auditLog->update($data);
        return new AuditLogResource($auditLog);
    }

    public function destroy(AuditLog $auditLog)
    {
        $auditLog->delete();
        return response()->json(['message' => 'AuditLog deleted.']);
    }
}
