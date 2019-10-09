<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InstagramTransaction extends Model {
    protected $table = 'instagram_transactions';
    protected $fillable = [
        'telegram_user_id','instagram_id', 'amount', 'description', 'confirm', 'photo'
    ];
}
