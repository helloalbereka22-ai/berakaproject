<?php

namespace App\Livewire\Admin\Content;

use App\Models\Article;
use App\Models\Category;
use App\Services\AuditService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class ArticleIndex extends Component
{
    use WithPagination;

    // ── List filters ──────────────────────────────────────────────────────
    public string $search       = '';
    public string $filterStatus = '';
    public string $filterType   = '';

    // ── Form state ────────────────────────────────────────────────────────
    public bool   $showForm    = false;
    public ?int   $editId      = null;

    public string $title        = '';
    public string $slug         = '';
    public string $excerpt      = '';
    public string $body         = '';
    public string $status       = 'draft';
    public string $articleType  = 'article';
    public string $categoryId   = '';
    public string $publishedAt  = '';

    // ── List watchers ─────────────────────────────────────────────────────
    public function updatedSearch(): void      { $this->resetPage(); }
    public function updatedFilterStatus(): void{ $this->resetPage(); }
    public function updatedFilterType(): void  { $this->resetPage(); }

    public function updatedTitle(): void
    {
        // Auto-generate slug from title only when creating a new article
        if (!$this->editId && $this->title) {
            $this->slug = Str::slug($this->title);
        }
    }

    // ── CRUD ──────────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->authorize('content.create');
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('content.update');
        $article = Article::withTrashed()->findOrFail($id);

        $this->editId      = $id;
        $this->title       = $article->title;
        $this->slug        = $article->slug;
        $this->excerpt     = $article->excerpt ?? '';
        $this->body        = $article->body ?? '';
        $this->status      = $article->status;
        $this->articleType = $article->article_type ?? 'article';
        $this->categoryId  = $article->category_id ? (string) $article->category_id : '';
        $this->publishedAt = $article->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->showForm    = true;
    }

    public function save(): void
    {
        $this->validate([
            'title'       => 'required|string|max:255',
            'slug'        => 'required|string|max:255|regex:/^[a-z0-9\-]+$/',
            'excerpt'     => 'nullable|string|max:500',
            'body'        => 'required|string',
            'status'      => 'required|in:draft,published,archived',
            'articleType' => 'required|in:article,guide',
            'categoryId'  => 'nullable|integer|exists:categories,id',
            'publishedAt' => 'nullable|date',
        ]);

        // Sanitize HTML body — strip dangerous tags
        $safeBody = strip_tags($this->body,
            '<p><br><b><i><strong><em><ul><ol><li><h2><h3><h4><a><img><table><thead><tbody><tr><th><td><blockquote><hr><s><u>'
        );

        $payload = [
            'title'        => $this->title,
            'slug'         => $this->slug,
            'excerpt'      => $this->excerpt ?: null,
            'body'         => $safeBody,
            'status'       => $this->status,
            'article_type' => $this->articleType,
            'category_id'  => $this->categoryId ?: null,
            'published_at' => $this->publishedAt ?: ($this->status === 'published' ? now() : null),
            'created_by'   => auth()->id(),
        ];

        if ($this->editId) {
            $this->authorize('content.update');
            $article  = Article::findOrFail($this->editId);
            $original = $article->toArray();
            $article->update($payload);
            AuditService::logUpdate('App\\Models\\Article', $article, $original);
            session()->flash('success', 'Article updated successfully.');
        } else {
            $this->authorize('content.create');
            // Ensure unique slug
            if (Article::where('slug', $payload['slug'])->exists()) {
                $payload['slug'] = $payload['slug'] . '-' . Str::random(4);
            }
            $article = Article::create($payload);
            AuditService::logCreate('App\\Models\\Article', $article);
            session()->flash('success', 'Article created successfully.');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {
        $this->authorize('content.update');
        $article = Article::findOrFail($id);
        $old     = $article->status;

        $article->status = $old === 'published' ? 'draft' : 'published';
        if ($article->status === 'published' && !$article->published_at) {
            $article->published_at = now();
        }
        $article->save();

        AuditService::logUpdate('App\\Models\\Article', $article, ['status' => $old]);
        session()->flash('success', 'Article status updated.');
    }

    public function delete(int $id): void
    {
        $this->authorize('content.delete');
        $article = Article::findOrFail($id);
        AuditService::logDelete('App\\Models\\Article', $article);
        $article->delete();
        session()->flash('success', 'Article deleted (soft).');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->reset(['editId','title','slug','excerpt','body','publishedAt']);
        $this->status      = 'draft';
        $this->articleType = 'article';
        $this->categoryId  = '';
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render()
    {
        $articles = Article::withTrashed()
            ->with(['category', 'author'])
            ->when($this->search, function ($q) {
                $term = $this->search;
                $q->where(fn($sq) =>
                    $sq->where('title', 'like', "%{$term}%")
                       ->orWhere('slug',  'like', "%{$term}%")
                );
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterType,   fn($q) => $q->where('article_type', $this->filterType))
            ->orderByDesc('created_at')
            ->paginate(20);

        $categories = Category::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.content.articles', compact('articles', 'categories'))
            ->title('Articles');
    }
}
