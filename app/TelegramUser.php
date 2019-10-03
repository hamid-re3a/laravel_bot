<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model {
    protected $table = 'telegram_users';
    protected $fillable = [
        'telegram_id', 'name'
    ];
}
