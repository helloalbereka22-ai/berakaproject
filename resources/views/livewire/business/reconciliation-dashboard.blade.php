<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white/30 backdrop-blur-md rounded-2xl p-6 shadow-sm border border-white/50">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-500 bg-clip-text text-transparent">Reconciliation Engine</h1>
            <p class="text-gray-500 mt-1">Verify manufacturing output against physical ledger stock.</p>
            @if($lastRun)
                <p class="text-xs text-gray-400 mt-2">Last sweep: {{ $lastRun->format('Y-m-d H:i:s') }}</p>
            @endif
        </div>
        <button wire:click="runSweep" wire:loading.attr="disabled" class="mt-4 md:mt-0 px-8 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all shadow-lg hover:shadow-xl font-medium flex items-center gap-2">
            <span wire:loading.remove>Run Full Sweep</span>
            <span wire:loading>Running Sweep...</span>
            <svg wire:loading class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    </div>

    @if (session()->has('success'))
        <div class="p-4 rounded-2xl bg-green-50/80 backdrop-blur-sm border border-green-200 flex items-center gap-3">
            <div class="p-2 bg-green-100 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <p class="text-green-800 font-medium">{{ session('success') }}</p>
        </div>
    @endif
    
    @if (session()->has('warning'))
        <div class="p-4 rounded-2xl bg-orange-50/80 backdrop-blur-sm border border-orange-200 flex items-center gap-3">
            <div class="p-2 bg-orange-100 rounded-full">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <p class="text-orange-800 font-medium">{{ session('warning') }}</p>
        </div>
    @endif

    @if(count($variances) > 0)
        <div class="bg-white/40 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-red-200/50">
            <h2 class="text-xl font-bold text-red-600 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Variances Detected
            </h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="py-3 px-4 text-sm font-semibold text-gray-600">Product ID</th>
                            <th class="py-3 px-4 text-sm font-semibold text-gray-600">Total Manufactured</th>
                            <th class="py-3 px-4 text-sm font-semibold text-gray-600">Total Sold</th>
                            <th class="py-3 px-4 text-sm font-semibold text-gray-600">Physical Stock</th>
                            <th class="py-3 px-4 text-sm font-bold text-red-600">Variance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($variances as $v)
                            <tr class="border-b border-gray-100/50 hover:bg-white/50 transition-colors">
                                <td class="py-4 px-4 font-medium">{{ $v['product_id'] }}</td>
                                <td class="py-4 px-4 text-gray-600">{{ $v['total_manufactured'] }}</td>
                                <td class="py-4 px-4 text-gray-600">{{ $v['total_sold'] }}</td>
                                <td class="py-4 px-4 text-gray-600">{{ $v['current_stock'] }}</td>
                                <td class="py-4 px-4">
                                    <span class="px-3 py-1 rounded-full text-sm font-bold bg-red-100 text-red-700 border border-red-200">
                                        {{ $v['variance'] }} units
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 p-4 bg-orange-50 rounded-xl border border-orange-100 text-sm text-orange-800">
                <p><strong>Note:</strong> A variance means that the sum of all physical stock movements (ledgers) does not match the total output of approved production batches minus sales. This indicates manual stock tampering, unaccounted shrinkage, or ledger errors.</p>
            </div>
        </div>
    @elseif($lastRun !== null)
        <div class="flex flex-col items-center justify-center p-12 bg-white/30 backdrop-blur-xl rounded-3xl border border-white/60">
            <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">Perfect Balance</h3>
            <p class="text-gray-500 text-center max-w-md">The ledger matches production perfectly. No variances were found across any warehouses.</p>
        </div>
    @endif
</div>
