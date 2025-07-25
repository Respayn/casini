<label class="flex items-center gap-2 cursor-pointer select-none">
    <input
        type="checkbox"
        {{ $attributes->merge(['class' => 'sr-only peer']) }}
    >
    <span class="inline-flex items-center justify-center w-6 h-6 rounded-[3px] transition
        bg-gray-200 peer-checked:bg-[#56A2FF]">
        <svg
            class="w-5 h-5 text-white pointer-events-none peer-has-[:checked] peer-checked:opacity-0 transition"
            fill="none"
            stroke="currentColor"
            stroke-width="3"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
    </span>
    {{ $slot }}
</label>
