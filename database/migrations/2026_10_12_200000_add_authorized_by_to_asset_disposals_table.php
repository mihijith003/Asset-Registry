<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NEW: stores which admin authorized each disposal (set from the admin-password popup).
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('asset_disposals', 'authorized_by')) {
            Schema::table('asset_disposals', function (Blueprint $table) {
                // Nullable so existing disposal rows (recorded before this change) stay valid.
                $table->string('authorized_by', 150)->nullable()->after('disposal_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('asset_disposals', 'authorized_by')) {
            Schema::table('asset_disposals', function (Blueprint $table) {
                $table->dropColumn('authorized_by');
            });
        }
    }
};
