<div
    class="checkbox"
>
    <input
        class="checkbox-input"
        type="checkbox"
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

@once
    <style>
        .checkbox:not(.disabled):has(.checkbox-input:hover) .checkbox-box {
            border-color: #94a3b8;
        }

        .checkbox {
            position: relative;
            display: inline-flex;
            user-select: none;
            vertical-align: bottom;
            width: 1.25rem;
            height: 1.25rem;
        }

        .checkbox-input {
            cursor: pointer;
            appearance: none;
            position: absolute;
            inset-block-start: 0;
            inset-inline-start: 0;
            width: 100%;
            height: 100%;
            padding: 0;
            margin: 0;
            opacity: 0;
            z-index: 1;
            outline: 0 none;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .checkbox-box {
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            width: 1.25rem;
            height: 1.25rem;
            transition: background 0.2s, color 0.2s, border-color 0.2s, box-shadow 0.2s, outline-color 0.2s;
            outline-color: transparent;
            box-shadow: 0 0 #0000, 0 0 #0000, 0 1px 2px 0 rgba(18, 18, 23, 0.05);
        }

        .checkbox:has(input[type="checkbox"]:checked) .checkbox-box {
            border-color: #599CFF;
            background: #599CFF;
        }

        .checkbox-icon {
            transition-duration: 0.2s;
            color: #334155;
            font-size: 0.875rem;
            width: 0.875rem;
            height: 0.875rem;
            display: none;
        }

        .checkbox:has(input[type="checkbox"]:checked) .checkbox-icon {
            display: block;
            color: #ffffff;
        }

        .checkbox:has(input[type="checkbox"]:checked):not(.disabled):has(.checkbox-input:hover) .checkbox-box {
            background: #4070E0;
            border-color: #4070E0;
        }

        .checkbox:has(input[type="checkbox"]:checked):not(.disabled):has(.checkbox-input:hover) .checkbox-icon {
            color: #ffffff;
        }
    </style>
@endonce
