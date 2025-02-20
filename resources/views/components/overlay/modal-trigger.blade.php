@props(['name'])

<div
    class="contents"
    x-data
    x-on:click="$dispatch('modal-show', { name: '{{ $name }}' })"
    {{ $attributes }}
>
    {{ $slot }}
</div>
