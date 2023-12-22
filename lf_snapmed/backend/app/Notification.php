<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends UuidModel
{
    protected $fillable = [
        'type', 'med_user'
    ];

    protected $hidden = [
        'created_at', 'updated_at',
    ];


    public function physician()
    {
        return $this->belongsTo('App\MedUser', 'med_user', 'uuid');
    }

}
