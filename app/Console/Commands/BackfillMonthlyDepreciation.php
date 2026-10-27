<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetDepreciationLayer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillMonthlyDepreciation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:backfill-monthly-depreciation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill monthly_depreciation on asset_depreciation_layers and assets from (cost - COALESCE(residual_value, 0)) / useful_life_months';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updatedLayers = 0;
        $updatedAssets = 0;

        DB::transaction(function () use (&$updatedLayers, &$updatedAssets) {
            $assets = Asset::withTrashed()->with('layers')->get();

            foreach ($assets as $asset) {
                $assetResidual = (float) ($asset->residual_value ?? 0.0);
                $layers = $asset->layers;
                $totalLayerCost = (float) $layers->sum('cost');

                foreach ($layers as $layer) {
                    if ($layer->monthly_depreciation === null && $layer->useful_life_months !== null && (int) $layer->useful_life_months > 0) {
                        $layerCost = (float) $layer->cost;
                        // Apportion asset residual value across layers by cost (equals $assetResidual when there is 1 layer)
                        $layerResidual = ($layers->count() === 1)
                            ? $assetResidual
                            : ($totalLayerCost > 0 ? $assetResidual * ($layerCost / $totalLayerCost) : 0.0);

                        $depreciable = max(0.0, $layerCost - $layerResidual);
                        $monthly = round($depreciable / (int) $layer->useful_life_months, 2);

                        $layer->monthly_depreciation = $monthly;
                        $layer->save();
                        $updatedLayers++;
                    }
                }

                if ($asset->monthly_depreciation === null) {
                    $activeLayers = $layers->whereNull('end_date');
                    $referenceLayers = $activeLayers->isNotEmpty() ? $activeLayers : $layers;
                    $layersWithMonthly = $referenceLayers->whereNotNull('monthly_depreciation');

                    if ($layersWithMonthly->isNotEmpty()) {
                        $asset->monthly_depreciation = round((float) $layersWithMonthly->sum('monthly_depreciation'), 2);
                        if ($asset->useful_life_months === null) {
                            $asset->useful_life_months = $referenceLayers->whereNotNull('useful_life_months')->max('useful_life_months');
                        }
                        $asset->save();
                        $updatedAssets++;
                    } elseif ($asset->useful_life_months !== null && (int) $asset->useful_life_months > 0) {
                        $depreciable = max(0.0, (float) $asset->cost - $assetResidual);
                        $asset->monthly_depreciation = round($depreciable / (int) $asset->useful_life_months, 2);
                        $asset->save();
                        $updatedAssets++;
                    }
                }
            }

            // Also handle any orphan layers whose asset might not have been loaded
            $orphanLayers = AssetDepreciationLayer::whereNull('monthly_depreciation')
                ->whereNotNull('useful_life_months')
                ->where('useful_life_months', '>', 0)
                ->get();

            foreach ($orphanLayers as $layer) {
                $layer->monthly_depreciation = round(max(0.0, (float) $layer->cost) / (int) $layer->useful_life_months, 2);
                $layer->save();
                $updatedLayers++;
            }
        });

        $this->info("Backfill complete: {$updatedLayers} depreciation layer(s) and {$updatedAssets} asset(s) updated.");

        return self::SUCCESS;
    }
}

