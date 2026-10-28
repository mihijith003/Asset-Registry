{{-- "Disposals Report" print summary (route: asset-disposals.print-summary) - same filters as the Asset Disposal page --}}
@extends('layouts.print')

@section('title', 'Disposals Report')
@section('back', route('asset-disposals.index', request()->query()))
@section('orientation', 'landscape')
@section('doc-title', 'Disposals Report')
@section('doc-ref', $period)

@section('content')
    @include('partials.print-header', ['title' => 'Disposals Report', 'filters' => ['Period' => $period, 'Approval Status' => $statusLabel]])

    <table class="data">
        <thead>
            <tr>
                <th>Date</th>
                <th>Ref</th>
                <th>Asset Code</th>
                <th>Type / Sub Type</th>
                <th class="num">Original Cost</th>
                <th class="num">Net Book Value at Disposal Date</th>
                <th>Method</th>
                <th>Approval Status</th>
                <th>Authorised By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->disposal->disposal_date ?? '-' }}</td>
                    <td class="mono">{{ $row->disposal->reference }}</td>
                    <td class="mono">{{ $row->asset->asset_code ?? 'Asset #' . $row->disposal->asset_id }}</td>
                    <td>{{ $row->asset?->assetType->type_name ?? '-' }} / {{ trim($row->asset?->subType->sub_type_name ?? '-') }}</td>
                    <td class="num">{{ $row->asset ? money($row->asset->cost) : '-' }}</td>
                    <td class="num">{{ $row->book_value !== null ? money($row->book_value) : '-' }}</td>
                    <td>{{ $row->disposal->method }}</td>
                    <td>{{ \App\Http\Controllers\AssetDisposalController::APPROVAL_STATUSES[$row->disposal->approval_status] ?? ucfirst($row->disposal->approval_status) }}</td>
                    <td>{{ $row->disposal->authorized_by ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No disposals match the selected filters.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Net book value written off (approved disposals)</td>
                <td class="num">{{ money($totals->written_off) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary-totals">
        <span>Disposals: <strong>{{ $rows->count() - $totals->rejected_count }}</strong></span>
        @foreach ($totals->by_method as $method => $count)
            <span>{{ $method }}: <strong>{{ $count }}</strong></span>
        @endforeach
        @if ($totals->rejected_count)
            <span>Rejected claims (not disposals, not counted above): <strong>{{ $totals->rejected_count }}</strong></span>
        @endif
        @if ($totals->pending_count)
            <span>Pending approval (not yet written off): <strong>{{ $totals->pending_count }}</strong>,
                net book value <strong>{{ money($totals->pending_book_value) }}</strong></span>
        @endif
    </div>
@endsection
