<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
        try {
            $route = request()->route();

            if ($route === null) {
                return app()->getLocale() === 'en' ? $this->en_name : $value;
            }

            $routeAction = $route->getAction('as') ?? null;

            if ($routeAction === 'filament.admin.resources.statuses.edit') {
                return $value;
            } else {
                return app()->getLocale() === 'en' ? $this->en_name : $value;
            }
        } catch (\Exception $e) {
            return app()->getLocale() === 'en' ? $this->en_name : $value;
        }
    }

    public static function getCompletedStatusId()
    {
        return Cache::rememberForever('status.completed.id', function () {
            return self::whereName('Terminé')->first()->id;
        });
    }
}
