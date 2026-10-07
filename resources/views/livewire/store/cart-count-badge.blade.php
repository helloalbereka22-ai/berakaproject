<span>
    @if($count > 0)
    <span class="absolute -top-1 -right-1 min-w-[1.1rem] h-[1.1rem] px-0.5 flex items-center justify-center bg-indigo-600 text-white text-[10px] font-bold rounded-full leading-none">
        {{ $count > 99 ? '99+' : $count }}
    </span>
    @endif
</span>
