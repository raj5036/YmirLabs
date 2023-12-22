<?php

namespace App;

class Service extends UuidModel
{
    protected $fillable = [
        'clinic_id', 'name', 'code', 'duration', 'description'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'med_users', 'description', 'created_at', 'updated_at'
    ];

    public function clinic()
    {
        return $this->belongsTo('App\Clinic', 'clinic_id', 'id');
    }
}
