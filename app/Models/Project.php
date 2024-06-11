<?php

namespace App\Models;

use App\Mail\AssignToProjectMail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color'];

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
}
