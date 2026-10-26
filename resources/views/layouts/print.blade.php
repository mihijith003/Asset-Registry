{{--
    NEW: standalone layout for printable documents (asset sale voucher, asset disposal record).
    No sidebar and no Vite assets: the styles are inline so the page prints identically anywhere.
    Screen: a grey backdrop with an A4 "sheet" and a toolbar. Print: only the sheet, toolbar hidden.
    Usage: @extends('layouts.print') with @section('title'), @section('back') and @section('content').
    Wide list/summary pages: add @section('orientation', 'landscape') and use <table class="data"> - the page then
    prints on A4 landscape, and the table header row repeats at the top of every printed page.
--}}
@php($landscape = trim($__env->yieldContent('orientation')) === 'landscape')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>
        :root { --ink: #111827; --muted: #6b7280; --line: #d1d5db; --soft: #f3f4f6; --accent: #4338ca; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; color: var(--ink); font-family: "Segoe UI", Arial, sans-serif; font-size: 14px; line-height: 1.5; }

        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; max-width: 210mm; margin: 1rem auto 0; padding: 0 1rem; }
        .toolbar .hint { font-size: 0.8rem; color: var(--muted); }
        .toolbar a, .toolbar button { font: inherit; font-weight: 600; padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid var(--line); background: #fff; color: var(--ink); text-decoration: none; cursor: pointer; }
        .toolbar button { background: var(--accent); border-color: var(--accent); color: #fff; }

        .sheet { width: 210mm; min-height: 297mm; margin: 0.75rem auto 2rem; padding: 16mm; background: #fff; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15); }

        .doc-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; padding-bottom: 1rem; border-bottom: 3px solid var(--ink); }
        .org { font-size: 1.1rem; font-weight: 700; letter-spacing: 0.02em; }
        .org small { display: block; font-weight: 400; color: var(--muted); font-size: 0.8rem; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 1.5rem; text-transform: uppercase; letter-spacing: 0.06em; }
        .doc-title .ref { color: var(--muted); font-size: 0.9rem; }

        h2 { margin: 1.75rem 0 0.5rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); }

        table.details { width: 100%; border-collapse: collapse; }
        table.details th, table.details td { text-align: left; padding: 0.5rem 0.75rem; border: 1px solid var(--line); vertical-align: top; }
        table.details th { width: 28%; background: var(--soft); font-weight: 600; }
        .mono { font-family: Consolas, "Courier New", monospace; font-weight: 600; }
        .num { text-align: right !important; font-variant-numeric: tabular-nums; }

        .total-row th, .total-row td { background: var(--soft); font-weight: 700; font-size: 1.05rem; }
        .note { margin-top: 0.5rem; font-size: 0.8rem; color: var(--muted); }

        .signatures { display: flex; gap: 2.5rem; margin-top: 4rem; }
        .signature { flex: 1; text-align: center; }
        .signature .line { border-top: 1px solid var(--ink); }
        .signature .name { padding-top: 0.35rem; min-height: 1.9em; font-weight: 600; }
        .signature .sub { font-size: 0.8rem; color: var(--muted); }

        .doc-footer { margin-top: 3rem; padding-top: 0.75rem; border-top: 1px solid var(--line); display: flex; justify-content: space-between; gap: 1rem; font-size: 0.75rem; color: var(--muted); }

        /* Wide list tables (print summaries) */
        table.data { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        table.data th, table.data td { padding: 0.35rem 0.5rem; border: 1px solid var(--line); text-align: left; vertical-align: top; }
        table.data thead th { background: var(--soft); font-weight: 600; white-space: nowrap; }
        table.data tfoot td, table.data tfoot th { background: var(--soft); font-weight: 700; }
        .gain { color: #047857; }                /* positive change / gain on sale */
        .loss { color: #b91c1c; }                /* negative change / loss on sale */
        .tag { display: inline-block; padding: 0.05rem 0.4rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; border: 1px solid currentColor; }
        .summary-totals { margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem 2rem; font-size: 0.85rem; }
        .summary-totals strong { font-variant-numeric: tabular-nums; }
        thead { display: table-header-group; }   /* repeat the header row on every printed page */
        tfoot { display: table-footer-group; }

        body.landscape .toolbar { max-width: 297mm; }
        body.landscape .sheet { width: 297mm; min-height: 210mm; }

        @media (max-width: 800px) {
            .sheet { width: auto; min-height: 0; margin: 0.75rem 0.5rem 1.5rem; padding: 1rem; }
            .doc-header { flex-direction: column; }
            .doc-title { text-align: left; }
            .signatures { flex-direction: column; gap: 2.5rem; }
        }

        @page { size: A4; margin: 14mm; }
        @if ($landscape)
            @page { size: A4 landscape; margin: 10mm; }
        @endif
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            /* tighter spacing so a document always fits on one A4 page (the content just overflowed it) */
            body { font-size: 13px; }
            .doc-header { padding-bottom: 0.75rem; }
            h2 { margin: 1.1rem 0 0.4rem; }
            table.details th, table.details td { padding: 0.35rem 0.65rem; }
            .signatures { margin-top: 2.75rem; }
            .doc-footer { margin-top: 1.75rem; }
            table.details th, .total-row th, .total-row td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .signatures, table.details tr, table.data tr { break-inside: avoid; }
            table.data thead th, table.data tfoot td, table.data tfoot th, .gain, .loss, .tag { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="{{ $landscape ? 'landscape' : '' }}">
    <div class="toolbar">
        <a href="@yield('back')">&larr; Back</a>
        <span class="hint">To save as PDF, choose "Save as PDF" as the printer.</span>
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <main class="sheet">
        <header class="doc-header">
            <div class="org">
                Asset Manager
                <small>Inventory &amp; Lifecycle</small>
            </div>
            <div class="doc-title">
                <h1>@yield('doc-title')</h1>
                <div class="ref">@yield('doc-ref')</div>
            </div>
        </header>

        @yield('content')

        <footer class="doc-footer">
            <span>Printed by {{ Auth::user()->name ?? '-' }} on {{ now()->format('Y-m-d H:i') }}</span>
            <span>@yield('doc-ref')</span>
        </footer>
    </main>
</body>
</html>
