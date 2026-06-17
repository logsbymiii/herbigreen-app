<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmartDailyReport extends Model
{
    protected $fillable = [
        'employee_id',
        'raw_report',
        'extracted_metrics',
        'ai_insight',
        'report_date',
    ];

    protected $casts = [
        'extracted_metrics' => 'array',
        'report_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
