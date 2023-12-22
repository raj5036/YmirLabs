<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Feedback extends UuidModel
{
    protected $fillable = [
        'feedback', 'user'
    ];

    protected $hidden = [
        'created_at', 'updated_at',
    ];
}
