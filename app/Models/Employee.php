<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];


    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function smartDailyReports()
    {
        return $this->hasMany(SmartDailyReport::class);
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
