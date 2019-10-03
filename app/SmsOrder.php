<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SmsOrder extends Model {
    protected $table = 'sms_orders';
    protected $fillable = [
        'user_id', 'transaction_id', 'number'
    ];
}
