<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed — Al-Baraka</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-50 via-white to-pink-50 min-h-screen flex flex-col items-center justify-center p-4">

    {{-- Logo --}}
    <div class="mb-8 flex flex-col items-center">
        @php
            $logoPath = \App\Services\SettingsService::class;
            $logoPath = app(\App\Services\SettingsService::class)->get('system_logo_path', '');
        @endphp
        @if($logoPath)
            <img src="{{ asset('storage/' . $logoPath) }}" alt="Al-Baraka Logo"
                 class="h-16 w-16 object-contain rounded-2xl shadow-lg mb-3">
        @else
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg mb-3">
                <span class="text-white font-black text-2xl">A</span>
            </div>
        @endif
        <p class="text-lg font-black text-gray-900 leading-tight">Al-Baraka</p>
        <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest">Business Group</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-3xl shadow-xl p-10 text-center max-w-md w-full border border-gray-100">

        {{-- Success Icon --}}
        <div class="w-24 h-24 bg-gradient-to-br from-green-100 to-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
            <svg class="w-12 h-12 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="text-3xl font-black text-gray-900 mb-2">Order Placed!</h1>
        <p class="text-gray-500 text-base mb-6">Thank you! We received your order successfully.</p>

        {{-- Order Number --}}
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200 rounded-2xl px-8 py-5 mb-6">
            <p class="text-xs text-purple-500 font-bold uppercase tracking-widest mb-1">Your Order Number</p>
            <p class="text-2xl font-black text-purple-700 tracking-wide">{{ $orderNumber }}</p>
        </div>

        <p class="text-gray-500 text-sm mb-8">
            We will contact you on your phone to confirm delivery.<br>
            Please keep your phone available.
        </p>

        {{-- Buttons --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('store.products') }}"
               class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-black rounded-xl hover:from-purple-700 hover:to-pink-700 transition shadow-lg text-sm">
                🛍️ Continue Shopping
            </a>
            <a href="{{ route('home') }}"
               class="px-8 py-3 border-2 border-gray-200 text-gray-600 font-bold rounded-xl hover:border-purple-300 hover:text-purple-700 transition text-sm">
                🏠 Go Home
            </a>
        </div>
    </div>

    <p class="text-gray-400 text-xs mt-6">© {{ date('Y') }} Al-Baraka Business Group</p>

</body>
</html>
