<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'content',
        'media_path',
        'reported_at',
    ];

    public function employee(){
        return $this -> belongsTo(Employee::class);
    }
}
