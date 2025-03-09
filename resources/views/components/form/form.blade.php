@props([
    'is-normalized' => false
])
<form class="form {{ ($isNormalized ?? false) ? 'form_normalized' : '' }}"
    {{ $attributes->class(["text-primary-text flex flex-col gap-5"]) }}
>
    {{ $slot }}
</form>
