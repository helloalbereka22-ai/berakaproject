<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\AuthorizationService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly AuthorizationService $authService,
    ) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Category::with(['parent', 'children'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->whereIn('business_id', $user->assignedBusinessIds());
            });

        if ($request->filled('type'))        $query->where('type', $request->type);
        if ($request->filled('parent_id'))   $query->where('parent_id', $request->parent_id);
        if ($request->filled('is_active'))   $query->where('is_active', (bool) $request->is_active);
        if ($request->boolean('roots_only')) $query->whereNull('parent_id');

        return CategoryResource::collection(
            $query->orderBy('sort_order')->orderBy('name')->paginate(50)
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'business_id' => 'required|integer|exists:businesses,id',
            'parent_id'   => 'nullable|integer|exists:categories,id',
            'type'        => ['required', Rule::in(array_keys(Category::TYPES))],
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:50',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $this->authService->assertBusinessAccess($user, (int) $data['business_id']);

        $category = $this->productService->createCategory($data, (int) $data['business_id']);

        return new CategoryResource($category);
    }

    public function show(Request $request, Category $category)
    {
        $this->authService->assertBusinessAccess($request->user(), $category->business_id);

        return new CategoryResource($category->load(['parent', 'children', 'products']));
    }

    public function update(Request $request, Category $category)
    {
        $this->authService->assertBusinessAccess($request->user(), $category->business_id);

        $data = $request->validate([
            'parent_id'   => 'nullable|integer|exists:categories,id',
            'type'        => ['sometimes', Rule::in(array_keys(Category::TYPES))],
            'name'        => 'sometimes|string|max:255',
            'slug'        => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:50',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $category = $this->productService->updateCategory($category, $data);

        return new CategoryResource($category);
    }

    public function destroy(Request $request, Category $category)
    {
        $this->authService->assertBusinessAccess($request->user(), $category->business_id);

        try {
            $this->productService->deleteCategory($category);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Category deleted.']);
    }
}
