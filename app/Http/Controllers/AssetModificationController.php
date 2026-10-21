<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetModification;
use App\Models\AssetSubType;
use App\Support\AdminAuthorization; // admin-password check
use App\Support\Depreciation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\ActivityLog;

class AssetModificationController extends Controller
{
    /**
     * Display a listing of asset modifications and the modification form.
     */
    public function index(): View
    {
        $modifications = AssetModification::with(['asset', 'resultAsset'])
            ->orderByDesc('id')
            ->get(); // DataTables does the paging/search/sort in the browser

        return view('asset-modifications.index', ['modifications' => $modifications] + $this->formData());
    }

    /**
     * Show the form for creating a new asset modification.
     */
    public function create(): View
    {
        return view('asset-modifications.index', $this->formData());
    }

    /**
     * Data for the modification form.
     *
     *  - $assets / $components: only ACTIVE assets (not sold, not disposed, not already merged away).
     *    Components removed by an earlier modification are normal registry assets, so they appear here too.
     *  - $partOptions: for each asset that was made by combining earlier assets, those original parts, so one
     *    of them can be picked as the "removed component" (it is then restored to the registry).
     *  - $assetSubTypes: for describing a removed component that is not one of those parts.
     */
    private function formData(): array
    {
        $assets = Asset::active()->with('components')->orderBy('asset_code')->get();
        $components = $assets;

        $partOptions = $assets
            ->filter(fn ($asset) => $asset->components->isNotEmpty())
            ->mapWithKeys(fn ($asset) => [
                $asset->id => $asset->components->map(fn ($part) => [
                    'id' => $part->id,
                    'label' => trim($part->asset_code . ' - ' . $part->brand_name . ' ' . $part->model),
                    'cost' => (float) $part->cost,
                ])->values(),
            ]);

        $assetSubTypes = AssetSubType::orderBy('sub_type_name')->get();

        return compact('assets', 'components', 'partOptions', 'assetSubTypes');
    }

