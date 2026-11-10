@props(['title' => null])

@php
    $pageTitle = trim((string) ($title ?? ''));
    $pageTitle = preg_replace('/\s*[—–]\s*/u', ' - ', $pageTitle) ?? $pageTitle;
    $pageTitle = preg_replace('/\s*-\s*Casini\s*$/iu', '', $pageTitle) ?? $pageTitle;
    $pageTitle = preg_replace('/^Casini\s*-\s*/iu', '', $pageTitle) ?? $pageTitle;
    $pageTitle = preg_replace('/\s*-\s*Касини\s*$/u', '', $pageTitle) ?? $pageTitle;
    $pageTitle = preg_replace('/^Касини\s*-\s*/u', '', $pageTitle) ?? $pageTitle;
    $pageTitle = trim($pageTitle);
    $documentTitle = $pageTitle !== '' ? $pageTitle.' - Касини' : 'Касини';
@endphp

<meta name="robots" content="noindex, nofollow">
<title>{{ $documentTitle }}</title>
