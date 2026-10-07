<div class="bg-white dark:bg-gray-800 shadow rounded-lg h-full flex flex-col">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Recent Workflow Requests (All Branches)
        </h3>
        <a href="#" class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">View All</a>
    </div>
    
    <div class="flex-1 p-0 overflow-y-auto">
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($recentRequests as $request)
                <li class="px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $request->type }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Branch: {{ $request->branch->name ?? 'N/A' }} | User #{{ $request->requested_by }}
                        </p>
                    </div>
                    <div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $request->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ in_array($request->status, ['submitted', 'under_review']) ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $request->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                        ">
                            {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                        </span>
                    </div>
                </li>
            @empty
                <li class="px-5 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    No recent requests found in this business.
                </li>
            @endforelse
        </ul>
    </div>
</div>
