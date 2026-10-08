<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDisposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'disposal_id',
        'asset_id',
        'method',
        'disposal_date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
