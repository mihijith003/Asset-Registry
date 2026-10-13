<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NEW: optional free-text notes entered by the person recording a modification, sale or disposal.
return new class extends Migration
{
    private array $tables = ['asset_modifications', 'asset_sales', 'asset_disposals'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasColumn($table, 'comments')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->text('comments')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'comments')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('comments');
                });
            }
        }
    }
};
