<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model {
    protected $table = 'orders';
    protected $fillable = [
        'user_id', 'sms_receiver_id', 'shop_id', 'datetime', 'cost', 'duration', 'confirm', 'description'
    ];
}
