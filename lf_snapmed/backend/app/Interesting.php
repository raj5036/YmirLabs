<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Interesting extends Model
{

    protected $fillable = [
        'physician', 'examination', 'has_interest',
    ];

    public function examination()
    {
        return $this->belongsTo('App\Examination', 'examination', 'uuid');
    }

    public function physician()
    {
        return $this->belongsTo('App\MedUser', 'physician', 'uuid');
    }

}
