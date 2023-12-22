<?php

namespace App;

class Clinic extends UuidModel
{
    protected $fillable = [
        'country', 'description'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function services() {
        return $this->hasMany('App\Service', 'clinic_id', 'id');
    }
}
