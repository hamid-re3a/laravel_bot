<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MoneyTransaction extends Model {
    protected $table = 'money_transactions';
    protected $fillable = [
        'telegram_user_id', 'amount', 'description', 'confirm'
    ];
}
