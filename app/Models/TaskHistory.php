<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskHistory extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'action',
        'parameters'
    ];

    protected $with = ['user'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
