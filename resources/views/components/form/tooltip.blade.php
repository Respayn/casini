<div class="relative inline-block cursor-pointer">
    <x-icons.tooltip/>
    <div class="tooltip">
        {{ $slot }}
    </div>
</div>

<style>
    /* TODO: SASS, apply */
    .tooltip {
        position: absolute;
        left: 100%;
        bottom: 100%;
        margin-top: 0.25rem;
        width: 260px;
        background-color: #4B5563;
        color: white;
        font-size: 14px;
        font-weight: 400;
        font-style: italic;
        border-radius: 0.375rem;
        padding: 0.25rem 0.5rem;
        opacity: 0;
        transition: opacity 0.2s ease-in-out;
    }

    .relative:hover .tooltip {
        opacity: 1;
    }
</style>
