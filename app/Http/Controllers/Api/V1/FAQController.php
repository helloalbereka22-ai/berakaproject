<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\FAQResource;
use App\Models\FAQ;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group CMS — FAQs
 * Frequently asked questions displayed on the storefront.
 */
class FAQController extends ApiController
{
    /**
     * List FAQs
     *
     * @authenticated
     * @queryParam category string Filter by category.
     * @queryParam is_active boolean Filter by active status.
     * @queryParam search string Search in question and answer.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FAQ::query()
            ->when($request->filled('category'),  fn($q) => $q->where('category', $request->category))
            ->when($request->boolean('is_active', true), fn($q) => $q->where('is_active', true))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->search;
                $q->where(fn($sq) =>
                    $sq->where('question', 'like', "%{$term}%")
                       ->orWhere('answer', 'like', "%{$term}%")
                );
            })
            ->orderBy('sort_order')
            ->orderBy('id');

        $perPage = min((int) $request->input('per_page', 50), 200);

        return $this->paginated(FAQResource::collection($query->paginate($perPage)));
    }

    /**
     * Get FAQ
     *
     * @authenticated
     * @urlParam faq int required The FAQ ID.
     */
    public function show(FAQ $faq): JsonResponse
    {
        return $this->ok(new FAQResource($faq));
    }

    /**
     * Create FAQ
     *
     * @authenticated
     * @bodyParam question string required
     * @bodyParam answer string required
     * @bodyParam category string optional
     * @bodyParam is_active boolean optional Default: true.
     * @bodyParam sort_order int optional
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question'   => 'required|string|max:500',
            'answer'     => 'required|string',
            'category'   => 'nullable|string|max:100',
            'is_active'  => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $faq = FAQ::create($data);
        AuditService::logCreate('App\\Models\\FAQ', $faq);

        return $this->created(new FAQResource($faq));
    }

    /**
     * Update FAQ
     *
     * @authenticated
     * @urlParam faq int required The FAQ ID.
     */
    public function update(Request $request, FAQ $faq): JsonResponse
    {
        $data = $request->validate([
            'question'   => 'sometimes|string|max:500',
            'answer'     => 'sometimes|string',
            'category'   => 'nullable|string|max:100',
            'is_active'  => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $original = $faq->toArray();
        $faq->update($data);
        AuditService::logUpdate('App\\Models\\FAQ', $faq, $original);

        return $this->ok(new FAQResource($faq));
    }

    /**
     * Delete FAQ
     *
     * @authenticated
     * @urlParam faq int required The FAQ ID.
     */
    public function destroy(FAQ $faq): JsonResponse
    {
        AuditService::logDelete('App\\Models\\FAQ', $faq);
        $faq->delete();

        return $this->ok(null, 'FAQ deleted.');
    }
}
