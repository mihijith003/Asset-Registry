<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NEW: where an asset is allocated - either to a branch (e.g. "Chilaw") or to an employee (e.g. "EMP-001").
// One row per allocation; the newest row is the asset's CURRENT allocation, older rows are the history.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_allocations')) {
            return;
        }

        Schema::create('asset_allocations', function (Blueprint $table) {
            $table->id();
            // Same type as assets.id (the assets table has no foreign keys, so none here either).
            $table->unsignedInteger('asset_id')->index();
            $table->string('allocation_type', 20);   // 'branch' or 'employee'
            $table->string('allocated_to', 150);     // the typed-in branch name / employee number
            $table->string('allocated_by', 150)->nullable(); // name of the user who made the allocation
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_allocations');
    }
};
