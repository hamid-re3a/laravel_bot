<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SmsReceiver extends Model {
    protected $table = 'sms_receivers';
    protected $fillable = [
        'telegram_user_id', 'mobile', 'name'
    ];
}
