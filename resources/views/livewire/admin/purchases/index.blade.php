<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Manual Purchases</h1>
        <a href="{{ route('admin.purchases.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Add Purchase</a>
    </div>

    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search references or products..." class="w-full md:w-1/3 border rounded p-2">
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Ref</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($purchases as $purchase)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $purchase->purchase_date->format('Y-m-d') }}</div>
                            <div class="text-sm text-gray-500">{{ $purchase->reference }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $purchase->product?->name ?? 'N/A' }}</div>
                            <div class="text-sm text-gray-500">{{ $purchase->business?->name }} &bull; {{ $purchase->getPurchaseTypeLabel() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $purchase->quantity }} {{ $purchase->unit }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ number_format($purchase->total_price, 2) }} ETB</div>
                            <div class="text-xs text-gray-500">@ {{ number_format($purchase->unit_price, 2) }} / {{ $purchase->unit }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $purchase->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($purchase->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($purchase->status !== 'voided')
                                <button wire:click="voidPurchase({{ $purchase->id }})" wire:confirm="Are you sure you want to void this purchase and reverse the stock?" class="text-red-600 hover:text-red-900">Void</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $purchases->links() }}
    </div>
</div>
