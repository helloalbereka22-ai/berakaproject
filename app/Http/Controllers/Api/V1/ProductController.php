<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\AuthorizationService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService       $productService,
        private readonly AuthorizationService $authService,
    ) {}

    // ── CRUD ──────────────────────────────────────────────────────────────

    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $user  = $request->user();
        $query = Product::with(['category'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            );

        if ($request->filled('type'))        $query->where('type', $request->type);
        if ($request->filled('category_id')) $query->where('category_id', $request->category_id);
        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('search'))      $query->search($request->search);
        if ($request->filled('business_id')) {
            $this->authService->assertBusinessAccess($user, (int) $request->business_id);
            $query->where('business_id', $request->business_id);
        }

        return ProductResource::collection(
            $query->orderBy('sort_order')->orderBy('name')->paginate(25)
        );
    }

    public function store(Request $request): ProductResource
    {
        $data = $request->validate([
            'business_id'            => 'required|integer|exists:businesses,id',
            'category_id'            => 'required|integer|exists:categories,id',
            'type'                   => ['required', Rule::in(array_keys(Product::TYPES))],
            'name'                   => 'required|string|max:255',
            'short_name'             => 'nullable|string|max:100',
            'sku'                    => 'nullable|string|max:100|unique:products,sku',
            'barcode'                => 'nullable|string|max:100',
            'unit'                   => 'nullable|string|max:50',
            'package_size'           => 'nullable|string|max:50',
            'description'            => 'nullable|string',
            'selling_price'          => 'required|numeric|min:0',
            'cost_price'             => 'required|numeric|min:0',
            'reorder_point'          => 'nullable|numeric|min:0',
            'min_stock'              => 'nullable|numeric|min:0',
            'max_stock'              => 'nullable|numeric|min:0',
            'status'                 => ['nullable', Rule::in(array_keys(Product::STATUSES))],
            'is_featured'            => 'nullable|boolean',
            'is_marketplace_visible' => 'nullable|boolean',
            'sort_order'             => 'nullable|integer|min:0',
        ]);

        $this->authService->assertBusinessAccess($request->user(), (int) $data['business_id']);

        $product = $this->productService->create($data, (int) $data['business_id']);

        return new ProductResource($product->load('category'));
    }

    public function show(Request $request, Product $product): ProductResource
    {
        $this->authService->assertBusinessAccess($request->user(), $product->business_id);

        // No eager-loaded image relationship — images come from metadata attribute
        return new ProductResource($product->load(['category', 'variants', 'attributeValues']));
    }

    public function update(Request $request, Product $product): ProductResource
    {
        $this->authService->assertBusinessAccess($request->user(), $product->business_id);

        $data = $request->validate([
            'category_id'            => 'sometimes|integer|exists:categories,id',
            'type'                   => ['sometimes', Rule::in(array_keys(Product::TYPES))],
            'name'                   => 'sometimes|string|max:255',
            'short_name'             => 'nullable|string|max:100',
            'sku'                    => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'barcode'                => 'nullable|string|max:100',
            'unit'                   => 'nullable|string|max:50',
            'package_size'           => 'nullable|string|max:50',
            'description'            => 'nullable|string',
            'selling_price'          => 'sometimes|numeric|min:0',
            'cost_price'             => 'sometimes|numeric|min:0',
            'reorder_point'          => 'nullable|numeric|min:0',
            'min_stock'              => 'nullable|numeric|min:0',
            'max_stock'              => 'nullable|numeric|min:0',
            'status'                 => ['nullable', Rule::in(array_keys(Product::STATUSES))],
            'is_featured'            => 'nullable|boolean',
            'is_marketplace_visible' => 'nullable|boolean',
            'sort_order'             => 'nullable|integer|min:0',
        ]);

        $product = $this->productService->update($product, $data);

        return new ProductResource($product->load('category'));
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authService->assertBusinessAccess($request->user(), $product->business_id);
        $this->productService->delete($product);

        return response()->json(['message' => 'Product deleted.']);
    }

    // ── IMAGE MANAGEMENT ─────────────────────────────────────────────────
    // Images are stored in products.metadata['images'] — no separate table.
    //
    // Routes:
    //   GET    /products/{product}/images              → index
    //   POST   /products/{product}/images              → upload & add
    //   PATCH  /products/{product}/images/set-primary  → set primary
    //   PUT    /products/{product}/images/reorder      → reorder
    //   DELETE /products/{product}/images              → remove by path

    /**
     * GET /products/{product}/images
     * Returns all images for a product.
     */
    public function imageIndex(Request $request, Product $product): JsonResponse
    {
        $this->authService->assertBusinessAccess($request->user(), $product->business_id);

        return response()->json([
            'data' => $product->images,
        ]);
    }

    /**
     * POST /products/{product}/images
     * Upload a new image file and attach it to the product.
     *
     * Accepts multipart/form-data:
     *   image      file    required  — the image file
     *   alt        string  optional  — alt text / caption
     *   is_primary bool    optional  — set as primary image
     */
    public function imageStore(Request $request, Product $product): JsonResponse
    {
        $this->authService->assertBusinessAccess($request->user(), $product->business_id);

        $request->validate([
            'image'      => 'required|image|mimes:jpeg,png,webp,gif|max:4096',
            'alt'        => 'nullable|string|max:255',
            'is_primary' => 'nullable|boolean',
        ]);

        $path = $request->file('image')->store(
            "products/{$product->id}",
            'public'
        );

        $product->addImage(
            path:    $path,
            alt:     $request->input('alt', ''),
            primary: (bool) $request->input('is_primary', false),
        );

        return response()->json([
            'message' => 'Image uploaded.',
            'data'    => $product->fresh()->images,
        ], 201);
    }

    /**
     * PATCH /products/{product}/images/set-primary
     * Set an existing image as the primary image.
     *
     * Body: { "path": "products/1/abc.jpg" }
     */
    public function imageSetPrimary(Request $request, Product $product): JsonResponse
    {
        $this->authService->assertBusinessAccess($request->user(), $product->business_id);

        $data = $request->validate([
            'path' => 'required|string',
        ]);

        $product->setPrimaryImage($data['path']);

        return response()->json([
            'message' => 'Primary image updated.',
            'data'    => $product->fresh()->images,
        ]);
    }

    /**
     * PUT /products/{product}/images/reorder
     * Reorder images by supplying paths in the desired order.
     *
     * Body: { "paths": ["products/1/a.jpg", "products/1/b.jpg"] }
     */
    public function imageReorder(Request $request, Product $product): JsonResponse
    {
        $this->authService->assertBusinessAccess($request->user(), $product->business_id);

        $data = $request->validate([
            'paths'   => 'required|array|min:1',
            'paths.*' => 'required|string',
        ]);

        $product->reorderImages($data['paths']);

        return response()->json([
            'message' => 'Images reordered.',
            'data'    => $product->fresh()->images,
        ]);
    }

    /**
     * DELETE /products/{product}/images
     * Remove an image by path. Also deletes the file from storage.
     *
     * Body: { "path": "products/1/abc.jpg" }
     */
    public function imageDestroy(Request $request, Product $product): JsonResponse
    {
        $this->authService->assertBusinessAccess($request->user(), $product->business_id);

        $data = $request->validate([
            'path' => 'required|string',
        ]);

        $product->removeImage($data['path']);

        // Delete physical file from storage
        Storage::disk('public')->delete($data['path']);

        return response()->json([
            'message' => 'Image removed.',
            'data'    => $product->fresh()->images,
        ]);
    }
}
