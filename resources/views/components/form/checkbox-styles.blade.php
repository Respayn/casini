{{-- Стили чекбокса вне Alpine <template>, иначе @once внутри x-if/x-for не попадает в документ --}}
<style id="casini-checkbox-styles">
    .checkbox:not(.disabled):not(:has(.checkbox-input:disabled)):has(.checkbox-input:hover) .checkbox-box {
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

    .checkbox:has(input[type="checkbox"]:checked):not(.disabled):not(:has(.checkbox-input:disabled)):has(.checkbox-input:hover) .checkbox-box {
        background: #4070E0;
        border-color: #4070E0;
    }

    .checkbox:has(input[type="checkbox"]:checked):not(.disabled):not(:has(.checkbox-input:disabled)):has(.checkbox-input:hover) .checkbox-icon {
        color: #ffffff;
    }

    .checkbox.disabled,
    .checkbox:has(.checkbox-input:disabled),
    .checkbox.disabled .checkbox-input,
    .checkbox:has(.checkbox-input:disabled) .checkbox-input {
        cursor: not-allowed;
    }

    .checkbox.disabled .checkbox-box,
    .checkbox:has(.checkbox-input:disabled) .checkbox-box {
        background: #f1f5f9;
        border-color: #e2e8f0;
        box-shadow: none;
    }

    .checkbox.disabled:has(input[type="checkbox"]:checked) .checkbox-box,
    .checkbox:has(.checkbox-input:disabled):has(input[type="checkbox"]:checked) .checkbox-box {
        background: #cbd5e1;
        border-color: #cbd5e1;
    }

    .checkbox.disabled .checkbox-icon,
    .checkbox:has(.checkbox-input:disabled) .checkbox-icon {
        color: #94a3b8;
    }

    .checkbox.disabled:has(input[type="checkbox"]:checked) .checkbox-icon,
    .checkbox:has(.checkbox-input:disabled):has(input[type="checkbox"]:checked) .checkbox-icon {
        color: #ffffff;
    }
</style>
