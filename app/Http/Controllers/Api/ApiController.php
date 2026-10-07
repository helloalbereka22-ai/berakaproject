<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Base controller for all v1 API controllers.
 * Provides consistent JSON response helpers.
 */
abstract class ApiController extends Controller
{
    // ── Success responses ─────────────────────────────────────────────────

    protected function ok(mixed $data, string $message = 'OK'): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], 200);
    }

    protected function created(mixed $data, string $message = 'Created'): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    // ── Error responses ───────────────────────────────────────────────────

    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 404);
    }

    protected function forbidden(string $message = 'Access denied'): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 403);
    }

    protected function unauthorized(string $message = 'Unauthenticated'): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 401);
    }

    protected function error(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    // ── Paginated collection helper ───────────────────────────────────────

    /**
     * Wrap a paginated resource collection with consistent envelope.
     * Automatically inlines pagination meta from the resource's original response.
     */
    protected function paginated(\Illuminate\Http\Resources\Json\ResourceCollection $collection): JsonResponse
    {
        $response = $collection->response()->getData(true);

        return response()->json([
            'success' => true,
            'data'    => $response['data']  ?? [],
            'meta'    => $response['meta']  ?? [],
            'links'   => $response['links'] ?? [],
        ]);
    }
}
