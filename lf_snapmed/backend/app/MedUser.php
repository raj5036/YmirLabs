<?php

namespace App;

use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class MedUser extends UuidModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $fillable = [
        'display_name', 'phonenumber', 'email', 'password', 'otp_failed_count', 'servable_regions', 'active', 'superadmin', 'is_doctor', 'token', 'country', 'currency', 'is_email_verified', 'is_phone_verified'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'uuid', 'password', 'otp', 'created_at', 'updated_at',
    ];
}
