<?php

namespace App;

class Examination extends UuidModel
{
    const PAYMENT_TYPE_CARD = 'card';
    const PAYMENT_TYPE_PARTNER = 'partner';

    protected $fillable = [
        'child_ssn',
        'charged',
        'stripe',
        'patient',
        'payment_type',
        'who',
        'gender',
        'age',
        'pregnant',
        'breastfeeding',
        'category',
        'closeup',
        'overview',
        'idProof',
        'deadline',
        'deadline_time',
        'duration',
        'other_description',
        'body_part',
        'medication',
        'medication_description',
        'allergy',
        'allergy_description',
        'family_history',
        'family_history_description',
        'treatment',
        'treatment_description',
        'mole_size',
        'mole_symmetri',
        'mole_color',
        'mole_change',
        'mole_others',
        'mole_others_description',
        'mole_doctor',
        'mole_description',
        'rash_same',
        'rash_cold',
        'rash_drugs',
        'rash_drugs_description',
        'rash_doctor',
        'rash_description',
        'skin_cancer_change',
        'skin_cancer_change_description',
        'skin_cancer_size',
        'skin_cancer_doctor',
        'skin_cancer_description',
        'case_code',
        'partner_user_claim_number',
        'affiliate_partner',
        'amount_paid'
    ];

    protected $hidden = [
        'stripe', 'case_code'
    ];

    public function client()
    {
        return $this->belongsTo('App\User', 'patient', 'uuid');
    }

    public function partnerClaim()
    {
        return $this->belongsTo('App\PartnerUserClaimNumber', 'partner_user_claim_number', 'uuid');
    }

    public function diagnoses()
    {
        return $this->hasMany('App\Diagnosis', 'examination');
    }

    public function referrals()
    {
        return $this->hasMany('App\Referral', 'examination');
    }

    public function locked()
    {
        return $this->belongsTo('App\MedUser', 'locked_by', 'uuid');
    }

    public function closeups()
    {
        return $this->belongsToMany('App\Image', 'examinations_images', 'examination', 'image')
            ->wherePivot('type', 'closeup');
    }

    public function overviews()
    {
        return $this->belongsToMany('App\Image', 'examinations_images', 'examination', 'image')
            ->wherePivot('type', 'overview');
    }
}
