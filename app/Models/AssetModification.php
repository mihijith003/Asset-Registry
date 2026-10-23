<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetModification extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'asset_id', 'component_asset_id', 'result_asset_id', 'modification_date',
        'removed_asset', 'added_asset', 'new_value', 'new_location', 'authorized_by', 'reverted_at',
        'parent_original_cost', 'component_original_cost', // NEW: costs saved at combine time, restored on revert
        'comments', // NEW: notes from the person recording the modification
        'modification_type', 'removed_asset_id', 'removed_price', // NEW: upgrade/downgrade + the removed component
    ];

    /**
     * Display accessor that formats the id for humans (e.g. MOD-0001).
     */
    public function getReferenceAttribute(): string
    {
        return 'MOD-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public const TYPES = ['upgrade', 'downgrade'];

    //lets $mod->reverted_at be used as a date.
    protected $casts = [
        'reverted_at' => 'datetime',
    ];

    /**
     * The asset that was modified (the "parent", e.g. the CPU).
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    /**
     * NEW: the component that was added to the parent (e.g. the motherboard).
     */
    public function componentAsset()
    {
        return $this->belongsTo(Asset::class, 'component_asset_id')->withTrashed();
    }

    /**
     * NEW: the combined asset created by this modification (gone once the modification is reverted).
     */
    public function resultAsset()
    {
        return $this->belongsTo(Asset::class, 'result_asset_id')->withTrashed();
    }

    /**
     * The asset created or restored for the component removed during this modification.
     */
    public function removedAsset()
    {
        return $this->belongsTo(Asset::class, 'removed_asset_id')->withTrashed();
    }
}
