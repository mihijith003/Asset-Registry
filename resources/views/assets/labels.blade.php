{{--
    Printable QR label sheet (route: assets.labels) - same filters as the registry. Standalone page rather than
    layouts.print: that layout adds a document header / footer, sheet padding and its own @page margin, which would
    throw the sticker grid off.

    The server outputs a flat list of slots (blanks for ?start=, then one label per asset); resources/js/labels.js
    groups them into A4 pages of --label-cols x --label-rows, renders every QR code (bundled, no CDN) and only then
    enables Print.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asset Labels</title>
    <style>
        /* ===== Sticker stock: the ONE place to change the grid. A4 = 210 x 297mm; @page margin below is 8mm. ===== */
        :root {
            --label-cols: 3;
            --label-rows: 8;           /* labels per page = cols x rows (keep AssetController::LABELS_PER_PAGE equal) */
            --label-w: 63mm;
            --label-h: 34mm;
            --label-gap-x: 2.5mm;      /* 3 x 63 + 2 x 2.5 = 194mm = 210 - 2 x 8 */
            --label-gap-y: 0mm;        /* 8 x 34 = 272mm, within 297 - 2 x 8 = 281 */
            --qr-size: 25mm;
            --label-pad: 2mm;
        }
        @page { size: A4 portrait; margin: 8mm; }
        /* ============================================================================================================ */

        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; color: #111827; font-family: "Segoe UI", Arial, sans-serif; }

        .toolbar { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; max-width: 210mm; margin: 1rem auto 0; padding: 0 1rem; font-size: 14px; }
        .toolbar .count { flex: 1; color: #374151; }
        .toolbar .warn { flex-basis: 100%; background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; border-radius: 6px; padding: 0.5rem 0.75rem; }
        .toolbar a, .toolbar button { font: inherit; font-weight: 600; padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none; cursor: pointer; }
        .toolbar button { background: #4338ca; border-color: #4338ca; color: #fff; }
        .toolbar button:disabled { opacity: 0.55; cursor: wait; }

        /* Screen preview: each page as an A4 sheet with the 8mm margin drawn as padding. */
        .page { width: 210mm; height: 297mm; padding: 8mm; margin: 0.75rem auto 1.5rem; background: #fff; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15); }
        .grid {
            display: grid;
            grid-template-columns: repeat(var(--label-cols), var(--label-w));
            grid-auto-rows: var(--label-h);
            column-gap: var(--label-gap-x);
            row-gap: var(--label-gap-y);
        }
        #slots { display: none; }   /* the flat list before labels.js lays it out into pages */

        .label {
            display: flex; align-items: center; gap: 2mm; overflow: hidden;
            width: var(--label-w); height: var(--label-h); padding: var(--label-pad);
            border: 0.25mm dashed #b8bec8;   /* cutting guide */
            page-break-inside: avoid; break-inside: avoid;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .label.blank { border-color: transparent; background: repeating-linear-gradient(45deg, #f9fafb, #f9fafb 2mm, #f3f4f6 2mm, #f3f4f6 4mm); }
        .label .qr { flex: 0 0 var(--qr-size); width: var(--qr-size); height: var(--qr-size); }
        .label .qr svg { display: block; width: 100%; height: 100%; }
        .label .text { min-width: 0; flex: 1; line-height: 1.2; }
        .label .code { font-family: Consolas, "Courier New", monospace; font-weight: 700; font-size: 11pt; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .label .model { font-size: 7.5pt; margin-top: 1mm; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; }
        .label .kind { font-size: 6.5pt; color: #4b5563; margin-top: 0.8mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { width: auto; height: auto; padding: 0; margin: 0; box-shadow: none; break-after: page; page-break-after: always; }
            .page:last-child { break-after: auto; page-break-after: auto; }
            .label.blank { background: none; }
            /* Ctrl+P before every QR has rendered prints this notice, never a sheet of blank squares. */
            body:not(.qr-ready) #sheet { display: none; }
            body:not(.qr-ready) .not-ready { display: block; }
        }
        .not-ready { display: none; font-size: 14pt; padding: 2rem; }
    </style>
    @vite('resources/js/labels.js')
</head>
<body>
    <div class="toolbar">
        <a href="{{ $backUrl }}">&larr; Back</a>
        <span class="count">
            {{ $assets->count() }} {{ Str::plural('label', $assets->count()) }}@if ($single && $assets->isNotEmpty()) for {{ $assets->first()->asset_code }}@endif<span data-page-count></span>
            @if ($single && $assets->isEmpty())
                &middot; that asset is no longer in the register (sold, disposed or superseded) or outside the current filter
            @endif
            @if ($blanks) &middot; starting at position {{ $blanks + 1 }} @endif
        </span>
        <button type="button" data-print disabled>Rendering QR codes&hellip;</button>
        @if (in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1'], true))
            <div class="warn">
                The QR codes point at <strong>{{ request()->getSchemeAndHttpHost() }}</strong>, which a phone cannot reach.
                Open this page using the server's network address (e.g. http://192.168.x.x/...) before printing.
            </div>
        @endif
        <div class="warn" data-grid-warning hidden></div>
        <noscript><div class="warn">JavaScript is needed to draw the QR codes.</div></noscript>
    </div>

    <div class="not-ready">The QR codes have not finished rendering. Close this print dialog, wait for the Print button, then print again.</div>
    <div id="sheet" data-per-page="{{ $perPage }}"></div>

    <div id="slots">
        @for ($i = 0; $i < $blanks; $i++)
            <div class="label blank"></div>
        @endfor
        @foreach ($assets as $asset)
            <div class="label">
                <div class="qr" data-qr="{{ route('assets.timeline', $asset) }}"></div>
                <div class="text">
                    <div class="code">{{ $asset->asset_code }}</div>
                    <div class="model">{{ trim(($asset->brand->name ?? $asset->brand_name ?? '') . ' ' . ($asset->model ?? '')) ?: '-' }}</div>
                    <div class="kind">{{ $asset->assetType->type_name ?? '-' }} / {{ trim($asset->subType->sub_type_name ?? '-') }}</div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
