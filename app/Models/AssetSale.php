<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetSale extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'sales_ID',
        'asset_id',
        'sale_date',
        'buyer_name',
        'buyer_contact',
        'price',
        'authorized_by',
        'authorized_BY',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function setSaleIdAttribute($value)
    {
        $this->attributes['sales_ID'] = $value;
    }

    public function getSaleIdAttribute()
    {
        return $this->attributes['sales_ID'] ?? ($this->attributes['sale_id'] ?? null);
    }

    public function setAuthorizedByAttribute($value)
    {
        $this->attributes['authorized_BY'] = $value;
    }

    public function getAuthorizedByAttribute()
    {
        return $this->attributes['authorized_BY'] ?? ($this->attributes['authorized_by'] ?? null);
    }
}
