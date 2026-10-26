<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersByAssetType;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Support\AdminAuthorization; // NEW: admin-password check
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule; // NEW: for the merged-asset check
use Illuminate\View\View;
use App\Models\ActivityLog; 

class AssetDisposalController extends Controller
{
    use FiltersByAssetType;

    public const APPROVAL_STATUSES = ['approved' => 'Approved', 'pending' => 'Pending', 'rejected' => 'Rejected'];

    /**
     * Display a listing of asset disposals and the disposal form.
     */
    public function index(Request $request): View
    {
        $filters = $this->eventFilters($request, $this->extraFilterRules());
        $disposals = $this->filteredDisposals($filters, ['asset.assetType', 'asset.subType', 'asset.brand'])->orderByDesc('id')->get();

        // The approval queue is not filtered: every pending Lost claim needs action. (Was queried inside the view.)
        $pendingDisposals = AssetDisposal::with('asset')->where('approval_status', 'pending')->orderBy('id')->get();

        // Assets that were sold, disposed, merged into a combined asset, or superseded by revaluation are no longer disposable.
        $assets = Asset::active()->orderBy('asset_code')->get();
        $filterFields = $this->filterFields($filters);

        return view('asset-disposals.index', compact('disposals', 'pendingDisposals', 'assets', 'filterFields'));
    }

    /**
     * "Disposals Report": the disposals on the screen list (same filters), with book value at the disposal date.
     */
    public function printSummary(Request $request): View
    {
        $filters = $this->eventFilters($request, $this->extraFilterRules());
        $disposals = $this->filteredDisposals($filters, ['asset.assetType', 'asset.subType', 'asset.layers'])
            ->orderBy('disposal_date')->orderBy('id')->get();

        $rows = $disposals->map(function (AssetDisposal $disposal) {
            $asset = $disposal->asset;
            $date = $disposal->disposal_date ? Carbon::parse($disposal->disposal_date)->startOfDay() : Carbon::today();

            return (object) [
                'disposal' => $disposal,
                'asset' => $asset,
                'book_value' => $asset ? $asset->netBookValue($date) : null,
            ];
        });

        $approved = $rows->filter(fn ($r) => $r->disposal->approval_status === 'approved');
        $pending = $rows->filter(fn ($r) => $r->disposal->approval_status === 'pending');
        $rejected = $rows->filter(fn ($r) => $r->disposal->approval_status === 'rejected');
        $totals = (object) [
            'by_method' => $rows->reject(fn ($r) => $r->disposal->approval_status === 'rejected')
                ->countBy(fn ($r) => $r->disposal->method)->sortKeys(),
            'rejected_count' => $rejected->count(),
            'written_off' => round($approved->sum('book_value'), 2), // only approved disposals are written off
            'pending_count' => $pending->count(),
            'pending_book_value' => round($pending->sum('book_value'), 2),
        ];

        $period = $this->periodLabel($filters);
        $statusLabel = self::APPROVAL_STATUSES[$filters['approval_status']] ?? null;

        return view('asset-disposals.print-summary', compact('rows', 'totals', 'period', 'statusLabel'));
    }

    private function extraFilterRules(): array
    {
        return ['approval_status' => 'nullable|in:' . implode(',', array_keys(self::APPROVAL_STATUSES))];
    }

    private function filterFields(array $filters): array
    {
        return array_merge($this->dateFilterFields($filters), [[
            'name' => 'approval_status', 'label' => 'Approval Status', 'type' => 'select',
            'options' => ['' => 'All'] + self::APPROVAL_STATUSES,
        ]]);
    }

    private function filteredDisposals(array $filters, array $with)
    {
        // AssetDisposal::asset() is withTrashed(): an approved Lost item's asset is soft-deleted
        $query = AssetDisposal::with($with)
            ->when($filters['approval_status'], fn ($q, $status) => $q->where('approval_status', $status));

        $this->applyTypeFilter($query, $filters, 'asset');

        return $this->applyDateFilter($query, $filters, 'disposal_date');
    }

