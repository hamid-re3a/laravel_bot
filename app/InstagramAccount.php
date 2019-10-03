<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InstagramAccount extends Model {
    protected $table = 'instagram_accounts';
    protected $fillable = [
        'user_id', 'username', 'cookie', 'paid_until'
    ];
    protected $hidden = [
        'password'
    ];
}
