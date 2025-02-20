@props([
    'required' => false,
])

<label {{ $attributes->class(['text-sm', "after:content-['*']" => $required]) }}>
    {{ $slot }}
</label>
