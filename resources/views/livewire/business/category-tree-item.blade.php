<div class="border border-gray-100 rounded-lg bg-gray-50 mb-2">
    <div class="flex items-center p-3" style="margin-left: {{ $level * 1.5 }}rem;">
        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
        <span class="font-medium text-gray-900">{{ $category->name }}</span>
        <span class="ml-2 text-xs text-gray-500">({{ $category->slug }})</span>
    </div>
    
    @if($category->children->count() > 0)
        <div class="mt-1 border-t border-gray-100">
            @foreach($category->children as $child)
                @include('livewire.catalog.partials.category-tree-item', ['category' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
