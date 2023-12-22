<?php

namespace App;

class BugReport extends UuidModel
{
    protected $fillable = [
        'bug_json', 'user', 'examination'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function examination()
    {
        return $this->belongsTo('App\Examination', 'examination', 'uuid');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user', 'uuid');
    }
}
