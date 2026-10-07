<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group CMS — Articles
 * Blog posts, product guides, and news articles.
 */
class ArticleController extends ApiController
{
    public function __construct(private readonly AuthorizationService $authService) {}

    /**
     * List articles
     *
     * @authenticated
     * @queryParam status string Filter by status (draft|published|archived).
     * @queryParam article_type string Filter by type (article|guide).
     * @queryParam category_id int Filter by category.
     * @queryParam search string Full-text search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Article::with(['category', 'author'])
            ->when($request->filled('status'),       fn($q) => $q->where('status', $request->status))
            ->when($request->filled('article_type'), fn($q) => $q->where('article_type', $request->article_type))
            ->when($request->filled('category_id'),  fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->search;
                $q->where(fn($sq) =>
                    $sq->where('title', 'like', "%{$term}%")
                       ->orWhere('body', 'like', "%{$term}%")
                );
            })
            ->orderByDesc('published_at');

        // Public endpoint returns only published articles unless admin
        if (!$request->user()?->isSuperAdmin()) {
            $query->published();
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        return $this->paginated(ArticleResource::collection($query->paginate($perPage)));
    }

    /**
     * Get article by slug
     *
     * @authenticated
     * @urlParam slug string required The article slug.
     */
    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        $query = Article::where('slug', $slug)->with(['category', 'author', 'tags', 'coverImage']);

        if (!$request->user()?->isSuperAdmin()) {
            $query->published();
        }

        $article = $query->first();
        if (!$article) {
            return $this->notFound('Article not found.');
        }

        $article->increment('view_count');

        return $this->ok(new ArticleResource($article));
    }

    /**
     * Get article by ID
     *
     * @authenticated
     * @urlParam article int required The article ID.
     */
    public function show(Request $request, Article $article): JsonResponse
    {
        if ($article->status !== 'published' && !$request->user()?->isSuperAdmin()) {
            return $this->notFound('Article not found.');
        }

        return $this->ok(new ArticleResource($article->load(['category', 'author', 'tags'])));
    }

    /**
     * Create article
     *
     * @authenticated
     * @bodyParam title string required
     * @bodyParam body string required Rich text body (HTML stripped of dangerous tags).
     * @bodyParam excerpt string optional
     * @bodyParam category_id int optional
     * @bodyParam status string required (draft|published|archived).
     * @bodyParam article_type string optional (article|guide). Default: article.
     * @bodyParam published_at datetime optional
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'excerpt'      => 'nullable|string|max:500',
            'body'         => 'required|string',
            'category_id'  => 'nullable|integer|exists:categories,id',
            'status'       => 'required|in:draft,published,archived',
            'article_type' => 'nullable|in:article,guide',
            'published_at' => 'nullable|date',
            'slug'         => 'nullable|string|max:255|unique:articles,slug',
        ]);

        // Sanitize body — strip scripts/iframes
        $data['body']       = strip_tags($data['body'], '<p><br><b><i><strong><em><ul><ol><li><h2><h3><h4><a><img><table><thead><tbody><tr><th><td><blockquote>');
        $data['created_by'] = $request->user()->id;

        $article = Article::create($data);

        AuditService::logCreate('App\\Models\\Article', $article);

        return $this->created(new ArticleResource($article->load(['category', 'author'])));
    }

    /**
     * Update article
     *
     * @authenticated
     * @urlParam article int required The article ID.
     */
    public function update(Request $request, Article $article): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'excerpt'      => 'nullable|string|max:500',
            'body'         => 'sometimes|string',
            'category_id'  => 'nullable|integer|exists:categories,id',
            'status'       => 'sometimes|in:draft,published,archived',
            'article_type' => 'nullable|in:article,guide',
            'published_at' => 'nullable|date',
            'slug'         => "nullable|string|max:255|unique:articles,slug,{$article->id}",
        ]);

        if (isset($data['body'])) {
            $data['body'] = strip_tags($data['body'], '<p><br><b><i><strong><em><ul><ol><li><h2><h3><h4><a><img><table><thead><tbody><tr><th><td><blockquote>');
        }

        $original = $article->toArray();
        $article->update($data);

        AuditService::logUpdate('App\\Models\\Article', $article, $original);

        return $this->ok(new ArticleResource($article->load(['category', 'author'])));
    }

    /**
     * Delete article (soft delete)
     *
     * @authenticated
     * @urlParam article int required The article ID.
     */
    public function destroy(Request $request, Article $article): JsonResponse
    {
        AuditService::logDelete('App\\Models\\Article', $article);
        $article->delete();

        return $this->ok(null, 'Article deleted.');
    }
}
