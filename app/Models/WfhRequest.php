<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WfhRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'request_date',
        'reason',
        'status',
        'responded_at'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
