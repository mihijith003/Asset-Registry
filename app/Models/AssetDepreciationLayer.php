<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AssetDepreciationLayer extends Model
{
    protected $fillable = ['asset_id', 'source_asset_id', 'cost', 'start_date', 'end_date', 'useful_life_months', 'monthly_depreciation'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
