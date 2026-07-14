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
        'mbti_type',
    ];
}
