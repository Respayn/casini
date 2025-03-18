@props([
    'isNormalized' => false
])

<form {{ $attributes->class([
        'form',
        'form_normalized' => $isNormalized,
        'text-primary-text',
        'flex',
        'flex-col',
        'gap-5'
    ]) }}>
    {{ $slot }}
</form>
