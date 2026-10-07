<div class="bg-white/95 backdrop-blur-lg p-8 rounded-3xl shadow-2xl border border-white/20">
    <div class="text-center mb-6">
        <!-- Logo with inline styles to ensure it works -->
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 4rem; height: 4rem; border-radius: 1rem; background: linear-gradient(to bottom right, #f59e0b, #ea580c, #dc2626); box-shadow: 0 25px 50px -12px rgba(234, 88, 12, 0.5); margin-bottom: 1rem; position: relative;">
            <span style="color: white; font-weight: 900; font-size: 1.5rem;">A</span>
            <div style="position: absolute; inset: 0; border-radius: 1rem; background: rgba(255,255,255,0.2); animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;"></div>
        </div>
        
        <!-- Title with inline gradient -->
        <h1 style="font-size: 1.875rem; font-weight: 900; background: linear-gradient(to right, #d97706, #ea580c, #dc2626); -webkit-background-clip: text; background-clip: text; color: transparent; letter-spacing: -0.025em;">Al-Baraka</h1>
        <p style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-top: 0.25rem;">Business Management System</p>
    </div>

    <form wire:submit="login" class="space-y-4">
        <!-- Email -->
        <div>
            <label for="email" style="display: block; font-size: 0.75rem; font-weight: 700; color: #374151; margin-bottom: 0.375rem; text-transform: uppercase; letter-spacing: 0.05em;">Email</label>
            <div style="position: relative;">
                <div style="position: absolute; inset-y: 0; left: 0; padding-left: 0.75rem; display: flex; align-items: center; pointer-events: none;">
                    <svg style="height: 1.25rem; width: 1.25rem; color: #9ca3af;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                </div>
                <input wire:model="email" id="email" type="email" required autocomplete="email" placeholder="admin@example.com"
                    style="appearance: none; display: block; width: 100%; padding-left: 2.75rem; padding-right: 0.75rem; padding-top: 0.75rem; padding-bottom: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); font-size: 0.875rem; font-weight: 500; transition: all 0.2s;"
                    onfocus="this.style.outline='none'; this.style.borderColor='transparent'; this.style.boxShadow='0 0 0 2px #f97316';"
                    onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 2px 0 rgba(0, 0, 0, 0.05)';">
            </div>
        </div>

        <!-- Password -->
        <div>
            <label for="password" style="display: block; font-size: 0.75rem; font-weight: 700; color: #374151; margin-bottom: 0.375rem; text-transform: uppercase; letter-spacing: 0.05em;">Password</label>
            <div style="position: relative;">
                <div style="position: absolute; inset-y: 0; left: 0; padding-left: 0.75rem; display: flex; align-items: center; pointer-events: none;">
                    <svg style="height: 1.25rem; width: 1.25rem; color: #9ca3af;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input wire:model="password" id="password" type="password" required autocomplete="current-password" placeholder="••••••••"
                    style="appearance: none; display: block; width: 100%; padding-left: 2.75rem; padding-right: 0.75rem; padding-top: 0.75rem; padding-bottom: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); font-size: 0.875rem; font-weight: 500; transition: all 0.2s;"
                    onfocus="this.style.outline='none'; this.style.borderColor='transparent'; this.style.boxShadow='0 0 0 2px #f97316';"
                    onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 2px 0 rgba(0, 0, 0, 0.05)';">
            </div>
        </div>

        <!-- Error -->
        @if($emailError)
            <div style="padding: 0.75rem; background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 0.375rem; display: flex; align-items: flex-start; gap: 0.5rem;">
                <svg style="width: 1rem; height: 1rem; color: #dc2626; flex-shrink: 0; margin-top: 0.125rem;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p style="font-size: 0.75rem; color: #b91c1c; font-weight: 600;">{{ $emailError }}</p>
            </div>
        @endif

        <!-- Remember & Forgot -->
        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.875rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input wire:model="remember" id="remember" type="checkbox" style="height: 1rem; width: 1rem; accent-color: #ea580c; border-color: #d1d5db; border-radius: 0.25rem; cursor: pointer;">
                <span style="font-size: 0.75rem; font-weight: 600; color: #374151;">Remember me</span>
            </label>
            <a href="{{ route('password.request') }}" style="font-size: 0.75rem; font-weight: 700; color: #ea580c; text-decoration: none;" onmouseover="this.style.color='#c2410c'; this.style.textDecoration='underline';" onmouseout="this.style.color='#ea580c'; this.style.textDecoration='none';">
                Forgot?
            </a>
        </div>

        <!-- Buttons -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
            <!-- Website Button -->
            <a href="{{ route('home') }}" style="display: flex; align-items: center; justify-content: center; gap: 0.375rem; padding: 0.75rem; border: 2px solid #fed7aa; background: #ffedd5; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 700; color: #c2410c; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#fed7aa';" onmouseout="this.style.background='#ffedd5';">
                <svg style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                <span>Website</span>
            </a>

            <!-- Login Button -->
            <button type="submit" wire:loading.attr="disabled"
                style="display: flex; align-items: center; justify-content: center; gap: 0.375rem; padding: 0.75rem; border: none; border-radius: 0.75rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); font-size: 0.75rem; font-weight: 900; color: white; background: linear-gradient(to right, #f59e0b, #ea580c, #dc2626); cursor: pointer; transition: all 0.2s;"
                onmouseover="this.style.background='linear-gradient(to right, #d97706, #c2410c, #b91c1c)'; this.style.transform='scale(1.02)';"
                onmouseout="this.style.background='linear-gradient(to right, #f59e0b, #ea580c, #dc2626)'; this.style.transform='scale(1)';"
                onmousedown="this.style.transform='scale(0.98)';"
                onmouseup="this.style.transform='scale(1.02)';">
                <span wire:loading.remove style="display: flex; align-items: center; gap: 0.375rem;">
                    <svg style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    <span>LOGIN</span>
                </span>
                <span wire:loading style="display: flex; align-items: center; gap: 0.375rem;">
                    <svg style="animation: spin 1s linear infinite; height: 1rem; width: 1rem;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Loading…</span>
                </span>
            </button>
        </div>
    </form>
</div>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
