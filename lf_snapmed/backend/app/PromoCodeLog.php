<?php

namespace App;

use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class PromoCodeLog extends UuidModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $fillable = [
        'user_id', 'promo_id', 'promocode', 'discount_value', 'discount_fixed', 'initial_amount', 'amount_charged', 'currency', 'service', 'deadline_chat'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'uuid', 'created_at', 'updated_at',
    ];
}
