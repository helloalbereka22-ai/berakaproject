<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Batch Traceability</h2>
            <p class="text-sm text-slate-400 mt-1">Complete lifecycle view: materials → production → QC → distribution → sales → reconciliation.</p>
        </div>
        @if(count($compareIds) >= 2)
        <button wire:click="runComparison"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold transition">
            Compare {{ count($compareIds) }} Batches
        </button>
        @endif
    </div>

    {{-- Flash messages --}}
    @if(session('success'))<div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm">{{ session('success') }}</div>@endif
    @if(session('warning'))<div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm font-semibold">⚠ {{ session('warning') }}</div>@endif
    @if(session('error'))<div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">{{ session('error') }}</div>@endif

    {{-- Unresolved mismatches alert --}}
    @if($mismatches->isNotEmpty())
    <div class="bg-red-950/40 border border-red-500/30 rounded-2xl p-5">
        <h3 class="font-bold text-red-400 mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            {{ $mismatches->count() }} Unresolved Reconciliation Mismatch(es) — Investigation Required
        </h3>
        <div class="space-y-2">
            @foreach($mismatches as $mm)
            <div class="flex items-center justify-between p-3 bg-red-950/60 rounded-xl border border-red-500/20">
                <div>
                    <span class="text-sm font-bold text-white">{{ optional($mm->batch)->batch_number }}</span>
                    <span class="text-xs text-slate-400 ml-2">{{ optional($mm->batch?->manufacturingOrder?->product)->name }}</span>
                    <span class="ml-3 text-xs font-bold text-red-400">Variance: {{ $mm->variance }}</span>
                    @if($mm->status === 'investigating')
                    <span class="ml-2 inline-flex px-2 py-0.5 rounded-full text-xs bg-amber-500/20 text-amber-400">Investigating</span>
                    @endif
                </div>
                <div class="flex gap-2">
                    <button wire:click="selectBatch({{ optional($mm->batch)->id }})" class="text-xs px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition">View</button>
                    @if($mm->status !== 'resolved')
                    <button wire:click="openReconAction({{ $mm->id }}, 'investigate')" class="text-xs px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition">Investigate</button>
                    <button wire:click="openReconAction({{ $mm->id }}, 'resolve')" class="text-xs px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-600 text-white transition">Resolve</button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Batch number..."
            class="col-span-2 lg:col-span-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-indigo-500 outline-none">
        <select wire:model.live="businessId" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Businesses</option>
            @foreach($businesses as $b)<option value="{{ $b['id'] }}">{{ $b['name'] }}</option>@endforeach
        </select>
        <select wire:model.live="status" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">All Statuses</option>
            @foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
        <input wire:model.live="fromDate" type="date" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        <input wire:model.live="toDate" type="date" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Batch List --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800">
                <h3 class="font-bold text-white">Production Batches</h3>
            </div>
            <div class="divide-y divide-slate-800/60">
                @forelse($batches as $batch)
                @php
                    $isSelected = $selectedBatchId === $batch->id;
                    $hasMismatch = $batch->reconciliation?->has_mismatch;
                    $sc = ['open'=>'sky','in_qc'=>'amber','approved'=>'violet','rejected'=>'red','packed'=>'blue','completed'=>'emerald','cancelled'=>'slate'];
                    $c = $sc[$batch->status] ?? 'slate';
                @endphp
                <div class="px-5 py-4 hover:bg-slate-800/30 transition {{ $isSelected ? 'bg-indigo-900/20 border-l-2 border-indigo-500' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-white font-mono text-sm">{{ $batch->batch_number }}</span>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $c }}-500/10 text-{{ $c }}-400 border border-{{ $c }}-500/20">
                                    {{ $batch->getStatusLabel() }}
                                </span>
                                @if($hasMismatch)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-red-500/10 text-red-400 border border-red-500/20">
                                    ⚠ Mismatch
                                </span>
                                @endif
                                @if(in_array($batch->id, $compareIds))
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-violet-500/20 text-violet-300">★ Compare</span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-300 mt-1">{{ optional($batch->manufacturingOrder?->product)->name }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $batch->started_at?->format('d M Y') }}
                                @if($batch->oil_quantity_produced > 0)
                                    · {{ number_format($batch->oil_quantity_produced) }} {{ $batch->oil_unit }}
                                @endif
                                @if($batch->profit != 0)
                                    · Profit: {{ number_format($batch->profit, 2) }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button wire:click="selectBatch({{ $batch->id }})"
                                class="p-1.5 rounded-lg {{ $isSelected ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-indigo-400 hover:bg-indigo-500/10' }} transition" title="View traceability">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            <button wire:click="reconcile({{ $batch->id }})"
                                class="p-1.5 rounded-lg text-slate-500 hover:text-amber-400 hover:bg-amber-500/10 transition" title="Run reconciliation">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </button>
                            @if($batch->isQcApproved() && $batch->finishedGood)
                            <button wire:click="openDistributeModal({{ $batch->id }})"
                                class="p-1.5 rounded-lg text-slate-500 hover:text-blue-400 hover:bg-blue-500/10 transition" title="Distribute">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            </button>
                            @endif
                            <button wire:click="addToCompare({{ $batch->id }})"
                                class="p-1.5 rounded-lg text-slate-500 hover:text-violet-400 hover:bg-violet-500/10 transition" title="Add to comparison">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                    <div class="px-5 py-12 text-center text-slate-600">No batches found.</div>
                @endforelse
            </div>
            <div class="px-5 py-4 border-t border-slate-800">{{ $batches->links() }}</div>
        </div>

        {{-- Traceability Detail --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            @if($selectedBatchId && count($traceData))
            @php $t = $traceData; @endphp

            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-white font-mono">{{ $t['batch_number'] }}</h3>
                    <p class="text-xs text-slate-400">{{ $t['product'] }} {{ $t['package_size'] ? '· '.$t['package_size'] : '' }}</p>
                </div>
                <button wire:click="clearSelection" class="p-2 text-slate-500 hover:text-white hover:bg-slate-800 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="overflow-y-auto max-h-[72vh] divide-y divide-slate-800/60">

                {{-- Production info --}}
                <div class="px-5 py-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Production</p>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div><span class="text-slate-500">Factory</span><p class="text-white font-semibold">{{ $t['factory'] ?? '—' }}</p></div>
                        <div><span class="text-slate-500">Date</span><p class="text-white font-semibold">{{ $t['production_date'] ?? '—' }}</p></div>
                        <div><span class="text-slate-500">Supervisor</span><p class="text-white font-semibold">{{ $t['supervisor'] ?? '—' }}</p></div>
                        <div><span class="text-slate-500">Status</span>
                            <p><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-300">{{ $t['status_label'] }}</span></p>
                        </div>
                    </div>
                </div>

                {{-- Raw materials --}}
                <div class="px-5 py-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Raw Materials</p>
                    @foreach($t['raw_materials'] as $rm)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800/40 last:border-0">
                        <div>
                            <p class="text-sm font-semibold text-white">{{ $rm['material'] }}</p>
                            <p class="text-xs text-slate-500">{{ $rm['category'] }}@if($rm['lot_number']) · Lot: {{ $rm['lot_number'] }}@endif</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-white">{{ number_format($rm['quantity_kg'], 2) }} kg</p>
                            <p class="text-xs text-slate-500">@ {{ number_format($rm['unit_cost'], 4) }} = {{ number_format($rm['total_cost'], 2) }}</p>
                        </div>
                    </div>
                    @endforeach
                    <div class="flex justify-between pt-2 text-sm font-bold">
                        <span class="text-slate-400">Total Raw Material Cost</span>
                        <span class="text-white">{{ number_format($t['total_raw_material_cost'], 2) }}</span>
                    </div>
                </div>

                {{-- Oil output --}}
                <div class="px-5 py-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Oil Output & Yield</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-slate-800/50 rounded-xl p-3">
                            <p class="text-xs text-slate-500">Input (kg)</p>
                            <p class="text-xl font-bold text-white">{{ number_format($t['total_raw_material_kg'], 2) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-xl p-3">
                            <p class="text-xs text-slate-500">Output ({{ $t['oil_unit'] }})</p>
                            <p class="text-xl font-bold text-emerald-400">{{ number_format($t['oil_quantity_produced'], 2) }}</p>
                        </div>
                        <div class="bg-indigo-900/30 rounded-xl p-3 border border-indigo-500/20">
                            <p class="text-xs text-indigo-400">Yield %</p>
                            <p class="text-xl font-bold text-indigo-300">{{ $t['yield_percentage'] }}%</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-xl p-3">
                            <p class="text-xs text-slate-500">Cost / Litre</p>
                            <p class="text-xl font-bold text-white">{{ number_format($t['cost_per_litre'], 4) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Packing --}}
                @if(count($t['packing_materials']))
                <div class="px-5 py-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Packing Materials</p>
                    @foreach($t['packing_materials'] as $pm)
                    <div class="flex items-center justify-between py-1.5 text-sm">
                        <span class="text-slate-300">{{ $pm['material'] }}</span>
                        <span class="text-white">{{ number_format($pm['quantity'], 2) }} × {{ number_format($pm['unit_cost'], 4) }} = <strong>{{ number_format($pm['total_cost'], 2) }}</strong></span>
                    </div>
                    @endforeach
                    <div class="flex justify-between pt-2 text-sm font-bold border-t border-slate-800">
                        <span class="text-slate-400">Total Packing Cost</span>
                        <span class="text-white">{{ number_format($t['total_packing_cost'], 2) }}</span>
                    </div>
                </div>
                @endif

                {{-- QC --}}
                <div class="px-5 py-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Quality Control</p>
                    @if($t['qc_status'])
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div><span class="text-slate-500">Result</span>
                            <p><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold {{ $t['qc_status'] === 'approved' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' }}">{{ ucfirst($t['qc_status']) }}</span></p>
                        </div>
                        <div><span class="text-slate-500">Inspector</span><p class="text-white">{{ $t['qc_inspector'] ?? '—' }}</p></div>
                        <div><span class="text-slate-500">Inspection Date</span><p class="text-white">{{ $t['qc_date'] ?? '—' }}</p></div>
                        <div><span class="text-slate-500">Approval Date</span><p class="text-white">{{ $t['qc_approval_date'] ?? '—' }}</p></div>
                        @if($t['qc_remarks'])
                        <div class="col-span-2"><span class="text-slate-500">Remarks</span><p class="text-white text-xs">{{ $t['qc_remarks'] }}</p></div>
                        @endif
                        @if($t['qc_rejected_reason'])
                        <div class="col-span-2"><span class="text-red-400 text-xs">Rejection Reason</span><p class="text-red-300 text-xs">{{ $t['qc_rejected_reason'] }}</p></div>
                        @endif
                    </div>
                    @else
                        <p class="text-slate-500 text-sm">QC not yet performed.</p>
                    @endif
                </div>

                {{-- Finished goods & stock --}}
                <div class="px-5 py-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Finished Goods</p>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <div class="bg-slate-800/50 rounded-xl p-3 text-center">
                            <p class="text-xs text-slate-500">Produced</p>
                            <p class="text-lg font-bold text-white">{{ number_format($t['finished_quantity'], 2) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-xl p-3 text-center">
                            <p class="text-xs text-slate-500">Current Stock</p>
                            <p class="text-lg font-bold text-indigo-300">{{ number_format($t['current_stock'], 2) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-xl p-3 text-center">
                            <p class="text-xs text-slate-500">Remaining</p>
                            <p class="text-lg font-bold text-emerald-300">{{ number_format($t['remaining_quantity'], 2) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-xl p-3 text-center">
                            <p class="text-xs text-slate-500">Distributed</p>
                            <p class="text-lg font-bold text-blue-300">{{ number_format($t['distributed_quantity'], 2) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-xl p-3 text-center">
                            <p class="text-xs text-slate-500">Sold</p>
                            <p class="text-lg font-bold text-amber-300">{{ number_format($t['sold_quantity'], 2) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-xl p-3 text-center">
                            <p class="text-xs text-slate-500">Returned</p>
                            <p class="text-lg font-bold text-pink-300">{{ number_format($t['returned_quantity'], 2) }}</p>
                        </div>
                    </div>
                    @if($t['finished_warehouse'])
                    <p class="text-xs text-slate-500 mt-2">Warehouse: <span class="text-slate-300">{{ $t['finished_warehouse'] }}</span></p>
                    @endif
                </div>

                {{-- Distribution --}}
                @if(count($t['distributions']))
                <div class="px-5 py-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Distribution</p>
                    @foreach($t['distributions'] as $d)
                    <div class="p-3 bg-slate-800/40 rounded-xl mb-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs text-indigo-300">{{ $d['reference'] }}</span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $d['status'] === 'completed' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400' }}">
                                {{ ucfirst(str_replace('_', ' ', $d['status'])) }}
                            </span>
                        </div>
                        <p class="text-slate-300 mt-1">→ {{ $d['destination_branch'] }} / {{ $d['destination_location'] }}</p>
                        <div class="grid grid-cols-3 gap-2 mt-2 text-xs">
                            <div><span class="text-slate-500">Sent</span><p class="font-bold text-white">{{ number_format($d['quantity_sent'], 2) }}</p></div>
                            <div><span class="text-slate-500">Received</span><p class="font-bold text-white">{{ number_format($d['quantity_received'], 2) }}</p></div>
                            <div><span class="text-slate-500">Date</span><p class="text-white">{{ $d['distributed_at'] ?? '—' }}</p></div>
                        </div>
                        @if($d['notes'])<p class="text-xs text-slate-500 mt-1">{{ $d['notes'] }}</p>@endif
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Returns --}}
                @if(count($t['returns']))
                <div class="px-5 py-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Returns</p>
                    @foreach($t['returns'] as $r)
                    <div class="flex items-center justify-between py-2 text-sm border-b border-slate-800/40 last:border-0">
                        <div>
                            <p class="text-white font-semibold">{{ number_format($r['quantity'], 2) }} units</p>
                            <p class="text-xs text-slate-500">{{ $r['reason'] }} · {{ $r['returned_by'] }}</p>
                        </div>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $r['status'] === 'approved' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400' }}">
                            {{ ucfirst($r['status']) }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Financials --}}
                <div class="px-5 py-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Financials</p>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-400">Revenue</span><span class="text-white font-bold">{{ number_format($t['revenue'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-400">Raw Material Cost</span><span class="text-red-300">− {{ number_format($t['raw_material_cost'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-400">Packing Cost</span><span class="text-red-300">− {{ number_format($t['packing_cost'], 2) }}</span></div>
                        <div class="flex justify-between pt-2 border-t border-slate-700 font-bold text-base">
                            <span class="text-white">Net Profit</span>
                            <span class="{{ $t['profit'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ number_format($t['profit'], 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Reconciliation --}}
                @if($t['reconciliation'])
                @php $rec = $t['reconciliation']; @endphp
                <div class="px-5 py-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Reconciliation</p>
                    @if($rec['has_mismatch'])
                    <div class="p-4 bg-red-950/50 rounded-xl border border-red-500/30 mb-3">
                        <p class="text-red-400 font-bold text-sm mb-2">⚠ Mismatch Detected — Investigation Required</p>
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div><span class="text-slate-500">Expected</span><p class="font-bold text-white">{{ number_format($rec['expected_quantity'], 4) }}</p></div>
                            <div><span class="text-slate-500">Actual</span><p class="font-bold text-amber-300">{{ number_format($rec['actual_quantity'], 4) }}</p></div>
                            <div><span class="text-slate-500">Variance</span><p class="font-bold text-red-400">{{ number_format($rec['variance'], 4) }}</p></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Do not correct silently. Investigate and document the cause.</p>
                    </div>
                    @else
                    <div class="p-3 bg-emerald-950/30 rounded-xl border border-emerald-500/20">
                        <p class="text-emerald-400 text-sm font-semibold">✓ Quantities reconciled — no mismatch.</p>
                    </div>
                    @endif
                    <p class="text-xs text-slate-600 mt-2">Status: {{ ucfirst($rec['status']) }} · Last checked: {{ $rec['reconciled_at'] ?? '—' }}</p>
                    @if($rec['investigation_notes'])
                    <p class="text-xs text-amber-300 mt-1">{{ $rec['investigation_notes'] }}</p>
                    @endif
                </div>
                @endif

            </div>
            @else
            <div class="flex flex-col items-center justify-center py-20 px-6 text-center">
                <svg class="w-12 h-12 text-slate-700 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-slate-500 font-medium">Select a batch to view full traceability.</p>
                <p class="text-slate-700 text-sm mt-1">Click the eye icon on any batch in the list.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Comparison Modal --}}
    @if($showCompareModal && count($compareResults))
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 backdrop-blur-sm p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800">
                <h3 class="text-base font-bold text-white">Batch Comparison</h3>
                <button wire:click="$set('showCompareModal', false)" class="text-slate-500 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="overflow-auto p-5">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-700">
                            <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase">Metric</th>
                            @foreach($compareResults as $b)
                            <th class="text-right py-3 px-4 text-xs font-semibold text-white">{{ $b['batch_number'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach([
                            'product'          => 'Product',
                            'raw_material_kg'  => 'Raw Material (kg)',
                            'oil_litres'       => 'Oil Produced (L)',
                            'yield_pct'        => 'Yield %',
                            'raw_material_cost'=> 'Raw Material Cost',
                            'packing_cost'     => 'Packing Cost',
                            'total_cost'       => 'Total Cost',
                            'revenue'          => 'Revenue',
                            'profit'           => 'Profit',
                            'profit_margin_pct'=> 'Profit Margin %',
                            'sell_through_pct' => 'Sell-Through %',
                            'qc_status'        => 'QC Status',
                        ] as $key => $label)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3 px-4 text-slate-400 font-medium">{{ $label }}</td>
                            @foreach($compareResults as $b)
                            <td class="py-3 px-4 text-right">
                                @php $val = $b[$key] ?? '—'; @endphp
                                @if(in_array($key, ['raw_material_cost','packing_cost','total_cost','revenue','profit']))
                                    <span class="{{ $key === 'profit' && $val >= 0 ? 'text-emerald-400 font-bold' : ($key === 'profit' ? 'text-red-400 font-bold' : 'text-white') }}">
                                        {{ is_numeric($val) ? number_format($val, 2) : $val }}
                                    </span>
                                @elseif(in_array($key, ['yield_pct','profit_margin_pct','sell_through_pct']))
                                    <span class="text-indigo-300">{{ is_numeric($val) ? number_format($val, 2).'%' : $val }}</span>
                                @elseif(in_array($key, ['raw_material_kg','oil_litres']))
                                    <span class="text-white">{{ is_numeric($val) ? number_format($val, 2) : $val }}</span>
                                @elseif($key === 'qc_status')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $val === 'approved' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' }}">{{ $val }}</span>
                                @else
                                    <span class="text-slate-300">{{ $val }}</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Reconciliation Action Modal --}}
    @if($showReconModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md mx-4 shadow-2xl">
            <div class="px-6 py-5 border-b border-slate-800">
                <h3 class="text-base font-bold text-white">{{ $reconAction === 'investigate' ? 'Start Investigation' : 'Resolve Mismatch' }}</h3>
            </div>
            <div class="px-6 py-5">
                <label class="block text-xs font-semibold text-slate-400 mb-2">
                    {{ $reconAction === 'investigate' ? 'Investigation Notes' : 'Resolution Notes' }}
                    <span class="text-red-400">*</span>
                </label>
                <textarea wire:model="reconNotes" rows="4"
                    placeholder="{{ $reconAction === 'investigate' ? 'Describe what is being investigated and why the mismatch occurred...' : 'Describe how the mismatch was resolved and what caused it...' }}"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 outline-none resize-none"></textarea>
                @error('reconNotes') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="px-6 py-4 border-t border-slate-800 flex justify-end gap-3">
                <button wire:click="$set('showReconModal', false)" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</button>
                <button wire:click="submitReconAction"
                    class="px-5 py-2 rounded-xl text-sm font-bold transition {{ $reconAction === 'investigate' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white">
                    {{ $reconAction === 'investigate' ? 'Mark for Investigation' : 'Mark Resolved' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Distribution Modal --}}
    @if($showDistModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-lg mx-4 shadow-2xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800">
                <h3 class="text-base font-bold text-white">Distribute Finished Goods</h3>
                <button wire:click="$set('showDistModal', false)" class="text-slate-500 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Source Location <span class="text-red-400">*</span></label>
                    <select wire:model="distLocationId" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        <option value="">Select source warehouse...</option>
                        @foreach($locations as $l)<option value="{{ $l['id'] }}">{{ $l['name'] }} ({{ $l['type'] }})</option>@endforeach
                    </select>
                    @error('distLocationId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Destination Branch <span class="text-red-400">*</span></label>
                    <input wire:model="distDestBranchId" type="number" placeholder="Branch ID"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Destination Location <span class="text-red-400">*</span></label>
                    <select wire:model="distDestLocationId" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
                        <option value="">Select destination...</option>
                        @foreach($locations as $l)<option value="{{ $l['id'] }}">{{ $l['name'] }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Quantity <span class="text-red-400">*</span></label>
                    <input wire:model="distQuantity" type="number" step="0.0001" min="0.0001"
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-indigo-500">
                    @error('distQuantity') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Notes</label>
                    <textarea wire:model="distNotes" rows="2" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-indigo-500 resize-none"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-800 flex justify-end gap-3">
                <button wire:click="$set('showDistModal', false)" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">Cancel</button>
                <button wire:click="saveDistribution" class="px-5 py-2 rounded-xl text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white transition">Record Distribution</button>
            </div>
        </div>
    </div>
    @endif

</div>
