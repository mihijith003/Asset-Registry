{{--
    Detail sections below the movement summary, shared by the Period Report screen and its print view.
    $sections from ReportController::sections() (only the ticked ones); $print = true on the print view.
    Everything shown here was loaded in the controller - no queries in these loops.
--}}
@php
    $money = fn ($v) => $v === null ? '-' : money($v);
    $kind = fn ($asset) => $asset ? trim(($asset->assetType->type_name ?? '-') . ' / ' . trim($asset->subType->sub_type_name ?? '-')) : '-';
    $code = fn ($asset, $id = null) => $asset->asset_code ?? ($id ? 'Asset #' . $id : '-');
    $tableClass = $print ? 'data' : 'table';
    $num = 'style="text-align: right; font-variant-numeric: tabular-nums;"';
@endphp

@once
    <style>
        .period-section .gain { color: #047857; }
        .period-section .loss { color: #b91c1c; }
        .period-section .none { color: #64748b; font-style: italic; margin: 0.25rem 0 0; }
        .period-section .check-ok { font-size: 0.8rem; color: #64748b; margin-top: 0.4rem; }
        .period-section .check-fail { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 6px; padding: 0.6rem 0.9rem; margin: 0.5rem 0; font-size: 0.875rem; }
    </style>
@endonce

@foreach ($sections as $key => $section)
    @php
        $rows = $section['rows'];
        $t = $section['totals'];
        $title = \App\Http\Controllers\ReportController::SECTIONS[$key] . ' (' . $rows->count() . ')';
    @endphp

    @if ($print)
        <div class="period-section">
        <h2>{{ $title }}</h2>
    @else
        <div class="card period-section">
        <div class="card-header"><div><h3>{{ $title }}</h3></div></div>
        <div class="table-responsive" style="padding: 0 1.5rem 1.5rem;">
    @endif

    @if (isset($section['check']) && ! $section['check']->ok)
        <div class="check-fail">
            <strong>&#9888; Does not agree with the movement summary.</strong>
            This section totals {{ $money($section['check']->detail) }}, but the summary's {{ $section['check']->column }} column is
            {{ $money($section['check']->summary) }} (difference {{ $money($section['check']->difference) }}).
        </div>
    @endif

    @if ($rows->isEmpty())
        <p class="none">No {{ strtolower(\App\Http\Controllers\ReportController::SECTIONS[$key]) }} in this period.</p>
    @else
        @switch($key)
            @case('registrations')
                <table class="{{ $tableClass }}">
                    <thead><tr>
                        <th>Date</th><th>Asset Code</th><th>Type / Sub Type</th><th>Brand &amp; Model</th><th>Supplier</th>
                        <th>PO Ref</th><th>Invoice Ref</th><th {!! $num !!}>Cost</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td>{{ $r->date }}</td>
                                <td class="mono">{{ $r->asset->asset_code }}</td>
                                <td>{{ $kind($r->asset) }}</td>
                                <td>{{ trim(($r->asset->brand->name ?? $r->asset->brand_name ?? '') . ' ' . ($r->asset->model ?? '')) ?: '-' }}</td>
                                <td>{{ $r->asset->supplier ?: '-' }}</td>
                                <td>{{ $r->asset->po_reference ?: '-' }}</td>
                                <td>{{ $r->asset->invoice_reference ?: '-' }}</td>
                                <td {!! $num !!}>{{ $money($r->cost) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr style="font-weight: 700;"><td colspan="7">Total</td><td {!! $num !!}>{{ $money($t['cost']) }}</td></tr></tfoot>
                </table>
                @break

            @case('sales')
                <table class="{{ $tableClass }}">
                    <thead><tr>
                        <th>Date</th><th>Ref</th><th>Asset Code</th><th>Type / Sub Type</th><th {!! $num !!}>Cost</th>
                        <th {!! $num !!}>NBV at Sale</th><th {!! $num !!}>Proceeds</th><th {!! $num !!}>Gain / Loss</th>
                        <th>Buyer</th><th>Authorised By</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td>{{ $r->sale->sale_date }}</td>
                                <td class="mono">{{ $r->sale->reference }}</td>
                                <td class="mono">{{ $code($r->sale->asset, $r->sale->asset_id) }}</td>
                                <td>{{ $kind($r->sale->asset) }}</td>
                                <td {!! $num !!}>{{ $money($r->cost) }}</td>
                                <td {!! $num !!}>{{ $money($r->nbv) }}</td>
                                <td {!! $num !!}>{{ $money((float) $r->sale->price) }}</td>
                                <td {!! $num !!}>@include('partials.signed-amount', ['amount' => $r->gain])</td>
                                <td>{{ $r->sale->buyer_name ?: '-' }}</td>
                                <td>{{ $r->sale->authorized_by ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr style="font-weight: 700;">
                        <td colspan="4">Total</td>
                        <td {!! $num !!}>{{ $money($t['cost']) }}</td>
                        <td {!! $num !!}>{{ $money($t['nbv']) }}</td>
                        <td {!! $num !!}>{{ $money($t['proceeds']) }}</td>
                        <td {!! $num !!}>@include('partials.signed-amount', ['amount' => $t['gain']])</td>
                        <td colspan="2"></td>
                    </tr></tfoot>
                </table>
                @break

            @case('disposals')
                <table class="{{ $tableClass }}">
                    <thead><tr>
                        <th>Date</th><th>Ref</th><th>Asset Code</th><th>Type / Sub Type</th><th {!! $num !!}>Cost</th>
                        <th {!! $num !!}>NBV at Disposal</th><th>Method</th><th>Approval Status</th><th>Authorised By</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $r)
                            @php($status = $r->disposal->approval_status)
                            <tr @if ($status === 'rejected') style="color: #94a3b8;" @endif>
                                <td>{{ $r->disposal->disposal_date }}</td>
                                <td class="mono">{{ $r->disposal->reference }}</td>
                                <td class="mono">{{ $code($r->disposal->asset, $r->disposal->asset_id) }}</td>
                                <td>{{ $kind($r->disposal->asset) }}</td>
                                <td {!! $num !!}>{{ $money($r->cost) }}</td>
                                <td {!! $num !!}>{{ $money($r->nbv) }}</td>
                                <td>{{ $r->disposal->method ?: '-' }}</td>
                                <td>{{ \App\Http\Controllers\AssetDisposalController::APPROVAL_STATUSES[$status] ?? ucfirst((string) $status) }}</td>
                                <td>{{ $r->disposal->authorized_by ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @foreach ($t['byMethod'] as $method => $m)
                            <tr style="font-weight: 700;">
                                <td colspan="4">{{ $method }} ({{ $m->count }})</td>
                                <td {!! $num !!}>{{ $money($m->cost) }}</td>
                                <td {!! $num !!}>{{ $money($m->nbv) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        @endforeach
                        <tr style="font-weight: 700;">
                            <td colspan="5">Written off (approved only, as in the summary)</td>
                            <td {!! $num !!}>{{ $money($t['approvedNbv']) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
                <p class="check-ok">Method totals include pending claims (the asset is still held){{ $t['rejected'] ? '; ' . $t['rejected'] . ' rejected claim(s) are listed in grey and not totalled' : '' }}.</p>
                @break

            @case('modifications')
                <table class="{{ $tableClass }}">
                    <thead><tr>
                        <th>Date</th><th>Ref</th><th>Type</th><th>Asset Before</th><th>Component Added</th><th>Component Removed</th>
                        <th>Resulting Asset</th><th {!! $num !!}>Cost Before</th><th {!! $num !!}>Cost After</th><th {!! $num !!}>Change</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td>{{ $r->mod->modification_date }}</td>
                                <td class="mono">{{ $r->mod->reference }}</td>
                                <td>{{ $r->mod->modification_type ? ucfirst($r->mod->modification_type) : '-' }}</td>
                                <td class="mono">{{ $code($r->mod->asset, $r->mod->asset_id) }}</td>
                                <td class="mono">{{ $r->mod->componentAsset->asset_code ?? ($r->mod->added_asset ?: '-') }}</td>
                                <td class="mono">{{ $r->mod->removedAsset->asset_code ?? ($r->mod->removed_asset ?: '-') }}</td>
                                <td class="mono">{{ $r->mod->resultAsset->asset_code ?? '-' }}</td>
                                <td {!! $num !!}>{{ $money($r->before) }}</td>
                                <td {!! $num !!}>{{ $money($r->after) }}</td>
                                <td {!! $num !!}>@include('partials.signed-amount', ['amount' => $r->change])</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr style="font-weight: 700;">
                        <td colspan="9">{{ $t['upgrades'] }} upgrade(s), {{ $t['downgrades'] }} downgrade(s){{ $t['unknown'] ? ' (' . $t['unknown'] . ' without recorded costs)' : '' }} &mdash; net change</td>
                        <td {!! $num !!}>@include('partials.signed-amount', ['amount' => $t['change']])</td>
                    </tr></tfoot>
                </table>
                @if ($t['reverted'])
                    <p class="check-ok">{{ $t['reverted'] }} reverted modification(s) in this period are not listed (they are ignored by the summary too).</p>
                @endif
                @break

            @case('revaluations')
                <table class="{{ $tableClass }}">
                    <thead><tr>
                        <th>Date</th><th>Asset Code</th><th>Type / Sub Type</th><th {!! $num !!}>Previous Value</th>
                        <th {!! $num !!}>Revalued Amount</th><th {!! $num !!}>Change</th><th {!! $num !!}>New Life (months)</th>
                        <th {!! $num !!}>New Monthly Amount</th><th>Successor Asset</th><th>Authorised By</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td>{{ $r->reval->revaluation_date->toDateString() }}</td>
                                <td class="mono">{{ $code($r->reval->asset, $r->reval->asset_id) }}</td>
                                <td>{{ $kind($r->reval->asset) }}</td>
                                <td {!! $num !!}>{{ $money($r->reval->previous_value) }}</td>
                                <td {!! $num !!}>{{ $money($r->reval->revalued_amount) }}</td>
                                <td {!! $num !!}>@include('partials.signed-amount', ['amount' => $r->change])</td>
                                <td {!! $num !!}>{{ $r->reval->new_useful_life_months ?? '-' }}</td>
                                <td {!! $num !!}>{{ $r->monthly === null ? 'Not set' : $money($r->monthly) }}</td>
                                <td class="mono">{{ $r->reval->resultAsset->asset_code ?? 'None (in place)' }}</td>
                                <td>{{ $r->reval->authorized_by ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr style="font-weight: 700;">
                        <td colspan="3">Total</td>
                        <td {!! $num !!}>{{ $money($t['previous']) }}</td>
                        <td {!! $num !!}>{{ $money($t['revalued']) }}</td>
                        <td {!! $num !!}>@include('partials.signed-amount', ['amount' => $t['change']])</td>
                        <td colspan="4"></td>
                    </tr></tfoot>
                </table>
                @break

            @case('depreciation')
                <table class="{{ $tableClass }}">
                    <thead><tr>
                        <th>Asset Code</th><th>Type / Sub Type</th><th {!! $num !!}>Cost</th><th {!! $num !!}>Monthly Amount</th>
                        <th {!! $num !!}>Months Charged</th><th {!! $num !!}>Charge for Period</th>
                        <th {!! $num !!}>Accumulated at Period End</th><th {!! $num !!}>NBV at Period End</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td class="mono">{{ $r->asset->asset_code }}@if ($r->leftOn)<span style="font-weight: 400; color: #64748b;"> *</span>@endif</td>
                                <td>{{ $kind($r->asset) }}</td>
                                <td {!! $num !!}>{{ $money($r->cost) }}</td>
                                <td {!! $num !!}>{{ $r->monthly === null ? 'Varies' : $money($r->monthly) }}</td>
                                <td {!! $num !!}>{{ $r->months }}</td>
                                <td {!! $num !!}>{{ $money($r->charge) }}</td>
                                <td {!! $num !!}>{{ $money($r->accumulated) }}</td>
                                <td {!! $num !!}>{{ $money($r->nbv) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr style="font-weight: 700;">
                        <td colspan="5">Total</td>
                        <td {!! $num !!}>{{ $money($t['charge']) }}</td>
                        <td {!! $num !!}>{{ $money($t['accumulated']) }}</td>
                        <td {!! $num !!}>{{ $money($t['nbv']) }}</td>
                    </tr></tfoot>
                </table>
                @if ($rows->contains(fn ($r) => $r->leftOn) || $rows->contains(fn ($r) => $r->monthly === null))
                    <p class="check-ok">
                        @if ($rows->contains(fn ($r) => $r->leftOn))
                            * Left the register during the period (sold, disposed, merged or superseded): cost, accumulated and NBV are on that date.
                        @endif
                        @if ($rows->contains(fn ($r) => $r->monthly === null))
                            "Varies": the monthly amount changed during the period (revaluation); Months Charged is the longest run on one rate.
                        @endif
                    </p>
                @endif
                @break
        @endswitch
    @endif

    @if (isset($section['check']) && $section['check']->ok && $rows->isNotEmpty())
        <p class="check-ok">&#10003; Total agrees with the movement summary's {{ $section['check']->column }} column.</p>
    @endif

    @unless ($print)
        </div>
    @endunless
    </div>
@endforeach
