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
    ];

    public function employee(){
        return $this->belongsTo(Employee::class);
    }
}
