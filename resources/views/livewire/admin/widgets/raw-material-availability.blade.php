<div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden h-full flex flex-col">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Raw Material Availability
        </h3>
        <button class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Request Stock</button>
    </div>
    
    <div class="flex-1 overflow-y-auto p-0">
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($materials as $material)
                <li class="px-5 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $material['name'] }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $material['quantity'] }} {{ $material['unit'] }} available</span>
                    </div>
                    <div>
                        @if($material['status'] === 'Healthy')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $material['status'] }}
                            </span>
                        @elseif($material['status'] === 'Low Stock')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                {{ $material['status'] }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ $material['status'] }}
                            </span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
