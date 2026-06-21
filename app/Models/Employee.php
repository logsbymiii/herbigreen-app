<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $guarded = [];


    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function gmvReports()
    {
        return $this->hasMany(GmvReport::class);
    }

}
