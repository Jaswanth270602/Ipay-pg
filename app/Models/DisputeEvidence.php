<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputeEvidence extends Model
{
    use HasFactory;

    protected $table = 'dispute_evidence';

    protected $fillable = [
        'dispute_id',
        'document_type',
        'file_name',
        'file_path',
        'file_url',
        'file_size',
        'mime_type',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    /**
     * Get the full URL for the file
     */
    public function getFileUrlAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Generate URL from file path
        if ($this->file_path && \Storage::disk('local')->exists($this->file_path)) {
            return \Storage::disk('local')->url($this->file_path);
        }

        return null;
    }
}

