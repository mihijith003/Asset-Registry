<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetRevaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'revaluation_date',
        'previous_value',
        'revalued_amount',
        'reason',
        'authorized_by',
        'comments',
    ];

    protected $casts = [
        'revaluation_date' => 'date',
        'previous_value' => 'float',
        'revalued_amount' => 'float',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Human-readable reference: REV-0001
     */
    public function getReferenceAttribute(): string
    {
        return 'REV-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }
}

