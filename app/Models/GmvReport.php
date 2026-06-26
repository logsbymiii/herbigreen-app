<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GmvReport extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
