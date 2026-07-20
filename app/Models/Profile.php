<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'phone_number',
        'birth_date',
        'salary',
        'job_position',
        'mbti_type',
        'is_subscribed',
        'subscription_plan',
        'subscription_price',
        'subscribed_at',
        'subscription_expires_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'salary' => 'decimal:2',
        'is_subscribed' => 'boolean',
        'subscribed_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
    ];

    public function hasActiveSubscription(): bool
    {
        return $this->is_subscribed
            && $this->subscription_expires_at !== null
            && $this->subscription_expires_at->isFuture();
    }

    public function getAgeAttribute()
    {
        return $this->birth_date
            ? $this->birth_date->age
            : null;
    }
}