    /**
     * Store a newly created asset disposal in storage.
     * Note: Inputs are limited to asset_id, method, and comments.
     * No fields for disposal_method or reason.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'], // availability is checked under a lock below
            'method' => ['required', 'string', 'in:Donated,Scrapped,Lost'],
            'comments' => ['nullable', 'string', 'max:1000'], 

            // An Admin password must be supplied to record the disposal
            'admin_password' => ['required', 'string'],
        ]);



      

        // Verify the password against the Admin users. If it doesn't match,
        // nothing is saved and the user is sent back with their input preserved.
        $admin = AdminAuthorization::findAdminByPassword($validated['admin_password']);

        if (! $admin) {
            return back()
                ->withInput($request->except('admin_password'))
                ->withErrors(['admin_password' => 'Incorrect admin password. The disposal was not recorded.']);
        }

        //Logic for Lost items
        $validated['approval_status'] = ($validated['method'] === 'Lost')? 'pending' : 'approved';

        unset($validated['admin_password']);

        // Keep the created record so we can link to its printable document below.
      
        try {
            $disposal = DB::transaction(function () use ($validated, $admin) {
                // Re-check, under a lock, that the asset is still active (not sold / disposed / revalued / combined).
                if (! Asset::findActiveForUpdate($validated['asset_id'])) {
                    throw ValidationException::withMessages(['asset_id' => Asset::UNAVAILABLE_MESSAGE]);
                }

                $disposal = AssetDisposal::create([
                    'asset_id' => $validated['asset_id'],
                    'method' => $validated['method'],
                    'disposal_date' => now()->toDateString(),
                    // Record the name of the admin whose password authorized the disposal.
                    'authorized_by' => $admin->name,
                    'comments' => $validated['comments'] ?? null,
                    'approval_status' => $validated['approval_status'],
                ]);

                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'Asset Disposed - ' . $validated['method'],
                    'description' => "Recorded disposal for Asset ID: " . $validated['asset_id'],
                    'status' => 1,
                ]);

                return $disposal;
            });
        } catch (ValidationException $e) {
            // Handled here (not by the exception handler) so the admin password is never flashed back.
            return back()->withInput($request->except('admin_password'))->withErrors($e->errors());
        }

        // Also pass the document link so the page can offer "Print document" straight away.
        return redirect()->route('asset-disposals.index')
            ->with('success','Disposal recorded. Lost items require Admin approval.')
            ->with('print_url', route('asset-disposals.print', $disposal));
    }

    /**
     * NEW: printable disposal record for one disposal.
     */
    public function print(AssetDisposal $disposal): View
    {
        $disposal->load(['asset.assetType', 'asset.subType']);

        return view('asset-disposals.print', compact('disposal'));
    }

    /**
     * Approve a pending Lost claim (admin password required): the disposal becomes 'approved', the approving admin is
     * recorded in authorized_by, and the asset is soft-deleted.
     */
    public function approve(Request $request, $id): RedirectResponse
    {
        $validated = $request->validate(['admin_password' => ['required', 'string']]);

        $admin = AdminAuthorization::findAdminByPassword($validated['admin_password']);
        if (! $admin) {
            return back()->withErrors(['admin_password' => 'Incorrect admin password. The claim was not approved.']);
        }

        $result = DB::transaction(function () use ($id, $admin) {
            $disposal = AssetDisposal::with('asset')->lockForUpdate()->findOrFail($id);

            if ($disposal->approval_status !== 'pending') {
                return "{$disposal->reference} is not pending approval (it is {$disposal->approval_status}), so it was not changed.";
            }

            $recordedBy = $disposal->authorized_by;
            $disposal->update([
                'approval_status' => 'approved',
                'authorized_by' => $admin->name, // replaces the recording admin's name, which the log below keeps
            ]);

            // Soft delete the actual asset (existing behaviour)
            $disposal->asset?->delete();

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'Disposal Approved - ' . $disposal->reference,
                'description' => 'Lost claim for asset ' . ($disposal->asset->asset_code ?? '#' . $disposal->asset_id)
                    . ' approved by ' . $admin->name . ' (recorded by ' . ($recordedBy ?: 'unknown') . '). Asset removed from the registry.',
                'status' => 1,
            ]);

            return null;
        });

        if ($result) {
            return back()->withErrors(['approval_status' => $result]);
        }

        return back()->with('success', 'Lost item approved and removed from the registry.');
    }

    /**
     * Reject a pending Lost claim (admin password + reason required). The row is KEPT as 'rejected', with the reason
     * appended to comments, so the claim stays on record; the asset is active again (see Asset::scopeActive()).
     */
    public function reject(Request $request, $id): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
            'admin_password' => ['required', 'string'],
        ]);

        $admin = AdminAuthorization::findAdminByPassword($validated['admin_password']);
        if (! $admin) {
            return back()->withErrors(['admin_password' => 'Incorrect admin password. The claim was not rejected.']);
        }

        $result = DB::transaction(function () use ($id, $admin, $validated) {
            $disposal = AssetDisposal::with('asset')->lockForUpdate()->findOrFail($id);

            if ($disposal->approval_status !== 'pending') {
                return "{$disposal->reference} is not pending approval (it is {$disposal->approval_status}), so it was not changed.";
            }

            $reason = trim($validated['rejection_reason']);
            $disposal->update([
                'approval_status' => 'rejected',
                'comments' => trim(($disposal->comments ? $disposal->comments . "\n\n" : '') . 'REJECTED: ' . $reason),
            ]);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'Disposal Rejected - ' . $disposal->reference,
                'description' => 'Lost claim for asset ' . ($disposal->asset->asset_code ?? '#' . $disposal->asset_id)
                    . ' rejected by ' . $admin->name . '. Reason: ' . $reason . '. Asset remains in the registry.',
                'status' => 1,
            ]);

            return null;
        });

        if ($result) {
            return back()->withErrors(['approval_status' => $result]);
        }

        return back()->with('success', 'Lost item claim rejected. The asset remains in the active registry.');
    }
}
