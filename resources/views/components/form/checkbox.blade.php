@props([
    'checked' => false
])

@php
    $isDisabled = $attributes->has('disabled');
@endphp

<div @class(['checkbox', 'disabled' => $isDisabled])>
    <input
        class="checkbox-input"
        type="checkbox"
        @checked($checked)
        {{ $attributes }}
    >
    <div class="checkbox-box">
        <svg
            class="checkbox-icon"
            width="15"
            height="11"
            viewBox="0 0 15 11"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M4.63054 8.88958L1.52383 5.85408C1.35643 5.69051 1.12938 5.59862 0.892639 5.59862C0.655896 5.59862 0.42885 5.69051 0.261448 5.85408C0.0940456 6.01764 0 6.23949 0 6.4708C0 6.58534 0.023089 6.69875 0.0679483 6.80457C0.112808 6.91039 0.178559 7.00654 0.261448 7.08752L4.00383 10.7441C4.353 11.0853 4.91704 11.0853 5.26621 10.7441L14.7386 1.4889C14.906 1.32534 15 1.10349 15 0.872179C15 0.640862 14.906 0.419021 14.7386 0.255455C14.5712 0.0918902 14.3441 0 14.1074 0C13.8706 0 13.6436 0.0918902 13.4762 0.255455L4.63054 8.88958Z"
                fill="white"
            />
        </svg>
    </div>
</div>
