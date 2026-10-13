<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NEW: a modification is now an upgrade or a downgrade, and a removed component is registered as an asset
// with a price so it can be used in future modifications.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_modifications', function (Blueprint $table) {
            // 'upgrade' | 'downgrade' | 'replacement' (worked out from the added vs. removed price)
            if (! Schema::hasColumn('asset_modifications', 'modification_type')) {
                $table->string('modification_type', 20)->nullable();
            }
            // The registry asset created (or restored) for the component that was removed.
            if (! Schema::hasColumn('asset_modifications', 'removed_asset_id')) {
                $table->unsignedInteger('removed_asset_id')->nullable();
            }
            // The price of the removed component.
            if (! Schema::hasColumn('asset_modifications', 'removed_price')) {
                $table->decimal('removed_price', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_modifications', function (Blueprint $table) {
            foreach (['removed_price', 'removed_asset_id', 'modification_type'] as $column) {
                if (Schema::hasColumn('asset_modifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
