<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-white">Batch Processing</h2>
            <p class="text-slate-400 text-sm mt-1">Register production, material consumption, and finished goods.</p>
        </div>
        <a href="{{ route('admin.manufacturing.orders.create') }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold transition">
            + New Order
        </a>
    </div>

    {{-- Alerts --}}
    @if($successMessage)
        <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">{{ $errorMessage }}</div>
    @endif

    @php $order = $this->currentOrder; $batch = $this->currentBatch; @endphp

    {{-- STEP 1: Select Order --}}
    @if(!$batchId || $step === 1)
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800/80 flex flex-wrap items-center gap-3">
            <h3 class="text-sm font-bold text-white uppercase tracking-wide flex-1">Select Manufacturing Order</h3>

            <select wire:model.live="filterBusinessId" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none focus:border-indigo-500">
                <option value="">All Businesses</option>
                @foreach($businesses as $b)
                    <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterStatus" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none focus:border-indigo-500">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-800/60 bg-slate-800/20">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Product</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Size</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Planned Qty</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Factory</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    @forelse($this->orders as $o)
                    <tr class="hover:bg-slate-800/20 transition {{ $orderId === $o->id ? 'bg-indigo-500/5 border-l-2 border-indigo-500' : '' }}">
                        <td class="px-5 py-3 font-semibold text-white text-xs">{{ optional($o->product)->name }}</td>
                        <td class="px-5 py-3 text-slate-400 text-xs">{{ $o->package_size ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-mono text-slate-200 text-xs">{{ number_format($o->planned_quantity, 0) }}</td>
                        <td class="px-5 py-3 text-slate-400 text-xs">{{ optional($o->branch)->name }}</td>
                        <td class="px-5 py-3">
                            @php $sc=['pending'=>'amber','in_progress'=>'blue','completed'=>'emerald']; $c=$sc[$o->status]??'slate'; @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $c }}-500/10 text-{{ $c }}-400 border border-{{ $c }}-500/20">
                                {{ ucfirst(str_replace('_',' ',$o->status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if($o->activeBatch)
                                <button wire:click="selectOrder({{ $o->id }})"
                                    class="px-3 py-1.5 rounded-lg bg-blue-600/80 hover:bg-blue-600 text-white text-xs font-bold transition">
                                    Resume Batch
                                </button>
                            @elseif($o->canOpenBatch())
                                <button wire:click="selectOrder({{ $o->id }})"
                                    class="px-3 py-1.5 rounded-lg bg-emerald-600/80 hover:bg-emerald-600 text-white text-xs font-bold transition">
                                    Start Batch
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-600 text-sm">No manufacturing orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- STEPS 2–5: Batch in progress --}}
    @if($orderId)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Order + Batch info --}}
        <div class="space-y-4">
            @if($order)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Manufacturing Order</h3>
                <p class="font-bold text-white text-sm">{{ optional($order->product)->name }}</p>
                <p class="text-slate-400 text-xs mt-0.5">{{ optional($order->branch)->name }}</p>
                @if($order->package_size)
                    <p class="text-slate-400 text-xs">Size: {{ $order->package_size }}</p>
                @endif
                <p class="text-slate-400 text-xs">Planned: {{ number_format($order->planned_quantity, 0) }} units</p>
            </div>
            @endif

            @if($batch)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Current Batch</h3>
                <p class="font-mono font-bold text-indigo-300 text-sm">{{ $batch->batch_number }}</p>
                <p class="text-slate-400 text-xs mt-1">Status: <span class="text-white font-semibold">{{ ucfirst($batch->status) }}</span></p>
                @if($batch->oil_quantity_produced > 0)
                    <p class="text-slate-400 text-xs">Output: {{ $batch->oil_quantity_produced }} {{ $batch->oil_unit }}</p>
                @endif
                <p class="text-slate-400 text-xs">Raw Cost: {{ number_format($batch->raw_material_cost, 2) }} ETB</p>
                <p class="text-slate-400 text-xs">Pack Cost: {{ number_format($batch->packing_cost, 2) }} ETB</p>

                {{-- Consumption summary --}}
                @if($batch->rawMaterialConsumptions->count())
                <div class="mt-3 pt-3 border-t border-slate-800">
                    <p class="text-xs font-semibold text-slate-500 mb-1">Raw Consumed:</p>
                    @foreach($batch->rawMaterialConsumptions as $c)
                        <p class="text-xs text-slate-300">{{ optional($c->rawMaterial)->name }}: {{ $c->quantity_used }} {{ optional($c->rawMaterial)->unit }}</p>
                    @endforeach
                </div>
                @endif
                @if($batch->packingConsumptions->count())
                <div class="mt-2">
                    <p class="text-xs font-semibold text-slate-500 mb-1">Packing Consumed:</p>
                    @foreach($batch->packingConsumptions as $c)
                        <p class="text-xs text-slate-300">{{ optional($c->rawMaterial)->name }}: {{ $c->quantity_used }}</p>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
        </div>

        {{-- Right: Active step form --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Step 2a: Open batch (if no batch yet) --}}
            @if($orderId && !$batchId)
            <div class="bg-slate-900 border border-indigo-500/40 rounded-2xl p-5">
                <h3 class="text-sm font-bold text-white mb-4">Step 1 — Open Batch</h3>
                <div class="flex items-center gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Oil Unit</label>
                        <select wire:model="oilUnit" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none">
                            <option value="litre">Litre</option>
                            <option value="ml">ml</option>
                        </select>
                    </div>
                    <div class="pt-5">
                        <button wire:click="openBatch" wire:loading.attr="disabled"
                            class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold transition">
                            Open Batch
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- Step 2b: Consume raw materials (batch is open) --}}
            @if($batch && $batch->status === 'open')
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <h3 class="text-sm font-bold text-white mb-4">Step 2 — Consume Raw Materials</h3>
                @foreach($rawLines as $i => $line)
                <div class="grid grid-cols-12 gap-2 mb-2 items-end">
                    <div class="col-span-4">
                        @if($i === 0)<label class="block text-xs text-slate-500 mb-1">Location</label>@endif
                        <select wire:model="rawLines.{{ $i }}.location_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none">
                            <option value="">— Location —</option>
                            @foreach($locations as $l)
                                <option value="{{ $l['id'] }}">{{ $l['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-3">
                        @if($i === 0)<label class="block text-xs text-slate-500 mb-1">Material</label>@endif
                        <select wire:model="rawLines.{{ $i }}.product_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none">
                            <option value="">— Material —</option>
                            @foreach($rawMaterials as $m)
                                <option value="{{ $m['id'] }}">{{ $m['name'] }} ({{ $m['unit'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        @if($i === 0)<label class="block text-xs text-slate-500 mb-1">Qty</label>@endif
                        <input wire:model="rawLines.{{ $i }}.quantity" type="number" step="0.001" placeholder="Qty"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 placeholder-slate-600 outline-none">
                    </div>
                    <div class="col-span-2">
                        @if($i === 0)<label class="block text-xs text-slate-500 mb-1">Lot #</label>@endif
                        <input wire:model="rawLines.{{ $i }}.lot_number" type="text" placeholder="Lot"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 placeholder-slate-600 outline-none">
                    </div>
                    <div class="col-span-1 flex justify-end">
                        @if($i > 0)
                            <button wire:click="removeRawLine({{ $i }})" class="text-red-500 hover:text-red-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>
                </div>
                @endforeach
                <div class="flex items-center gap-3 mt-3">
                    <button wire:click="addRawLine" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold">+ Add Row</button>
                    <button wire:click="consumeRawMaterials" wire:loading.attr="disabled"
                        class="ml-auto px-5 py-2 rounded-xl bg-amber-600/80 hover:bg-amber-600 text-white text-xs font-bold transition">
                        Record Consumption
                    </button>
                </div>
            </div>

            {{-- Step 3: Record oil output --}}
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <h3 class="text-sm font-bold text-white mb-4">Step 3 — Record Oil Output</h3>
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-1">
                        <label class="block text-xs text-slate-500 mb-1">Quantity *</label>
                        <input wire:model="oilQuantity" type="number" step="0.001" placeholder="e.g. 800"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        @error('oilQuantity') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs text-slate-500 mb-1">Unit *</label>
                        <select wire:model="oilUnit" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none">
                            <option value="litre">Litre</option>
                            <option value="ml">ml</option>
                        </select>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs text-slate-500 mb-1">Output Location *</label>
                        <select wire:model="oilLocationId" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none">
                            <option value="">— Location —</option>
                            @foreach($locations as $l)
                                <option value="{{ $l['id'] }}">{{ $l['name'] }}</option>
                            @endforeach
                        </select>
                        @error('oilLocationId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end mt-3">
                    <button wire:click="recordOilOutput" wire:loading.attr="disabled"
                        class="px-5 py-2 rounded-xl bg-violet-600/80 hover:bg-violet-600 text-white text-xs font-bold transition">
                        Record Output → Submit to QC
                    </button>
                </div>
            </div>
            @endif

            {{-- Step 4: QC --}}
            @if($batch && $batch->status === 'in_qc')
            <div class="bg-slate-900 border border-amber-500/30 rounded-2xl p-5">
                <h3 class="text-sm font-bold text-white mb-4">Step 4 — Quality Check</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">QC Decision *</label>
                        <select wire:model="qcStatus" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none">
                            <option value="approved">Approved ✓</option>
                            <option value="rejected">Rejected ✗</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Qty Checked</label>
                        <input wire:model="qcQuantityCheck" type="number" placeholder="0"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Qty Passed</label>
                        <input wire:model="qcQuantityPass" type="number" placeholder="0"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Qty Failed</label>
                        <input wire:model="qcQuantityFail" type="number" placeholder="0"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-xs text-slate-500 mb-1">Remarks</label>
                    <textarea wire:model="qcRemarks" rows="2" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none resize-none"></textarea>
                </div>
                @if($qcStatus === 'rejected')
                <div class="mt-3">
                    <label class="block text-xs text-slate-500 mb-1">Rejection Reason</label>
                    <textarea wire:model="qcRejectedReason" rows="2" class="w-full bg-slate-950 border border-amber-500/30 rounded-xl px-3 py-2 text-sm text-red-200 outline-none resize-none"></textarea>
                </div>
                @endif
                <div class="flex justify-end mt-4">
                    <button wire:click="submitQc" wire:loading.attr="disabled"
                        class="px-5 py-2 rounded-xl {{ $qcStatus === 'approved' ? 'bg-emerald-600/80 hover:bg-emerald-600' : 'bg-red-600/80 hover:bg-red-600' }} text-white text-xs font-bold transition">
                        Submit QC — {{ ucfirst($qcStatus) }}
                    </button>
                </div>
            </div>
            @endif

            {{-- Step 5: Complete + packing --}}
            @if($batch && $batch->status === 'approved')
            <div class="bg-slate-900 border border-emerald-500/30 rounded-2xl p-5">
                <h3 class="text-sm font-bold text-white mb-4">Step 5 — Complete Batch & Pack</h3>

                {{-- Packing materials --}}
                <p class="text-xs font-semibold text-slate-400 uppercase mb-2">Packaging Consumption (optional)</p>
                @foreach($packingLines as $i => $line)
                <div class="grid grid-cols-10 gap-2 mb-2 items-end">
                    <div class="col-span-3">
                        @if($i === 0)<label class="block text-xs text-slate-500 mb-1">Location</label>@endif
                        <select wire:model="packingLines.{{ $i }}.location_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none">
                            <option value="">— Location —</option>
                            @foreach($locations as $l)
                                <option value="{{ $l['id'] }}">{{ $l['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-4">
                        @if($i === 0)<label class="block text-xs text-slate-500 mb-1">Packing Material</label>@endif
                        <select wire:model="packingLines.{{ $i }}.product_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none">
                            <option value="">— Material —</option>
                            @foreach($packingMats as $m)
                                <option value="{{ $m['id'] }}">{{ $m['name'] }} ({{ $m['unit'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        @if($i === 0)<label class="block text-xs text-slate-500 mb-1">Qty</label>@endif
                        <input wire:model="packingLines.{{ $i }}.quantity" type="number" step="1" placeholder="Qty"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none">
                    </div>
                    <div class="col-span-1 flex justify-end">
                        @if($i > 0)
                            <button wire:click="removePackingLine({{ $i }})" class="text-red-500 hover:text-red-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>
                </div>
                @endforeach
                <button wire:click="addPackingLine" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold mb-4">+ Add Packing Row</button>

                <div class="grid grid-cols-2 gap-4 mt-2">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Finished Goods Location *</label>
                        <select wire:model="fgLocationId" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none focus:border-emerald-500">
                            <option value="">— Location —</option>
                            @foreach($locations as $l)
                                <option value="{{ $l['id'] }}">{{ $l['name'] }}</option>
                            @endforeach
                        </select>
                        @error('fgLocationId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Selling Price / ml (optional)</label>
                        <input wire:model="sellingPricePerMl" type="number" step="0.0001" placeholder="0.0000"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none">
                    </div>
                </div>
                <div class="flex justify-end mt-4">
                    <button wire:click="completeBatch" wire:loading.attr="disabled"
                        wire:confirm="Complete this batch? Finished goods will be added to inventory."
                        class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold transition">
                        ✓ Complete Batch & Create Finished Goods
                    </button>
                </div>
            </div>
            @endif

        </div>{{-- end right col --}}
    </div>
    @endif

</div>
