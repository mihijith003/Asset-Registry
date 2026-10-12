<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NEW: remembers the cost of both original assets at the moment they were combined, so that
// reverting the modification can restore their original prices exactly.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asset_modifications', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_modifications', 'parent_original_cost')) {
                // cost of the modified asset (e.g. the laptop) before it was combined
                $table->decimal('parent_original_cost', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('asset_modifications', 'component_original_cost')) {
                // cost of the added item (e.g. the RAM / motherboard) before it was combined
                $table->decimal('component_original_cost', 12, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_modifications', function (Blueprint $table) {
            foreach (['component_original_cost', 'parent_original_cost'] as $column) {
                if (Schema::hasColumn('asset_modifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
