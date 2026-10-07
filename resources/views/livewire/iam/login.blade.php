<div class="max-w-md mx-auto my-12 bg-white rounded-lg shadow-md p-6 border-t-4 border-indigo-600">
    <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Platform Login</h2>

    <form wire:submit.prevent="login" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <input type="email" wire:model="email" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('email') border-red-500 @enderror" placeholder="admin@albareka.com">
            @error('email') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" wire:model="password" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('password') border-red-500 @enderror">
            @error('password') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center text-sm text-gray-600">
                <input type="checkbox" wire:model="remember" class="rounded text-indigo-600 mr-2"> Remember me
            </label>
        </div>

        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-md transition shadow">
            Sign In
        </button>
    </form>
</div>
