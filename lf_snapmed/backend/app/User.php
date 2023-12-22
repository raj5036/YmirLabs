<?php

namespace App;

use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends UuidModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    const LOGIN_METHOD_PHONE = 'phone';
    const LOGIN_METHOD_BANKID = 'bankid';

    protected $fillable = [
        'phonenumber', 'email', 'password', 'ssn', 'ssn_hash', 'login_method', 'locale', 'region', 'partner', 'brand', 'token', 'firstname', 'lastname', 'date_of_birth', 'ethnicity', 'partner_policynumber', 'phonenumber_not_verified', 'otp_failed_count', 'password_updated_at, is_email_verified'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'uuid', 'password', 'ssn', 'login_method', 'otp', 'created_at', 'updated_at', 'phonenumber_not_verified', 'partner_policynumber', 'ssn_hash', 'otp_failed_count', 'password_updated_at', 'token', 'is_email_verified'
    ];

    public function examinations() {
        return $this->hasMany('App\Examination', 'patient', 'uuid');
    }

    public function claimnumbers() {
        return $this->hasMany('App\PartnerUserClaimNumber', 'user_id', 'uuid');
    }

    public function idProof()
    {
        return $this->belongsToMany('App\Image', 'users_images', 'user', 'image')
            ->wherePivot('type', 'idProof');
    }

}
