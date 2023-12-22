<?php

namespace App;

class Referral extends UuidModel
{
    protected $fillable = [
        'examination', 'diagnosis', 'name', 'type', 'suffix', 'size_in_kb'
    ];

    protected $hidden = [
        'examination', 'created_at', 'updated_at'
    ];

    public function examination()
    {
        return $this->belongsTo('App\Examination', 'examination', 'uuid');
    }

    public function diagnosis()
    {
        return $this->belongsTo('App\Diagnosis', 'diagnosis', 'uuid');
    }
}
