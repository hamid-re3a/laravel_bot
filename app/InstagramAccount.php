<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InstagramAccount extends Model {
    protected $table = 'instagram_accounts';
    protected $fillable = [
        'telegram_user_id', 'username', 'password', 'cookie', 'paid_until','comment','follow',
        'is_credentials_valid', 'is_two_step_verification_valid', 'user_pass_changed', 'two_step_verification_changed'
    ];
    // protected $hidden = [
    //     'password'
    // ];
}
