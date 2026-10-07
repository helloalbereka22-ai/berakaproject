<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex justify-between items-center bg-white/30 backdrop-blur-md rounded-2xl p-6 shadow-sm border border-white/50">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-500 bg-clip-text text-transparent">Production Batches</h1>
            <p class="text-gray-500 mt-1">Execute manufacturing orders and manage QC</p>
        </div>
        <button wire:click="$toggle('isStartingBatch')" class="px-6 py-2.5 bg-gray-900 text-white rounded-xl hover:bg-gray-800 transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 font-medium">
            {{ $isStartingBatch ? 'Cancel' : 'Start New Batch' }}
        </button>
    </div>

    @if (session()->has('error'))
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200">
            {{ session('error') }}
        </div>
    @endif
    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if($isStartingBatch)
        <div class="bg-white/40 backdrop-blur-xl rounded-3xl p-8 shadow-xl border border-white/60">
            <h2 class="text-xl font-semibold mb-6">Start New Production Batch</h2>
            
            <div class="grid grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Manufacturing Order</label>
                    <select wire:model="selectedOrderId" class="w-full rounded-xl border-gray-200 bg-white/50 backdrop-blur-sm focus:ring-2 focus:ring-gray-900 transition-all">
                        <option value="">Select Order...</option>
                        @foreach($activeOrders as $order)
                            <option value="{{ $order->id }}">{{ $order->product->name }} (Target: {{ $order->target_quantity }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity to Produce</label>
                    <input type="number" wire:model="quantityToProduce" class="w-full rounded-xl border-gray-200 bg-white/50 backdrop-blur-sm focus:ring-2 focus:ring-gray-900 transition-all">
                </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-200/50">
                <button wire:click="startBatch" class="px-8 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 shadow-md font-medium transition-all hover:-translate-y-0.5">
                    Start Batch
                </button>
            </div>
        </div>
    @endif

    <!-- Batches List -->
    <div class="space-y-4">
        @foreach($batches as $batch)
            <div class="bg-white/40 backdrop-blur-xl rounded-2xl p-6 shadow-sm border border-white/60 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-lg font-bold text-gray-800">{{ $batch->batch_number }}</span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border 
                            @if($batch->status === 'approved') bg-green-100 text-green-700 border-green-200 
                            @elseif($batch->status === 'in_progress') bg-blue-100 text-blue-700 border-blue-200
                            @else bg-gray-100 text-gray-700 border-gray-200 @endif">
                            {{ ucfirst($batch->status) }}
                        </span>
                    </div>
                    <p class="text-gray-600 font-medium">Target: {{ $batch->manufacturingOrder->product->name }}</p>
                    <p class="text-sm text-gray-500">Output Qty: {{ $batch->quantity }} | Cost/Unit: ${{ $batch->cost_per_unit ?? 'TBD' }}</p>
                    
                    <div class="mt-3 flex gap-2 overflow-x-auto">
                        @foreach($batch->consumedMaterials as $material)
                            <div class="px-2 py-1 bg-white/50 rounded text-xs text-gray-600 border border-gray-100">
                                {{ $material->product->name }} ({{ $material->consumed_quantity }})
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($batch->status === 'in_progress')
                    <div>
                        <button wire:click="openQcModal({{ $batch->id }})" class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm font-medium text-sm transition-all">
                            QC Review & Approve
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- QC Modal -->
    @if($qcBatchId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl">
                <h3 class="text-xl font-bold mb-4">Quality Control Approval</h3>
                <p class="text-sm text-gray-500 mb-6">Approving this batch will create finished goods in the factory warehouse and trigger cost roll-up.</p>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">QC Notes</label>
                    <textarea wire:model="qcNotes" rows="3" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('qcBatchId', null)" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl font-medium">Cancel</button>
                    <button wire:click="approveBatch" class="px-6 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 shadow-md font-medium">Approve Batch</button>
                </div>
            </div>
        </div>
    @endif
</div>
