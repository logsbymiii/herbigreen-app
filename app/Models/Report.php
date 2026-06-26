<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'type',
        'content',
        'media_path',
        'reported_at',
    ];

    public function employee(){
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
