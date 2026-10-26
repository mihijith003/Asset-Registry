<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDisposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'method',
        'disposal_date',
        'authorized_by', 
        'comments', 
        'approval_status',
    ];

    /**
     * Display accessor that formats the id for humans (e.g. DIS-0001).
     */
    public function getReferenceAttribute(): string
    {
        return 'DIS-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * The reason an admin gave when rejecting this claim (stored in comments after "REJECTED: "), else null.
     */
    public function getRejectionReasonAttribute(): ?string
    {
        if ($this->approval_status !== 'rejected' || ! $this->comments) {
            return null;
        }

        $pos = strrpos($this->comments, 'REJECTED: ');

        return $pos === false ? null : trim(substr($this->comments, $pos + strlen('REJECTED: ')));
    }

    /**
     * The recorder's own comments, without an appended rejection reason.
     */
    public function getNotesAttribute(): ?string
    {
        if ($this->approval_status !== 'rejected' || ! $this->comments) {
            return $this->comments;
        }

        $pos = strrpos($this->comments, 'REJECTED: ');

        return $pos === false ? $this->comments : (trim(substr($this->comments, 0, $pos)) ?: null);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }
}
