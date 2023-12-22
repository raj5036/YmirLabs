<?php

namespace App;

use function GuzzleHttp\json_decode;

class Diagnosis extends UuidModel
{
    protected $fillable = [
        'category' , 'referral' , 'description', 'description_private', 'icd_codes', 'performed_by', 'examination', 'is_prescribed'
    ];

    protected $hidden = [
        'description_private', 'icd_codes'
    ];

    public function getIcdCodesAttribute($value)
    {
        if ($value) {
            return json_decode($value, true);
        }
        return null;
    }

    public function examination()
    {
        return $this->belongsTo('App\Examination', 'examination', 'uuid');
    }

    public function physician()
    {
        return $this->belongsTo('App\MedUser', 'performed_by', 'uuid');
    }

    public function referral()
    {
        return $this->hasMany('App\Referral', 'diagnosis');
    }
}
