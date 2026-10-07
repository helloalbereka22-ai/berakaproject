<div class="bg-white dark:bg-gray-800 shadow rounded-lg h-full flex flex-col">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Platform Audit Logs
        </h3>
        <a href="#" class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Export CSV</a>
    </div>
    
    <div class="flex-1 overflow-x-auto p-0">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module / Action</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                            {{ $log->created_at->format('M d, H:i') }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                {{ strtoupper($log->module) }}
                            </span>
                            <span class="ml-2 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-900 dark:text-white truncate max-w-xs" title="{{ $log->description }}">
                            {{ $log->description }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                            {{ $log->user->name ?? 'System' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            No audit logs generated yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
