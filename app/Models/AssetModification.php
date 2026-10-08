<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetModification extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'modification_id', 'asset_id', 'modification_date', 'removed_asset', 
        'added_asset', 'new_value', 'new_location', 'authorized_by'
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
