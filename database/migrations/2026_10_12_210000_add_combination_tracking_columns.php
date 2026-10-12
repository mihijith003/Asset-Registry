<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NEW: columns that let a modification create a brand-new "combined" asset and be reverted later.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // assets.merged_into_asset_id: set on the two ORIGINAL assets once they are combined,
        // pointing at the new combined asset. NULL = the asset is a normal (not merged-away) asset.
        if (! Schema::hasColumn('assets', 'merged_into_asset_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->unsignedInteger('merged_into_asset_id')->nullable()->after('cost')->index();
            });
        }

        Schema::table('asset_modifications', function (Blueprint $table) {
            // The component (e.g. the motherboard) that was added to asset_id (e.g. the CPU).
            if (! Schema::hasColumn('asset_modifications', 'component_asset_id')) {
                $table->unsignedInteger('component_asset_id')->nullable()->after('asset_id');
            }
            // The new combined asset that this modification created.
            if (! Schema::hasColumn('asset_modifications', 'result_asset_id')) {
                $table->unsignedInteger('result_asset_id')->nullable()->after('component_asset_id')->index();
            }
            // Set when the modification has been reverted (the combined asset is then deleted).
            if (! Schema::hasColumn('asset_modifications', 'reverted_at')) {
                $table->timestamp('reverted_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_modifications', function (Blueprint $table) {
            foreach (['reverted_at', 'result_asset_id', 'component_asset_id'] as $column) {
                if (Schema::hasColumn('asset_modifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('assets', 'merged_into_asset_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn('merged_into_asset_id');
            });
        }
    }
};
