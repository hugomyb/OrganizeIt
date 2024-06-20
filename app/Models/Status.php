<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'en_name', 'color'];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function getNameAttribute($value)
    {
        if (request()->route()->getAction('as') == 'filament.admin.resources.statuses.edit') {
            return $value;
        } else {
            return app()->getLocale() === 'en' ? $this->en_name : $value;
        }
    }
}
