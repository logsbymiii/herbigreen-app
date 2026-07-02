<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'type',
        'reason',
        'proof_path',
        'request_date',
        'status',
        'responded_at'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
