<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// NEW: a slice of an asset's cost with its own start date and useful life (see App\Support\Depreciation).
class AssetDepreciationLayer extends Model
{
    protected $fillable = ['asset_id', 'source_asset_id', 'cost', 'start_date', 'end_date', 'useful_life_months'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
