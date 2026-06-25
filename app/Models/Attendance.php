<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'type',
        'note',
        'proof_path',
        'date',
        'latitude',
        'longitude',
        'clocked_in_at',
        'clocked_out_at',
    ];

    public function employee(){
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
