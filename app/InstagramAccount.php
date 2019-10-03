<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InstagramAccount extends Model {
    protected $table = 'instagram_accounts';
    protected $fillable = [
        'telegram_user_id', 'username', 'password', 'cookie', 'paid_until'
    ];
    protected $hidden = [
        'password'
    ];
}
