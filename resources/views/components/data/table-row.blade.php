@props([
    'bgColor' => null,
])

<tr
    {{ $attributes }}
    @style(['background-color: '.$bgColor => filled($bgColor)])
>
    {{ $slot }}
</tr>
