<?php

namespace App;

use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class PromoCode extends UuidModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $fillable = [
        'promocode', 'description','discount_fixed', 'applicable_currencies', 'discount_12hrs', 'discount_24hrs', 'discount_video', 'valid_from', 'valid_to', 'one_time_code', 'reusable'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'uuid', 'used_up', 'created_at', 'updated_at',
    ];
}
