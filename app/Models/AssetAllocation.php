<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AssetAllocation extends Model
{
    public const TYPES = [
        'branch' => 'Branch',
        'employee' => 'Employee',
    ];

    protected $fillable = ['asset_id', 'allocation_type', 'allocated_to', 'allocated_by'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * e.g. "Branch: Chilaw" or "Employee: EMP-001"
     */
    public function getLabelAttribute(): string
    {
        return (self::TYPES[$this->allocation_type] ?? ucfirst($this->allocation_type)) . ': ' . $this->allocated_to;
    }
}
