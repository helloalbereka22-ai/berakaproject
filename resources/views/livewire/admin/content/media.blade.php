<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Media Library</h2>
            <p class="text-sm text-slate-400 mt-1">Images and files used across products, articles, and pages.</p>
        </div>
        @can('content.create')
        <button class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Upload File
        </button>
        @endcan
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">File</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="text-right px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Size</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Uploaded by</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($mediaFiles ?? [] as $file)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-400 text-xs font-mono shrink-0">
                                        {{ strtoupper(pathinfo($file->file_name, PATHINFO_EXTENSION)) }}
                                    </div>
                                    <span class="font-medium text-white truncate max-w-xs">{{ $file->file_name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-400 text-xs font-mono">{{ $file->mime_type }}</td>
                            <td class="px-6 py-4 text-right text-slate-400 text-xs">{{ number_format($file->size / 1024, 1) }} KB</td>
                            <td class="px-6 py-4 text-slate-400 text-sm">{{ optional($file->uploadedBy)->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-slate-500 text-xs">{{ $file->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                @can('content.delete')
                                <button class="p-2 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-16 text-center text-slate-600">No media files uploaded. Full media manager in Phase 5.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
