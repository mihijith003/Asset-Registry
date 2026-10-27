<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersByAssetType;
use App\Support\Depreciation;
use App\Support\PeriodMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// Period Report: movement summary (Opening -> Closing NBV per asset type) for a chosen date range.
class ReportController extends Controller
{
    use FiltersByAssetType;

    public function index(Request $request): View
    {
        return view('reports.period', $this->report($request));
    }

    public function print(Request $request): View
    {
        return view('reports.period-print', $this->report($request));
    }

    private function report(Request $request): array
    {
        $presets = $this->presets();
        $filters = $this->typeFilters($request);

        // First visit (no dates in the query string): this month. Once submitted, both dates are required.
        $input = $request->query->has('date_from') || $request->query->has('date_to')
            ? $request->only('date_from', 'date_to')
            : ['date_from' => $presets['this-month']['from'], 'date_to' => $presets['this-month']['to']];

        $validator = Validator::make($input, [
            'date_from' => 'required|date|before_or_equal:today',
            'date_to' => 'required|date|after_or_equal:date_from',
        ], ['date_from.before_or_equal' => 'The period cannot start in the future.']);

        if ($validator->fails()) {
            throw (new ValidationException($validator))
                ->redirectTo($request->fullUrlWithoutQuery(['date_from', 'date_to']));
        }

        $from = Carbon::parse($input['date_from'])->startOfDay();
        $to = Carbon::parse($input['date_to'])->startOfDay();
        $movement = new PeriodMovement($from, $to, $filters);
        $result = $movement->run();

        $period = $from->toDateString() . ' to ' . $to->toDateString();
        $closingNote = $movement->closingCapped
            ? "Closing values as at {$movement->closingDate} (period end {$to->toDateString()} is in the future)"
            : null;

        $filterFields = [
            ['name' => 'date_from', 'label' => 'Date from', 'type' => 'date', 'default' => $input['date_from']],
            ['name' => 'date_to', 'label' => 'Date to', 'type' => 'date', 'default' => $input['date_to']],
        ];

        return compact('result', 'movement', 'period', 'closingNote', 'presets', 'filterFields');
    }

    /**
     * Quick presets for the period selector. "Month End" is Last Month.
     */
    private function presets(): array
    {
        $today = Carbon::today();
        [$fyStart, $fyEnd] = Depreciation::range(Depreciation::label(Depreciation::financialYearOf($today)));
        $lastFyStart = $fyStart->copy()->subYear();

        return [
            'this-month' => ['label' => 'This Month', 'from' => $today->copy()->startOfMonth()->toDateString(), 'to' => $today->copy()->endOfMonth()->toDateString()],
            'last-month' => ['label' => 'Last Month (Month End)', 'from' => $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'to' => $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'this-fy' => ['label' => 'This Financial Year', 'from' => $fyStart->toDateString(), 'to' => $fyEnd->copy()->subDay()->toDateString()],
            'last-fy' => ['label' => 'Last Financial Year', 'from' => $lastFyStart->toDateString(), 'to' => $fyStart->copy()->subDay()->toDateString()],
        ];
    }
}
