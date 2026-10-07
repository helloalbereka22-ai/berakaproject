<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * DEPRECATED — The product_images table was dropped. Product images are now
 * stored as a single image_path string column on the products table.
 *
 * All endpoints return 410 Gone to signal the API consumer to migrate.
 * Use PATCH /api/v1/products/{id} with an image_path field instead.
 */
class ProductImageController extends Controller
{
    private function gone(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'The product_images endpoint has been removed. '
                . 'Use the image_path field on the product resource instead.',
        ], 410);
    }

    public function index(Request $request)   { return $this->gone(); }
    public function store(Request $request)   { return $this->gone(); }
    public function show(int $id)             { return $this->gone(); }
    public function update(Request $r, $id)   { return $this->gone(); }
    public function destroy($id)              { return $this->gone(); }
}
