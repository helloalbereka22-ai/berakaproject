<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex justify-between items-center bg-white/30 backdrop-blur-md rounded-2xl p-6 shadow-sm border border-white/50">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-500 bg-clip-text text-transparent">Manufacturing Orders</h1>
            <p class="text-gray-500 mt-1">Manage production BOMs and targets</p>
        </div>
        <button wire:click="$toggle('isCreating')" class="px-6 py-2.5 bg-gray-900 text-white rounded-xl hover:bg-gray-800 transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 font-medium">
            {{ $isCreating ? 'Cancel' : 'Create Order' }}
        </button>
    </div>

    @if($isCreating)
        <div class="bg-white/40 backdrop-blur-xl rounded-3xl p-8 shadow-xl border border-white/60">
            <h2 class="text-xl font-semibold mb-6">New Manufacturing Order</h2>
            
            <div class="grid grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Finished Good</label>
                    <select wire:model="targetProductId" class="w-full rounded-xl border-gray-200 bg-white/50 backdrop-blur-sm focus:ring-2 focus:ring-gray-900 transition-all">
                        <option value="">Select Product...</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} (SKU: {{ $product->sku }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Quantity</label>
                    <input type="number" wire:model="targetQuantity" class="w-full rounded-xl border-gray-200 bg-white/50 backdrop-blur-sm focus:ring-2 focus:ring-gray-900 transition-all">
                </div>
            </div>

            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-800">Bill of Materials (Raw Materials)</h3>
                    <button wire:click="addBomRow" class="text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">+ Add Material</button>
                </div>
                
                <div class="space-y-4">
                    @foreach($boms as $index => $bom)
                        <div class="flex items-center gap-4 bg-white/50 p-4 rounded-xl border border-white/60">
                            <div class="flex-1">
                                <select wire:model="boms.{{ $index }}.product_id" class="w-full rounded-lg border-gray-200 bg-white/70">
                                    <option value="">Select Raw Material...</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-48">
                                <input type="number" step="0.0001" placeholder="Qty per unit" wire:model="boms.{{ $index }}.quantity_per_unit" class="w-full rounded-lg border-gray-200 bg-white/70">
                            </div>
                            <button wire:click="removeBomRow({{ $index }})" class="p-2 text-red-500 hover:text-red-700 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-200/50">
                <button wire:click="createOrder" class="px-8 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 shadow-md font-medium transition-all hover:-translate-y-0.5">
                    Save Manufacturing Order
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
        @foreach($orders as $order)
            <div class="bg-white/40 backdrop-blur-xl rounded-3xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 border border-white/60 group">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center border border-blue-200/50 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200/50">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
                <h3 class="text-xl font-bold text-gray-800">{{ $order->product->name }}</h3>
                <p class="text-gray-500 text-sm mb-4">Target: <span class="font-bold text-gray-700">{{ $order->target_quantity }} units</span></p>
                
                <div class="space-y-2 mb-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Bill of Materials</p>
                    @foreach($order->boms as $bom)
                        <div class="flex justify-between text-sm bg-white/30 rounded-lg px-3 py-2 border border-white/40">
                            <span class="text-gray-600 truncate">{{ $bom->product->name }}</span>
                            <span class="font-medium text-gray-800">{{ $bom->quantity_per_unit }} / unit</span>
                        </div>
                    @endforeach
                </div>
                
                <div class="pt-4 border-t border-gray-200/50">
                    <p class="text-xs text-gray-400">Created {{ $order->created_at->diffForHumans() }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
