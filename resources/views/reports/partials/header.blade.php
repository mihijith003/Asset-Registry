{{--
    Shared report header, included by every report page AND its print view, so a printed report always says
    what it covers and who produced it, and when.

    Usage:  @include('reports.partials.header', ['title' => 'Fixed Asset Schedule', 'period' => 'As at 2026-10-26'])

    Styles are inline (not in app.css) because layouts.print does not load the Vite assets.
--}}
@once
    <style>
        .report-header { margin-bottom: 1.5rem; padding: 1rem 1.25rem; border: 1px solid #e2e8f0; border-left: 4px solid #4f46e5; border-radius: 0.5rem; background: #fff; }
        .report-header h2.report-title { margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b; text-transform: none; letter-spacing: normal; }
        .report-header .report-period { margin-top: 0.25rem; font-size: 0.95rem; font-weight: 600; color: #334155; }
        .report-header .report-generated { margin-top: 0.35rem; font-size: 0.8rem; color: #64748b; }
        @media print {
            .report-header { border: none; border-bottom: 1px solid #d1d5db; border-radius: 0; padding: 0 0 0.75rem; margin: 1rem 0; }
        }
    </style>
@endonce

<div class="report-header">
    <h2 class="report-title">{{ $title }}</h2>
    <div class="report-period">{{ $period }}</div>
    <div class="report-generated">
        Generated on {{ now()->format('Y-m-d H:i T') }} by {{ auth()->user()->name ?? 'Unknown user' }}
    </div>
</div>
