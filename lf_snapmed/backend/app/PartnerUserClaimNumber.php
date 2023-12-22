<?php

namespace App;

class PartnerUserClaimNumber extends UuidModel
{
    protected $fillable = ['claimnumber', 'user_id', 'partner'];

    /**
     * Get the user that owns the phone.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'uuid', 'created_at', 'updated_at',
    ];
}
