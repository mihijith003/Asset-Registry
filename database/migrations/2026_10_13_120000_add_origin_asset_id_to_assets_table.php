<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NEW: lets a component that was removed from an asset be traced back to the asset it originally came with.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assets', 'origin_asset_id')) {
            Schema::table('assets', function (Blueprint $table) {
                // NULL for normal assets. For a removed component: the asset it originally came with.
                $table->unsignedInteger('origin_asset_id')->nullable()->after('merged_into_asset_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assets', 'origin_asset_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn('origin_asset_id');
            });
        }
    }
};
