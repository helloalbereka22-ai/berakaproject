<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Pages</h2>
            <p class="text-sm text-slate-400 mt-1">Static content pages — About, Contact, Terms, Privacy, etc.</p>
        </div>
        @can('content.create')
        <button class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Page
        </button>
        @endcan
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Title</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Slug</th>
                        <th class="text-center px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Updated</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($pages ?? [] as $page)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4 font-semibold text-white">{{ $page->title }}</td>
                            <td class="px-6 py-4 font-mono text-xs text-indigo-300">/{{ $page->slug }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold
                                    {{ $page->status === 'published' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700/50 text-slate-400' }}">
                                    {{ ucfirst($page->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 text-xs">{{ $page->updated_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                @can('content.update')
                                <button class="p-2 rounded-lg text-slate-500 hover:text-indigo-400 hover:bg-indigo-500/10 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-16 text-center text-slate-600">No pages yet. Full CMS implemented in Phase 5.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
