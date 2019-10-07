<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderService extends Model {
    protected $table = 'orders';
    protected $fillable = [
        'order_id', 'service_id', 'count'
    ];
}
