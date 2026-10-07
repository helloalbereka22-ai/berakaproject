<div class="bg-white dark:bg-gray-800 shadow rounded-lg h-full flex flex-col">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Production Alerts
        </h3>
        <button class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">View All</button>
    </div>
    
    <div class="flex-1 overflow-y-auto p-0">
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($alerts as $alert)
                <li class="px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-0.5">
                            @if($alert['type'] === 'critical')
                                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            @elseif($alert['type'] === 'warning')
                                <svg class="h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <p class="text-sm text-gray-900 dark:text-white font-medium">
                                {{ $alert['message'] }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $alert['time'] }}
                            </p>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-5 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    No production alerts.
                </li>
            @endforelse
        </ul>
    </div>
</div>
