<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_name',
        'team_name',
        'team_type',
        'organization_name',
        'phone',
        'email',
        'is_approved',
        'is_internal',
        'referral_code',
        'whitelabel_url',
        'registration_date',
        'ref',
        'notes',
        'settings',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_internal' => 'boolean',
        'registration_date' => 'date',
        'settings' => 'array',
    ];

    /**
     * Generate a unique referral code if not provided.
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }
}
