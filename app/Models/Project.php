<?php

namespace App\Models;

use App\Filament\Resources\ProjectResource;
use App\Mail\AssignToProjectMail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Mail;
use Laravel\Scout\Searchable;

class Project extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    protected $fillable = ['name', 'color'];

    protected $appends = ['url'];

    protected $with = ['groups', 'users'];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user');
    }

    public function getUrlAttribute()
    {
        return ProjectResource::getUrl('show', ['record' => $this->id]);
    }
}
