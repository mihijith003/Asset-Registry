<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetSale extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'sale_date',
        'buyer_name',
        'buyer_contact',
        'price',
        'payment_method',
        'authorized_by',
        'comments', 
    ];

    /**
     * Display accessor that formats the id for humans (e.g. SAL-0001).
     */
    public function getReferenceAttribute(): string
    {
        return 'SAL-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
