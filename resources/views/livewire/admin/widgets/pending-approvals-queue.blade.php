<div class="bg-white dark:bg-gray-800 shadow rounded-lg h-full flex flex-col">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Pending Approvals
        </h3>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
            {{ count($requests) }} pending
        </span>
    </div>
    
    <div class="flex-1 overflow-y-auto p-0">
        @if (session()->has('success'))
            <div class="p-2 bg-green-50 text-green-700 text-xs text-center border-b border-green-100">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="p-2 bg-red-50 text-red-700 text-xs text-center border-b border-red-100">
                {{ session('error') }}
            </div>
        @endif
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($requests as $request)
                <li class="px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $request->type ?? 'Inventory Request' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Branch: {{ $request->branch->name ?? 'N/A' }} &bull; {{ $request->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex space-x-2">
                            <button wire:click="approveRequest({{ $request->id }})" wire:loading.attr="disabled" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 transition disabled:opacity-50">
                                <span wire:loading.remove wire:target="approveRequest({{ $request->id }})">Approve</span>
                                <span wire:loading wire:target="approveRequest({{ $request->id }})">...</span>
                            </button>
                            <button class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                                Review
                            </button>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-5 py-10 text-center">
                    <svg class="mx-auto h-10 w-10 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">All caught up! No pending approvals.</p>
                </li>
            @endforelse
        </ul>
    </div>
    
    <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3 border-t border-gray-200 dark:border-gray-700 text-center">
        <a href="#" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">View All Requests</a>
    </div>
</div>
