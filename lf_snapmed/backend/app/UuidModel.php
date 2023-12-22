<?php

namespace App;

use Webpatser\Uuid\Uuid;

use Illuminate\Database\Eloquent\Model;

class UuidModel extends Model
{
    public $incrementing = false;
    protected $primaryKey = 'uuid';
    protected $keyType = 'char';

    /**
     *  Setup model event hooks
     */
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) Uuid::generate(4);
        });
    }

}
