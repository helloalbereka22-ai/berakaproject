<div class="bg-white dark:bg-gray-800 shadow rounded-lg h-full flex flex-col">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Low Stock Books
        </h3>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
            {{ count($books) }} alerts
        </span>
    </div>
    
    <div class="flex-1 overflow-y-auto p-0">
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($books as $book)
                <li class="px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $book['title'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Stock: <span class="font-semibold {{ $book['stock'] === 0 ? 'text-red-600' : 'text-yellow-600' }}">{{ $book['stock'] }}</span> / Min: {{ $book['min_stock'] }}
                            </p>
                        </div>
                        <button class="ml-4 inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-indigo-600 hover:bg-indigo-700 transition">
                            Request Stock
                        </button>
                    </div>
                </li>
            @empty
                <li class="px-5 py-10 text-center">
                    <svg class="mx-auto h-10 w-10 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">All books are well-stocked!</p>
                </li>
            @endforelse
        </ul>
    </div>
</div>
