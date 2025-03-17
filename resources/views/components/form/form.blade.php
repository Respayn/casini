@props([
    'isNormalized' => false
])

<form {{ $attributes->merge(['class' => 'form ' . ($isNormalized ? 'form_normalized' : '') . ' text-primary-text flex flex-col gap-5']) }}>
    {{ $slot }}
</form>
