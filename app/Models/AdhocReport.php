<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdhocReport extends Model
{
    use HasFactory;

    protected $table = 'adhoc_reports';

    protected $fillable = [
        'adhoc_report_name',
        'adhoc_report_description',
        'sql_query',
        'adhoc_report_created_date',
        'created_by',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'adhoc_report_created_date' => 'datetime',
        'created_by' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created the report.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