    /**
     * Store a newly created asset modification in storage.
     *
     * The selected asset and the added component are COMBINED into a brand-new asset. If a component is
     * removed during the modification, it goes back into the registry as an asset of its own (with the price
     * entered), so it can be used in future modifications.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'modification_type' => ['required', Rule::in(AssetModification::TYPES)],
            'asset_id' => ['required', 'exists:assets,id'],
            // The component that goes in. It is always required: even when a part is removed, a new one takes its place.
            'component_asset_id' => ['required', 'exists:assets,id', 'different:asset_id'],
            'modification_date' => ['required', 'date'],
            'comments' => ['nullable', 'string', 'max:1000'],

            // The component that comes out (optional): '' = none, 'new' = typed in below, or the id of one of the
            // asset's original parts.
            'removed_part' => ['nullable', 'string', 'max:20'],
            'removed_price' => ['nullable', 'required_with:removed_part', 'numeric', 'min:0'],
            'removed_brand' => ['nullable', 'string', 'max:100'],
            'removed_model' => ['nullable', 'string', 'max:100'],
            'removed_asset_sub_type_id' => ['nullable', 'exists:asset_sub_types,id'],

            // For a typed-in component: the asset (or one of its original parts) it originally came with.
            'removed_origin_asset_id' => ['nullable', 'exists:assets,id'],

            'admin_password' => ['required', 'string'],
        ]);

        // Check the typed-in removed component before asking for / checking the password.
        if (($validated['removed_part'] ?? null) === 'new'
            && empty($validated['removed_brand']) && empty($validated['removed_model'])) {
            return back()->withInput($request->except('admin_password'))
                ->withErrors(['removed_brand' => 'Enter the brand or model of the removed component.']);
        }

        if (($validated['removed_part'] ?? null) !== null
            && $validated['removed_part'] !== 'new' && ! ctype_digit($validated['removed_part'])) {
            return back()->withInput($request->except('admin_password'))
                ->withErrors(['removed_part' => 'Select a valid removed component.']);
        }

        // Verify the password against the Admin users before touching any data.
        $admin = AdminAuthorization::findAdminByPassword($validated['admin_password']);

        if (! $admin) {
            return back()
                ->withInput($request->except('admin_password'))
                ->withErrors(['admin_password' => 'Incorrect admin password. The modification was not recorded.']);
        }

        // The password itself must never be saved, and the authorizer is the matched admin.
        unset($validated['admin_password']);
        $validated['authorized_by'] = $admin->name;

        $error = null;
        $type = null;
        $restoredCode = null;
        DB::transaction(function () use ($validated, &$error, &$type, &$restoredCode) {
            [$error, $type, $restoredCode] = $this->modify($validated) + [null, null, null];
        });

        if ($error) {
            // The admin password must never be flashed back into the session/form.
            return back()->withInput($request->except('admin_password'))->withErrors(['asset_id' => $error]);
        }

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'Asset Modified',
            'description' => "Modification recorded for Parent Asset ID: " . $validated['asset_id'],
            'status' => 1,
        ]);

        return redirect()->route('asset-modifications.index')->with(
            'success',
            'Asset modification recorded (' . strtolower($type) . '). '
                . ($restoredCode
                    ? 'This undid an earlier modification, so the original asset ' . $restoredCode . ' is back in the registry under its original code'
                    : 'The assets were combined into a new asset')
                . (! empty($validated['removed_part']) ? ' and the removed component was returned to the registry.' : '.')
        );
    }

    /**
     * Combine the asset with the added component into a NEW asset, and register the removed component (if any).
     * The originals are kept but hidden from the registry (merged_into_asset_id).
     *
     * @return array{0: string|null, 1: string|null}  [error message or null, modification type]
     */
    private function modify(array $data): array
    {
        $parent = Asset::active()->find($data['asset_id']);
        $component = Asset::active()->find($data['component_asset_id']);

        if (! $parent || ! $component) {
            return ['Both assets must be active (not sold, disposed or already combined).', null];
        }

        $removedPart = $data['removed_part'] ?? null;
        $removedPrice = $removedPart ? round((float) $data['removed_price'], 2) : 0.0;

        if ($removedPrice > (float) $parent->cost) {
            return ['The removed component cannot cost more than the asset it is removed from (' . number_format($parent->cost, 2) . ').', null];
        }

        // The asset's current allocation is carried over to the new assets.
        $allocation = $parent->currentAllocation;

        // Depreciation: each asset's cost is held in layers that keep their own start date and life, so assets that
        // were registered on different dates keep their own age when they are combined (see App\Support\Depreciation).
        $toArray = fn ($layer) => [
            'source_asset_id' => $layer->source_asset_id,
            'cost' => (float) $layer->cost,
            'start_date' => $layer->start_date instanceof \DateTimeInterface ? $layer->start_date->format('Y-m-d') : (string) $layer->start_date,
            'end_date' => $layer->end_date ? ($layer->end_date instanceof \DateTimeInterface ? $layer->end_date->format('Y-m-d') : (string) $layer->end_date) : null,
            'useful_life_months' => $layer->useful_life_months,
        ];
        $parentLayers = $parent->layers->map($toArray)->all();
        $componentLayers = $component->layers->map($toArray)->all();

        // 1. The removed component goes back into the registry as an asset of its own.
        $removed = null;
        if ($removedPart === 'new') {
            // It originally came with the asset itself, or with one of the parts that asset was made from.
            $originId = ! empty($data['removed_origin_asset_id']) ? (int) $data['removed_origin_asset_id'] : $parent->id;

            if ($originId !== $parent->id && ! in_array($originId, $parent->descendantIds(), true)) {
                return ['The selected "originally came with" asset is not part of the selected asset.', null];
            }

            $subType = ! empty($data['removed_asset_sub_type_id']) ? AssetSubType::find($data['removed_asset_sub_type_id']) : null;
            $typeId = $subType?->asset_type_id ?? $parent->asset_type_id;
            $subTypeId = $subType?->id ?? $parent->asset_sub_type_id;

            $removed = Asset::create([
                'asset_code' => Asset::generateCode((int) $typeId, (int) $subTypeId),
                'asset_type_id' => $typeId,
                'asset_sub_type_id' => $subTypeId,
                'brand_name' => $data['removed_brand'] ?? null,
                'model' => $data['removed_model'] ?? null,
                'financial_year' => $parent->financial_year,
                'location' => null,
                'cost' => $removedPrice,
                'origin_asset_id' => $originId, // trace the component back to the asset it came with
            ]);

            // It was part of the asset (or of the part it came with), so its cost is taken out of that part's layers
            // and keeps their start dates. Its own sub type decides how long it lasts, when that is set.
            $prefer = $originId === $parent->id ? null : array_merge([$originId], Asset::find($originId)->descendantIds());
            [$removedLayers, $parentLayers] = Depreciation::slice($parentLayers, $removedPrice, $prefer);

            $ownMonths = $subType?->useful_life_years ? $subType->useful_life_years * 12 : null;
            foreach ($removedLayers as $layer) {
                $removed->layers()->create([
                    'source_asset_id' => $removed->id,
                    'cost' => $layer['cost'],
                    'start_date' => $layer['start_date'],
                    'useful_life_months' => $ownMonths ?? $layer['useful_life_months'],
                ]);
            }
        } elseif ($removedPart !== null) {
            // One of the original parts the asset was made from: restore it to the registry at the entered price.
            $removed = Asset::where('id', $removedPart)->where('merged_into_asset_id', $parent->id)->first();

            if (! $removed) {
                return ['The selected component is not part of the selected asset.', null];
            }

            $removed->merged_into_asset_id = null;
            $removed->cost = $removedPrice;
            $removed->save();

            // Its cost is taken out of the parent's layers (its own first) and its layers are rebuilt from that,
            // so it keeps its original start date(s) at the price entered.
            [$removedLayers, $parentLayers] = Depreciation::slice(
                $parentLayers,
                $removedPrice,
                array_merge([$removed->id], $removed->descendantIds())
            );

            $removed->layers()->delete();
            foreach ($removedLayers as $layer) {
                $removed->layers()->create($layer);
            }
        }

        if ($removed && $allocation) {
            $removed->allocations()->create([
                'allocation_type' => $allocation->allocation_type,
                'allocated_to' => $allocation->allocated_to,
                'allocated_by' => $data['authorized_by'],
            ]);
        }

        // 2. The new combined asset. Cost = asset - removed component + added component.
        $totalCost = round((float) $parent->cost - $removedPrice + (float) $component->cost, 2);

        // Brand / model describe the parts, e.g. "ASUS + Gigabyte" (the removed part's name is taken out).
        $brand = $this->joinNames($parent->brand_name, $component->brand_name, $removed?->brand_name ?? ($data['removed_brand'] ?? null));
        $model = $this->joinNames($parent->model, $component->model, $removed?->model ?? ($data['removed_model'] ?? null));

        // Undoing an earlier modification: this asset is the result of modification M, the part being removed now is the
        // one that replaced a component in M, and the component being added now is the one M removed. The result is then
        // the asset M started from, so that asset gets its ORIGINAL id back instead of a new one being created.
        $original = null;
        if ($removedPart !== null && $removedPart !== 'new') {
            $earlier = AssetModification::where('removed_asset_id', $component->id)->orderByDesc('id')->first();

            if ($earlier
                && (int) $earlier->result_asset_id === (int) $parent->id
                && (int) $earlier->component_asset_id === (int) $removedPart) {
                $candidate = Asset::find($earlier->asset_id);

                if ($candidate && (int) $candidate->merged_into_asset_id === (int) $parent->id) {
                    $original = $candidate;
                }
            }
        }

        if ($original) {
            $combined = $original;
            $combined->merged_into_asset_id = null; // back in the registry under its original code
            $combined->cost = $totalCost;
            $combined->save();
            $combined->layers()->delete();
        } else {
            $combined = Asset::create([
                'asset_code' => Asset::generateCode((int) $parent->asset_type_id, (int) $parent->asset_sub_type_id),
                'asset_type_id' => $parent->asset_type_id,
                'asset_sub_type_id' => $parent->asset_sub_type_id,
                'brand_name' => $brand ?: null,
                'model' => $model ?: null,
                'financial_year' => $parent->financial_year,
                'location' => $parent->location,
                'cost' => $totalCost,
            ]);
        }

        // The combined asset's layers: what is left of the asset plus the added component, each with its own start date.
        foreach (array_merge($parentLayers, $componentLayers) as $layer) {
            $combined->layers()->create($layer);
        }

        if ($allocation) {
            $combined->allocations()->create([
                'allocation_type' => $allocation->allocation_type,
                'allocated_to' => $allocation->allocated_to,
                'allocated_by' => $data['authorized_by'],
            ]);
        }

        // Remove the two originals from the registry by pointing them at the new asset.
        $parent->update(['merged_into_asset_id' => $combined->id]);
        $component->update(['merged_into_asset_id' => $combined->id]);

        $type = $data['modification_type'];

        AssetModification::create([
            'asset_id' => $parent->id,
            'component_asset_id' => $component->id,
            'result_asset_id' => $combined->id,
            'modification_date' => $data['modification_date'],
            'modification_type' => $type,
            'removed_asset' => $removed ? trim($removed->asset_code . ' - ' . trim($removed->brand_name . ' ' . $removed->model)) : null,
            'removed_asset_id' => $removed?->id,
            'removed_price' => $removed ? $removedPrice : null,
            'added_asset' => trim($component->asset_code . ' - ' . ($component->brand_name ?? '')),
            'new_value' => $totalCost,
            'new_location' => null,
            'authorized_by' => $data['authorized_by'],
            'parent_original_cost' => $parent->cost,
            'component_original_cost' => $component->cost,
            'comments' => $data['comments'] ?? null,
        ]);

        return [null, $type, $original?->asset_code];
    }

    /**
     * "ASUS + Gigabyte" from the parent and added names, with the removed part's name taken out of the parent's.
     */
    private function joinNames(?string $parent, ?string $added, ?string $removed): string
    {
        $parts = $parent ? explode(' + ', $parent) : [];

        if ($removed) {
            foreach ($parts as $i => $part) {
                if (strcasecmp(trim($part), trim($removed)) === 0) {
                    unset($parts[$i]);
                    break;
                }
            }
        }

        if ($added) {
            $parts[] = $added;
        }

        return Str::limit(implode(' + ', array_filter($parts)), 100, '');
    }
}
