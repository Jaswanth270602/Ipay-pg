<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatatableExport extends Model
{
    use HasFactory;

    protected $table = 'datatable_exports';

    protected $fillable = [
        'date_created',
        'page_category',
        'queue_status',
        'file_type',
        'downloadable_url',
        'expiry_time',
        'file_name',
        'file_path',
        'export_params',
        'created_by',
    ];

    protected $casts = [
        'date_created' => 'datetime',
        'expiry_time' => 'datetime',
        'export_params' => 'array',
        'created_by' => 'integer',
    ];

    /**
     * Check if the export file has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_time) {
            return false;
        }
        return now()->greaterThan($this->expiry_time);
    }

    /**
     * Check if the export is ready for download.
     */
    public function isReady(): bool
    {
        return $this->queue_status === 'completed' && 
               !empty($this->downloadable_url) && 
               !$this->isExpired();
    }

    /**
     * Get time remaining until expiry.
     */
    public function getTimeForExpiry(): ?string
    {
        if (!$this->expiry_time) {
            return null;
        }

        $now = now();
        if ($now->greaterThan($this->expiry_time)) {
            return 'Expired';
        }

        $diff = $now->diff($this->expiry_time);
        
        if ($diff->days > 0) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ' . $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        } else {
            return $diff->s . ' second' . ($diff->s > 1 ? 's' : '');
        }
    }
}
