<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetModification;
use App\Models\AssetRevaluation;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AssetTimelineController extends Controller
{
    /**
     * Display the complete lifecycle timeline of an asset across all its historical/successor rows.
     */
    public function show(Asset $asset): View
    {
        $chain = $asset->resolveChain([
            'assetType',
            'subType',
            'brand',
            'allocations',
            'currentAllocation',
            'sales',
            'sale',
            'disposals',
            'disposal',
            'layers',
            'originAsset',
            'removalModification.asset',
            'supersedes',
            'supersededBy',
            'mergedInto',
            'components',
            'revaluationIn.asset',
            'modifications',
        ]);

        // Use the eager-loaded instance from the resolved chain so accessing relations on $asset triggers 0 queries
        $asset = $chain->firstWhere('id', $asset->id) ?? $asset;
        $chainIds = $chain->pluck('id')->all();

        // Eager-load all modifications touching any asset in the chain
        $modifications = AssetModification::with(['asset', 'componentAsset', 'resultAsset', 'removedAsset'])
            ->where(function ($query) use ($chainIds) {
                $query->whereIn('asset_id', $chainIds)
                    ->orWhereIn('component_asset_id', $chainIds)
                    ->orWhereIn('result_asset_id', $chainIds)
                    ->orWhereIn('removed_asset_id', $chainIds);
            })
            ->orderBy('modification_date')
            ->orderBy('id')
            ->get()
            ->unique('id');

        // Eager-load all revaluations touching any asset in the chain
        $revaluations = AssetRevaluation::with(['asset', 'resultAsset'])
            ->where(function ($query) use ($chainIds) {
                $query->whereIn('asset_id', $chainIds)
                    ->orWhereIn('result_asset_id', $chainIds);
            })
            ->orderBy('revaluation_date')
            ->orderBy('id')
            ->get()
            ->unique('id');

        // Identify assets that were created as the result of a modification or revaluation
        $modResultMap = $modifications->whereNotNull('result_asset_id')->keyBy('result_asset_id');
        $modRemovedMap = $modifications->whereNotNull('removed_asset_id')->keyBy('removed_asset_id');
        $revalResultMap = $revaluations->whereNotNull('result_asset_id')->keyBy('result_asset_id');

        $events = [];

        // 1. Registered, Allocated, Sold, and Disposed events for each asset in the chain
        foreach ($chain as $node) {
            $regDate = $node->created_at
                ? $node->created_at->copy()
                : ($node->purchase_date ? $node->purchase_date->copy()->startOfDay() : Carbon::today());

            $transitions = [];
            $regSubtitle = 'Initial Asset Registration';

            if ($revalResultMap->has($node->id)) {
                $reval = $revalResultMap->get($node->id);
                $regSubtitle = "Successor Asset Created via Revaluation ({$reval->reference})";
                if ($reval->asset) {
                    $transitions[] = [
                        'kind' => 'revalued',
                        'from' => $reval->asset,
                        'to' => $node,
                    ];
                }
            } elseif ($modResultMap->has($node->id)) {
                $mod = $modResultMap->get($node->id);
                $regSubtitle = "Combined Asset Created via Modification ({$mod->reference})";
                if ($mod->asset || $mod->componentAsset) {
                    $transitions[] = [
                        'kind' => 'combined',
                        'parent' => $mod->asset,
                        'component' => $mod->componentAsset,
                        'component_text' => $mod->added_asset,
                        'result' => $node,
                    ];
                }
            } elseif ($modRemovedMap->has($node->id) || $node->originAsset) {
                $fromAsset = $node->originAsset ?? $modRemovedMap->get($node->id)?->asset;
                $regSubtitle = 'Removed Component Registered as Standalone Asset';
                if ($fromAsset) {
                    $transitions[] = [
                        'kind' => 'removed',
                        'from' => $fromAsset,
                        'to' => $node,
                    ];
                }
            }

            $events[] = (object) [
                'type' => 'registered',
                'badge_class' => 'badge-success',
                'badge_label' => 'Registered',
                'date' => $node->created_at ? $node->created_at->format('Y-m-d H:i') : ($node->purchase_date?->format('Y-m-d') ?? '-'),
                'sort_time' => $regDate->timestamp,
                'sort_seq' => 10 + (int) $node->id,
                'title' => "Registered {$node->asset_code}",
                'subtitle' => $regSubtitle,
                'asset' => $node,
                'transitions' => $transitions,
                'figures' => array_filter([
                    'Asset Code' => ['asset' => $node],
                    'Recorded Cost' => 'LKR ' . number_format((float) $node->cost, 2),
                    'Purchase Date' => $node->purchase_date ? $node->purchase_date->format('Y-m-d') : null,
                    'Financial Year' => $node->financial_year,
                    'Useful Life' => $node->useful_life_months ? $node->useful_life_months . ' months' : null,
                    'Monthly Depreciation' => $node->monthly_depreciation !== null ? 'LKR ' . number_format((float) $node->monthly_depreciation, 2) : null,
                    'Residual Value' => ((float) $node->residual_value > 0) ? 'LKR ' . number_format((float) $node->residual_value, 2) : null,
                    'Supplier' => $node->supplier,
                    'PO / Invoice' => trim(($node->po_reference ?? '') . ($node->invoice_reference ? ' / ' . $node->invoice_reference : ''), ' /') ?: null,
                ]),
                'comments' => $node->comments,
            ];

            // Allocations (sorted oldest first to label Allocated vs Reallocated)
            $chronologicalAllocations = $node->allocations->sortBy('id')->values();
            foreach ($chronologicalAllocations as $idx => $alloc) {
                $allocDate = $alloc->created_at ? $alloc->created_at->copy() : $regDate->copy();
                $isFirst = ($idx === 0);
                $events[] = (object) [
                    'type' => 'allocated',
                    'badge_class' => 'badge-secondary',
                    'badge_label' => $isFirst ? 'Allocated' : 'Reallocated',
                    'date' => $alloc->created_at ? $alloc->created_at->format('Y-m-d H:i') : '-',
                    'sort_time' => $allocDate->timestamp,
                    'sort_seq' => 30 + (int) $alloc->id,
                    'title' => ($isFirst ? 'Allocated ' : 'Reallocated ') . $node->asset_code . ' to ' . $alloc->label,
                    'subtitle' => 'Asset Allocation (' . (\App\Models\AssetAllocation::TYPES[$alloc->allocation_type] ?? ucfirst($alloc->allocation_type)) . ')',
                    'asset' => $node,
                    'transitions' => [],
                    'figures' => array_filter([
                        'Asset' => ['asset' => $node],
                        'Allocation Type' => \App\Models\AssetAllocation::TYPES[$alloc->allocation_type] ?? ucfirst($alloc->allocation_type),
                        'Allocated To' => $alloc->allocated_to,
                        'Allocated By' => $alloc->allocated_by,
                    ]),
                    'comments' => null,
                ];
            }

            // Sales
            foreach ($node->sales as $sale) {
                $saleDate = $sale->sale_date ? Carbon::parse($sale->sale_date)->endOfDay() : Carbon::today()->endOfDay();
                $events[] = (object) [
                    'type' => 'sold',
                    'badge_class' => 'badge-danger',
                    'badge_label' => 'Sold',
                    'date' => $sale->sale_date ?? '-',
                    'sort_time' => $saleDate->timestamp,
                    'sort_seq' => 80 + (int) $sale->id,
                    'title' => "Sold {$node->asset_code} ({$sale->reference})",
                    'subtitle' => 'Asset Sale — Approval Status: Approved',
                    'asset' => $node,
                    'transitions' => [],
                    'figures' => array_filter([
                        'Sale Reference' => $sale->reference,
                        'Asset' => ['asset' => $node],
                        'Sale Price' => 'LKR ' . number_format((float) $sale->price, 2),
                        'Approval Status' => 'Approved',
                        'Buyer' => $sale->buyer_name,
                        'Buyer Contact' => $sale->buyer_contact,
                        'Payment Method' => $sale->payment_method,
                        'Authorized By' => $sale->authorized_by,
                    ]),
                    'comments' => $sale->comments,
                    'document_url' => route('asset-sales.print', $sale),
                ];
            }

            // Disposals
            foreach ($node->disposals as $disposal) {
                $dispDate = $disposal->disposal_date ? Carbon::parse($disposal->disposal_date)->endOfDay() : Carbon::today()->endOfDay();
                $statusLabel = ucfirst($disposal->approval_status ?? 'approved');
                $isRejected = $disposal->approval_status === 'rejected';
                $events[] = (object) [
                    'type' => 'disposed',
                    'badge_class' => $isRejected ? 'badge-secondary' : 'badge-danger',
                    'badge_label' => $isRejected ? 'Disposal Rejected' : 'Disposed (' . $statusLabel . ')',
                    'date' => $disposal->disposal_date ?? '-',
                    'sort_time' => $dispDate->timestamp,
                    'sort_seq' => 90 + (int) $disposal->id,
                    'title' => $isRejected
                        ? "Disposal claim rejected for {$node->asset_code} — {$disposal->method} ({$disposal->reference})"
                        : "Disposed {$node->asset_code} — {$disposal->method} ({$disposal->reference})",
                    'subtitle' => "Approval Status: {$statusLabel}",
                    'asset' => $node,
                    'transitions' => [],
                    'figures' => array_filter([
                        'Disposal Reference' => $disposal->reference,
                        'Asset' => ['asset' => $node],
                        'Method' => $disposal->method,
                        'Approval Status' => $statusLabel,
                        'Authorized By' => $disposal->authorized_by,
                    ]),
                    'comments' => $disposal->comments,
                    'document_url' => route('asset-disposals.print', $disposal),
                ];
            }
        }

        // 2. Modified events (upgrade / downgrade)
        foreach ($modifications as $mod) {
            $modDate = $mod->modification_date ? Carbon::parse($mod->modification_date)->setTime(12, 0, 0) : Carbon::today();
            $typeLabel = $mod->modification_type ? ucfirst($mod->modification_type) : 'Modification';

            $transitions = [];
            if ($mod->componentAsset || $mod->resultAsset) {
                $transitions[] = [
                    'kind' => 'combined',
                    'parent' => $mod->asset,
                    'component' => $mod->componentAsset,
                    'component_text' => $mod->added_asset,
                    'result' => $mod->resultAsset,
                ];
            }
            if ($mod->removedAsset && $mod->asset) {
                $transitions[] = [
                    'kind' => 'removed',
                    'from' => $mod->asset,
                    'to' => $mod->removedAsset,
                ];
            }

            $events[] = (object) [
                'type' => 'modified',
                'badge_class' => $mod->modification_type === 'upgrade' ? 'badge-success' : 'badge-secondary',
                'badge_label' => 'Modified — ' . $typeLabel,
                'date' => $mod->modification_date ?? '-',
                'sort_time' => $modDate->timestamp,
                'sort_seq' => 50 + (int) $mod->id,
                'title' => "Modified — {$typeLabel} ({$mod->reference})",
                'subtitle' => $mod->reverted_at ? 'Reverted on ' . $mod->reverted_at->format('Y-m-d H:i') : 'Asset Modification',
                'asset' => $mod->asset ?? $mod->resultAsset,
                'transitions' => $transitions,
                'figures' => array_filter([
                    'Modification ID' => $mod->reference,
                    'Type' => $typeLabel,
                    'Parent Asset' => $mod->asset ? [
                        'asset' => $mod->asset,
                        'suffix' => ' (LKR ' . number_format((float) ($mod->parent_original_cost ?? $mod->asset->cost), 2) . ')',
                    ] : null,
                    'Component Added' => $mod->componentAsset ? [
                        'asset' => $mod->componentAsset,
                        'suffix' => ' (LKR ' . number_format((float) ($mod->component_original_cost ?? $mod->componentAsset->cost), 2) . ')',
                    ] : $mod->added_asset,
                    'Component Removed' => $mod->removedAsset ? [
                        'asset' => $mod->removedAsset,
                        'suffix' => $mod->removed_price !== null ? ' (LKR ' . number_format((float) $mod->removed_price, 2) . ')' : '',
                    ] : ($mod->removed_asset ? $mod->removed_asset . ($mod->removed_price !== null ? ' (LKR ' . number_format((float) $mod->removed_price, 2) . ')' : '') : null),
                    'Resulting Asset' => $mod->resultAsset ? ['asset' => $mod->resultAsset] : null,
                    'New Value' => $mod->new_value !== null ? 'LKR ' . number_format((float) $mod->new_value, 2) : null,
                    'Authorized By' => $mod->authorized_by,
                ]),
                'comments' => $mod->comments,
            ];
        }

        // 3. Revalued events
        foreach ($revaluations as $reval) {
            $revalDate = $reval->revaluation_date ? $reval->revaluation_date->copy()->setTime(12, 0, 0) : Carbon::today();
            $transitions = [];
            if ($reval->asset && $reval->resultAsset) {
                $transitions[] = [
                    'kind' => 'revalued',
                    'from' => $reval->asset,
                    'to' => $reval->resultAsset,
                ];
            }

            $delta = (float) $reval->revalued_amount - (float) $reval->previous_value;
            $deltaFormatted = ($delta >= 0 ? '+' : '-') . 'LKR ' . number_format(abs($delta), 2);

            $events[] = (object) [
                'type' => 'revalued',
                'badge_class' => 'badge-secondary',
                'badge_label' => 'Revalued',
                'date' => $reval->revaluation_date ? $reval->revaluation_date->format('Y-m-d') : '-',
                'sort_time' => $revalDate->timestamp,
                'sort_seq' => 60 + (int) $reval->id,
                'title' => "Revalued: " . ($reval->asset?->asset_code ?? '') . ($reval->resultAsset ? " -> {$reval->resultAsset->asset_code}" : '') . " ({$reval->reference})",
                'subtitle' => $reval->reason ?: 'Asset Revaluation',
                'asset' => $reval->asset ?? $reval->resultAsset,
                'transitions' => $transitions,
                'figures' => array_filter([
                    'Revaluation ID' => $reval->reference,
                    'Predecessor Asset' => $reval->asset ? ['asset' => $reval->asset] : null,
                    'Successor Asset' => $reval->resultAsset ? ['asset' => $reval->resultAsset] : null,
                    'Previous Carrying Value' => 'LKR ' . number_format((float) $reval->previous_value, 2),
                    'Revalued Amount' => 'LKR ' . number_format((float) $reval->revalued_amount, 2) . " ({$deltaFormatted})",
                    'New Useful Life' => $reval->new_useful_life_months ? $reval->new_useful_life_months . ' months' : null,
                    'New Monthly Depreciation' => $reval->resultAsset?->monthly_depreciation !== null
                        ? 'LKR ' . number_format((float) $reval->resultAsset->monthly_depreciation, 2)
                        : null,
                    'Reason' => $reval->reason,
                    'Authorized By' => $reval->authorized_by,
                ]),
                'comments' => $reval->comments,
            ];
        }

        // Sort newest first (descending by sort_time, then descending by sort_seq)
        usort($events, function ($a, $b) {
            if ($a->sort_time !== $b->sort_time) {
                return $b->sort_time <=> $a->sort_time;
            }
            return $b->sort_seq <=> $a->sort_seq;
        });

        $chainById = $chain->keyBy('id');
        $currentAsset = $asset;
        $seenForward = [$currentAsset->id => true];
        while ($nextId = ($currentAsset->superseded_by_asset_id ?? $currentAsset->merged_into_asset_id)) {
            if (isset($seenForward[$nextId]) || ! $chainById->has($nextId)) {
                break;
            }
            $seenForward[$nextId] = true;
            $currentAsset = $chainById->get($nextId);
        }

        return view('assets.timeline', compact('asset', 'chain', 'currentAsset', 'events'));
    }
}
