<?php

namespace App\Livewire\Admin\Cms;

use App\Models\Article;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class GmNewsManager extends Component
{
    use WithFileUploads, WithPagination;

    // ── List filters ──────────────────────────────────────────────────────
    public string $search      = '';
    public string $filterStatus = '';

    // ── Form mode: list | create | edit ───────────────────────────────────
    public string $mode        = 'list';
    public ?int   $editingId   = null;

    // ── Form fields ───────────────────────────────────────────────────────
    public string $title         = '';
    public string $excerpt       = '';
    public string $body          = '';
    public string $status        = 'draft';
    public string $publishDate   = '';
    public        $image         = null;
    public string $currentImage  = '';  // existing image path when editing

    // ── Delete confirm ────────────────────────────────────────────────────
    public bool   $confirmDelete  = false;
    public ?int   $deleteId       = null;

    // ── Notifications ─────────────────────────────────────────────────────
    public string $successMessage = '';
    public string $errorMessage   = '';

    public function mount(): void
    {
        $this->publishDate = now()->format('Y-m-d');
    }

    // ── Computed stats ────────────────────────────────────────────────────
    public function getStatsProperty(): array
    {
        $base = Article::withoutTrashed()->where('article_type', 'news');
        return [
            'total'      => (clone $base)->count(),
            'published'  => (clone $base)->where('status', 'published')->count(),
            'draft'      => (clone $base)->where('status', 'draft')->count(),
            'unpublished'=> (clone $base)->where('status', 'unpublished')->count(),
            'views'      => (clone $base)->sum('view_count'),
        ];
    }

    // ── Navigate to create form ───────────────────────────────────────────
    public function createNews(): void
    {
        $this->reset(['title','excerpt','body','image','currentImage','editingId']);
        $this->status      = 'draft';
        $this->publishDate = now()->format('Y-m-d');
        $this->mode        = 'create';
    }

    // ── Navigate to edit form ─────────────────────────────────────────────
    public function editNews(int $id): void
    {
        $article = Article::findOrFail($id);
        $this->editingId    = $id;
        $this->title        = $article->title;
        $this->excerpt      = $article->excerpt ?? '';
        $this->body         = $article->body    ?? '';
        $this->status       = $article->status;
        $this->publishDate  = $article->published_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->currentImage = $article->seo_metadata['news_image'] ?? '';
        $this->image        = null;
        $this->mode         = 'edit';
    }

    public function backToList(): void
    {
        $this->mode = 'list';
        $this->reset(['title','excerpt','body','image','currentImage','editingId','errorMessage']);
    }

    // ── Save (create or update) ───────────────────────────────────────────
    public function saveDraft(): void  { $this->status = 'draft';     $this->save(); }
    public function savePublish(): void { $this->status = 'published'; $this->save(); }

    public function save(): void
    {
        $this->errorMessage = '';

        $this->validate([
            'title'       => 'required|string|max:255',
            'excerpt'     => 'required|string|max:600',
            'body'        => 'required|string|min:10',
            'status'      => 'required|in:draft,published,unpublished',
            'publishDate' => 'nullable|date',
            'image'       => ($this->editingId ? 'nullable' : 'required')
                             . '|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $imagePath = $this->currentImage;

        if ($this->image) {
            // Delete old image if replacing
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            // Store new image with secure unique filename
            $ext       = $this->image->getClientOriginalExtension();
            $filename  = 'news_' . Str::uuid() . '.' . $ext;
            $imagePath = $this->image->storeAs('news', $filename, 'public');
        }

        $meta = ['news_image' => $imagePath];

        $publishedAt = $this->publishDate
            ? \Carbon\Carbon::parse($this->publishDate)
            : null;

        if ($this->editingId) {
            $article = Article::findOrFail($this->editingId);
            $article->update([
                'title'        => $this->title,
                'excerpt'      => $this->excerpt,
                'body'         => $this->body,
                'status'       => $this->status,
                'published_at' => $this->status === 'published' ? ($publishedAt ?? now()) : $publishedAt,
                'seo_metadata' => $meta,
            ]);
            $this->successMessage = 'News updated successfully.';
        } else {
            Article::create([
                'title'        => $this->title,
                'slug'         => Article::generateUniqueSlug($this->title),
                'excerpt'      => $this->excerpt,
                'body'         => $this->body,
                'status'       => $this->status,
                'article_type' => 'news',
                'published_at' => $this->status === 'published' ? ($publishedAt ?? now()) : $publishedAt,
                'seo_metadata' => $meta,
                'created_by'   => auth()->id(),
            ]);
            $this->successMessage = 'News ' . ($this->status === 'published' ? 'published' : 'saved as draft') . ' successfully.';
        }

        $this->mode = 'list';
        $this->reset(['title','excerpt','body','image','currentImage','editingId']);
        $this->resetPage();
    }

    // ── Toggle publish/unpublish ──────────────────────────────────────────
    public function toggleStatus(int $id): void
    {
        $article = Article::findOrFail($id);
        if ($article->status === 'published') {
            $article->update(['status' => 'unpublished']);
            $this->successMessage = 'News unpublished.';
        } else {
            $article->update(['status' => 'published', 'published_at' => $article->published_at ?? now()]);
            $this->successMessage = 'News published.';
        }
    }

    // ── Delete ────────────────────────────────────────────────────────────
    public function confirmDeleteNews(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteNews(): void
    {
        if ($this->deleteId) {
            $article = Article::findOrFail($this->deleteId);
            // Delete image from storage
            $imgPath = $article->seo_metadata['news_image'] ?? null;
            if ($imgPath && Storage::disk('public')->exists($imgPath)) {
                Storage::disk('public')->delete($imgPath);
            }
            $article->delete();
            $this->successMessage = 'News deleted successfully.';
        }
        $this->confirmDelete = false;
        $this->deleteId      = null;
        $this->resetPage();
    }

    public function updatedSearch(): void   { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function render()
    {
        $news = Article::where('article_type', 'news')
            ->when($this->search, fn($q) =>
                $q->where(fn($sq) =>
                    $sq->where('title',   'like', "%{$this->search}%")
                       ->orWhere('excerpt','like', "%{$this->search}%")
                )
            )
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.admin.cms.gm-news-manager', [
            'news'  => $news,
            'stats' => $this->stats,
            'mode'  => $this->mode,
        ])->title('News Management');
    }
}
