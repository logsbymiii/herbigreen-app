<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WfhRequest extends Model
{
    use SoftDeletes;

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
