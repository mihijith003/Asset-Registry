<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetModification extends Model
{
    use HasFactory;

    public $timestamps = false;

    // CHANGED: added component_asset_id, result_asset_id and reverted_at (see the
    // "combination tracking" migration).
    // protected $fillable = [
    //     'modification_id', 'asset_id', 'modification_date', 'removed_asset',
    //     'added_asset', 'new_value', 'new_location', 'authorized_by'
    // ];
    protected $fillable = [
        'modification_id', 'asset_id', 'component_asset_id', 'result_asset_id', 'modification_date',
        'removed_asset', 'added_asset', 'new_value', 'new_location', 'authorized_by', 'reverted_at',
        'parent_original_cost', 'component_original_cost', // NEW: costs saved at combine time, restored on revert
    ];

    // NEW: lets $mod->reverted_at be used as a date.
    protected $casts = [
        'reverted_at' => 'datetime',
    ];

    /**
     * The asset that was modified (the "parent", e.g. the CPU).
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * NEW: the component that was added to the parent (e.g. the motherboard).
     */
    public function componentAsset()
    {
        return $this->belongsTo(Asset::class, 'component_asset_id');
    }

    /**
     * NEW: the combined asset created by this modification (gone once the modification is reverted).
     */
    public function resultAsset()
    {
        return $this->belongsTo(Asset::class, 'result_asset_id');
    }
}
