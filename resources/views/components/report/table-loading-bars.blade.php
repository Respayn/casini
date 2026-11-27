{{-- Полоска внутри настоящей ячейки. Отдельная таблица с равными колонками
     не совпадает с шапкой, когда отчёт шире экрана. --}}
<style>
    /* Шапка группы (название + стрелка) без полоски: иначе одна длинная «ёлка» на всю ширину. */
    .report-table-loading tbody td:not(:has(table)):not(.report-group-header-cell) {
        position: relative;
        color: transparent !important;
    }

    .report-table-loading tbody td:not(:has(table)):not(.report-group-header-cell) > * {
        visibility: hidden;
    }

    .report-table-loading tbody td:not(:has(table)):not(.report-group-header-cell)::after {
        content: "";
        position: absolute;
        left: 10px;
        right: 10px;
        top: 50%;
        height: 14px;
        margin-top: -7px;
        border-radius: 0.25rem;
        background-color: #e5e7eb;
        animation: report-skel-pulse 1.5s ease-in-out infinite;
    }

    @keyframes report-skel-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.45; }
    }
</style>
