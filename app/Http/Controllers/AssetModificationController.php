<?php

namespace App\Http\Controllers;

use App\Models\Asset;
// use App\Models\AssetDisposal; // CHANGED: a modification is no longer recorded as a disposal.
use App\Models\AssetModification;
use App\Support\AdminAuthorization; // NEW: admin-password check
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetModificationController extends Controller
{
    /**
     * Display a listing of asset modifications and the modification form.
     */
    public function index(): View
    {
        // CHANGED: the form data now comes from formData() (active assets + revert options).
        // $modifications = AssetModification::with('asset')
        //     ->orderByDesc('id')
        //     ->paginate(10);
        //
        // $assets = Asset::orderBy('asset_code')->get();
        // $components = Asset::doesntHave('sale')->doesntHave('disposal')->get();
        //
        // return view('asset-modifications.index', compact('modifications', 'assets', 'components'));

        $modifications = AssetModification::with(['asset', 'resultAsset']) // CHANGED: also load the combined asset
            ->orderByDesc('id')
            ->get(); // CHANGED: DataTables does the paging/search/sort in the browser

        return view('asset-modifications.index', ['modifications' => $modifications] + $this->formData());
    }

    /**
     * Show the form for creating a new asset modification.
     */
    public function create(): View
    {
        // CHANGED: uses formData() like index().
        // $components = Asset::doesntHave('sale')->doesntHave('disposal')->get();
        // $assets = Asset::orderBy('asset_code')->get();
        //
        // return view('asset-modifications.index', compact('assets', 'components'));

        return view('asset-modifications.index', $this->formData());
    }

    /**
     * NEW: data for the modification form.
     *
     *  - $assets / $components: only ACTIVE assets (not sold, not disposed, not already merged away).
     *    This includes combined assets (e.g. ID 003) so they can be modified again or reverted.
     *  - $revertOptions: for each combined asset, the component(s) that were added to it, so the
     *    "Removed Asset / Component" dropdown can show them (keyed by combined asset id).
     */
    private function formData(): array
    {
        $assets = Asset::active()->orderBy('asset_code')->get();
        $components = $assets;

        $revertOptions = AssetModification::with('componentAsset')
            ->whereNotNull('result_asset_id')
            ->whereNull('reverted_at')
            ->get()
            ->filter(fn ($mod) => $mod->componentAsset) // skip rows whose component no longer exists
            ->groupBy('result_asset_id')
            ->map(fn ($mods) => $mods->map(fn ($mod) => [
                'id' => $mod->component_asset_id,
                'label' => trim($mod->componentAsset->asset_code . ' - ' . $mod->componentAsset->brand_name . ' ' . $mod->componentAsset->model),
            ])->values());

        return compact('assets', 'components', 'revertOptions');
    }

    /**
     * Store a newly created asset modification in storage.
     *
     * NEW behaviour:
     *  - If a "removed asset" is chosen, the modification of the selected combined asset is REVERTED.
     *  - Otherwise the selected asset and the modification item are COMBINED into a brand-new asset.
     */
    public function store(Request $request): RedirectResponse
    {
        // CHANGED: component_asset_id is only required when not reverting; the free-text
        // "removed_asset" input became a dropdown (removed_asset_id).
        // $validated = $request->validate([
        //     'modification_id' => ['required', 'string', 'max:50', 'unique:asset_modifications,modification_id'],
        //     'asset_id' => ['required', 'exists:assets,id'],
        //     'component_asset_id' => ['required', 'exists:assets,id'],
        //     'modification_date' => ['required', 'date'],
        //     'removed_asset' => ['nullable', 'string', 'max:150'],
        //     'new_value' => ['nullable', 'numeric', 'min:0'],
        //     'new_location' => ['nullable', 'string', 'max:100'],
        //     // CHANGED: the free-text "authorized_by" input was removed from the form.
        //     // 'authorized_by' => ['nullable', 'string', 'max:150'],
        //     // NEW: an Admin password must now be supplied (via the popup on "Record Modification").
        //     'admin_password' => ['required', 'string'],
        // ]);
        $validated = $request->validate([
            'modification_id' => ['required', 'string', 'max:50', 'unique:asset_modifications,modification_id'],
            'asset_id' => ['required', 'exists:assets,id'],
            'component_asset_id' => ['required_without:removed_asset_id', 'nullable', 'exists:assets,id', 'different:asset_id'],
            'removed_asset_id' => ['nullable', 'exists:assets,id'],
            'modification_date' => ['required', 'date'],
            'new_value' => ['nullable', 'numeric', 'min:0'],
            'new_location' => ['nullable', 'string', 'max:100'],
            'comments' => ['nullable', 'string', 'max:1000'], // NEW: optional notes
            // CHANGED: the free-text "authorized_by" input was removed from the form.
            'admin_password' => ['required', 'string'],
        ]);

        // NEW: verify the password against the Admin users before touching any data.
        $admin = AdminAuthorization::findAdminByPassword($validated['admin_password']);

        if (! $admin) {
            return back()
                ->withInput($request->except('admin_password'))
                ->withErrors(['admin_password' => 'Incorrect admin password. The modification was not recorded.']);
        }

        // NEW: the password itself must never be saved, and the authorizer is the matched admin.
        unset($validated['admin_password']);
        $validated['authorized_by'] = $admin->name;

        // NEW: revert when a removed asset was chosen, otherwise combine the two assets.
        $isRevert = ! empty($validated['removed_asset_id']);

        $error = null;
        DB::transaction(function () use ($validated, $isRevert, &$error) {
            $error = $isRevert ? $this->revert($validated) : $this->combine($validated);
        });

        if ($error) {
            // The admin password must never be flashed back into the session/form.
            return back()->withInput($request->except('admin_password'))->withErrors(['asset_id' => $error]);
        }

        return redirect()->route('asset-modifications.index')->with(
            'success',
            $isRevert
                ? 'Modification reverted. The original assets were restored to the registry.'
                : 'Asset modification recorded. The assets were combined into a new asset.'
        );
    }

    /**
     * NEW: combine two assets (e.g. CPU 001 + motherboard 002) into a NEW asset (003).
     * The two originals are kept but hidden from the registry (merged_into_asset_id).
     *
     * @return string|null  an error message, or null on success
     */
    private function combine(array $data): ?string
    {
        $parent = Asset::active()->find($data['asset_id']);
        $component = Asset::active()->find($data['component_asset_id']);

        if (! $parent || ! $component) {
            return 'Both assets must be active (not sold, disposed or already combined).';
        }

        // Combined cost = cost of both originals. The originals' own costs are NOT changed,
        // which is what makes a later revert exact.
        $totalCost = round((float) $parent->cost + (float) $component->cost, 2);

        // Brand / model of the new asset describe both parts, e.g. "ASUS + Gigabyte".
        $brand = Str::limit(implode(' + ', array_filter([$parent->brand_name, $component->brand_name])), 100, '');
        $model = Str::limit(implode(' + ', array_filter([$parent->model, $component->model])), 100, '');

        // The new asset gets a brand-new asset code and is registered like any other asset.
        $combined = Asset::create([
            'asset_code' => Asset::generateCode(),
            'asset_type_id' => $parent->asset_type_id,
            'asset_sub_type_id' => $parent->asset_sub_type_id,
            'brand_name' => $brand ?: null,
            'model' => $model ?: null,
            'financial_year' => $parent->financial_year,
            'location' => ($data['new_location'] ?? null) ?: $parent->location,
            'cost' => $totalCost,
        ]);

        // NEW: the combined asset stays where the parent asset was allocated, unless a new location
        // was typed in the form (that text is then shown as its location instead).
        $parentAllocation = $parent->currentAllocation;
        if ($parentAllocation && empty($data['new_location'])) {
            $combined->allocations()->create([
                'allocation_type' => $parentAllocation->allocation_type,
                'allocated_to' => $parentAllocation->allocated_to,
                'allocated_by' => $data['authorized_by'],
            ]);
        }

        // Remove the two originals from the registry by pointing them at the new asset.
        $parent->update(['merged_into_asset_id' => $combined->id]);
        $component->update(['merged_into_asset_id' => $combined->id]);

        // History row. component_asset_id / result_asset_id are what a later revert uses.
        AssetModification::create([
            'modification_id' => $data['modification_id'],
            'asset_id' => $parent->id,
            'component_asset_id' => $component->id,
            'result_asset_id' => $combined->id,
            'modification_date' => $data['modification_date'],
            'added_asset' => trim($component->asset_code . ' - ' . ($component->brand_name ?? '')),
            'new_value' => $totalCost,
            'new_location' => $data['new_location'] ?? null,
            'authorized_by' => $data['authorized_by'],
            // NEW: save each original's price so a revert can put them back exactly.
            'parent_original_cost' => $parent->cost,
            'component_original_cost' => $component->cost,
            'comments' => $data['comments'] ?? null,
        ]);

        return null;
    }

    /**
     * NEW: revert a modification. $data['asset_id'] is the combined asset (003) and
     * $data['removed_asset_id'] the component that was added to it (002). The combined asset
     * is deleted and both originals (001, 002) are restored to the registry.
     *
     * @return string|null  an error message, or null on success
     */
    private function revert(array $data): ?string
    {
        $combined = Asset::active()->find($data['asset_id']);

        if (! $combined) {
            return 'The selected asset is not active, so its modification cannot be reverted.';
        }

        $modification = AssetModification::where('result_asset_id', $combined->id)
            ->where('component_asset_id', $data['removed_asset_id'])
            ->whereNull('reverted_at')
            ->first();

        if (! $modification) {
            return 'That item was not added to the selected asset, so there is nothing to revert.';
        }

        $parent = Asset::find($modification->asset_id);
        $component = Asset::find($modification->component_asset_id);

        if (! $parent || ! $component) {
            return 'The original assets could not be found, so the modification cannot be reverted.';
        }

        // Put the originals back in the registry.
        // CHANGED: their prices are now also explicitly restored to the values saved when they were
        // combined (e.g. 100000 and 10000), instead of assuming nothing touched them meanwhile.
        // Modifications made before the saved costs existed have null and keep the current price.
        // $parent->update(['merged_into_asset_id' => null]);
        // $component->update(['merged_into_asset_id' => null]);
        $parent->merged_into_asset_id = null;
        if ($modification->parent_original_cost !== null) {
            $parent->cost = $modification->parent_original_cost;
        }
        $parent->save();

        $component->merged_into_asset_id = null;
        if ($modification->component_original_cost !== null) {
            $component->cost = $modification->component_original_cost;
        }
        $component->save();

        // Mark the original modification as reverted and log the revert as its own history row.
        // result_asset_id is cleared because that asset is about to be deleted; leaving it would
        // point at a missing (or, if the id were ever reused, an unrelated) asset.
        $modification->update(['reverted_at' => now(), 'result_asset_id' => null]);

        AssetModification::create([
            'modification_id' => $data['modification_id'],
            'asset_id' => $parent->id,
            'modification_date' => $data['modification_date'],
            'removed_asset' => trim($component->asset_code . ' - ' . ($component->brand_name ?? '')),
            'new_value' => $parent->cost,
            'new_location' => $data['new_location'] ?? null,
            'authorized_by' => $data['authorized_by'],
            'comments' => $data['comments'] ?? null,
        ]);

        // Finally remove the combined asset from the registry.
        $combined->delete();

        return null;
    }

    // CHANGED: the old store() body, kept for reference. It added the component's cost onto the
    // parent asset and recorded the component as a DISPOSAL ("Merged into Asset ID: x").
    //
    //     DB::transaction(function () use ($validated) {
    //         $parentAsset = Asset::findOrFail($validated['asset_id']);
    //         $componentAsset = Asset::findOrFail($validated['component_asset_id']);
    //
    //         //Calculate the new total cost
    //         $newTotalCost = $parentAsset->cost + $componentAsset->cost;
    //
    //         // 3. Update the validated array with the new values BEFORE creating the modification log
    //         $validated['added_asset'] = trim($componentAsset->asset_code . ' - ' . ($componentAsset->brand_name ?? ''));
    //         $validated['new_value'] = $newTotalCost;
    //
    //         AssetModification::create($validated);
    //
    //         $parentAsset = Asset::findOrFail($validated['asset_id']);
    //         $componentAsset = Asset::findOrFail($validated['component_asset_id']);
    //
    //         $parentAsset->cost += $componentAsset->cost;
    //         $parentAsset->save();
    //
    //         AssetDisposal::create([
    //             'disposal_id' => 'DISP-MOD-' . $componentAsset->id . '-' . strtoupper(Str::random(6)),
    //             'asset_id' => $componentAsset->id,
    //             'method' => 'Merged into Asset ID: ' . $parentAsset->id,
    //             'disposal_date' => now()->toDateString(),
    //             'authorized_by' => $validated['authorized_by'],
    //         ]);
    //     });
    //
    //     return redirect()->route('asset-modifications.index')->with('success', 'Asset modification recorded successfully.');
}
