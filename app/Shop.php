<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model {
    protected $table = 'shops';
    protected $fillable = [
        'telegram_user_id', 'name', 'address', 'latitude', 'longitude', 'picture', 'description'
    ];
}
