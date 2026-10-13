<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// NEW: straight-line monthly depreciation.
//  - asset_sub_types.useful_life_years: the useful life used for assets of that sub type (NULL = not depreciated).
//  - asset_depreciation_layers: every asset's cost is held in "layers", each with its own start date and life, so an
//    asset made by combining assets registered on different dates keeps each part's own age.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('asset_sub_types', 'useful_life_years')) {
            Schema::table('asset_sub_types', function (Blueprint $table) {
                $table->unsignedSmallInteger('useful_life_years')->nullable();
            });

            // Starting values (editable on the Depreciation page): electronics 5 years, furniture 3 years.
            foreach (['Electronics' => 5, 'Furniture' => 3] as $typeName => $years) {
                DB::table('asset_sub_types')
                    ->whereIn('asset_type_id', DB::table('asset_types')->where('type_name', $typeName)->pluck('id'))
                    ->update(['useful_life_years' => $years]);
            }
        }

        if (! Schema::hasTable('asset_depreciation_layers')) {
            Schema::create('asset_depreciation_layers', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('asset_id')->index();
                // The registered asset this layer of cost originally came from (used to decide which layers a
                // removed component is taken out of).
                $table->unsignedInteger('source_asset_id')->nullable();
                $table->decimal('cost', 12, 2);
                $table->date('start_date');
                $table->unsignedInteger('useful_life_months')->nullable(); // NULL = not depreciated
                $table->timestamps();
            });

            $this->backfill();
        }
    }

    /**
     * Give every existing asset its layers. A normal asset gets one layer starting on its registration date.
     * A combined asset gets the layers of the assets it was made from (scaled so they add up to its cost).
     * Assets are processed oldest first, so the parts of a combined asset always have their layers already.
     */
    private function backfill(): void
    {
        $lives = DB::table('asset_sub_types')->pluck('useful_life_years', 'id');
        $now = now();

        foreach (DB::table('assets')->orderBy('id')->get() as $asset) {
            $children = DB::table('assets')->where('merged_into_asset_id', $asset->id)->pluck('id');
            $parts = $children->isEmpty()
                ? collect()
                : DB::table('asset_depreciation_layers')->whereIn('asset_id', $children)->get();

            if ($parts->isEmpty()) {
                $years = $lives[$asset->asset_sub_type_id] ?? null;

                DB::table('asset_depreciation_layers')->insert([
                    'asset_id' => $asset->id,
                    'source_asset_id' => $asset->id,
                    'cost' => $asset->cost,
                    'start_date' => substr($asset->created_at ?? $now->toDateString(), 0, 10),
                    'useful_life_months' => $years ? $years * 12 : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                continue;
            }

            $partsTotal = (float) $parts->sum('cost');
            $factor = $partsTotal > 0 ? (float) $asset->cost / $partsTotal : 0;

            foreach ($parts as $part) {
                DB::table('asset_depreciation_layers')->insert([
                    'asset_id' => $asset->id,
                    'source_asset_id' => $part->source_asset_id,
                    'cost' => round($part->cost * $factor, 2),
                    'start_date' => $part->start_date,
                    'useful_life_months' => $part->useful_life_months,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_layers');

        if (Schema::hasColumn('asset_sub_types', 'useful_life_years')) {
            Schema::table('asset_sub_types', function (Blueprint $table) {
                $table->dropColumn('useful_life_years');
            });
        }
    }
};
