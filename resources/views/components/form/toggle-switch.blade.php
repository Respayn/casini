<label class="inline-flex cursor-pointer items-center">
    <input
        class="peer sr-only"
        type="checkbox"
        {{ $attributes }}
    >
    <div
        class="bg-toggle-switch-bg after:bg-toggle-switch-handle-bg peer-checked:after:bg-primary relative h-6 w-11 rounded-full after:absolute after:start-0 after:top-0 after:h-6 after:w-6 after:rounded-full after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-focus:outline-none">
    </div>
</label>
