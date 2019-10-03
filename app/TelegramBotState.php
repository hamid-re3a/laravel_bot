<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TelegramBotState extends Model {
    protected $table = 'telegram_bot_states';
    protected $fillable = [
        'chat_id', 'state', 'carry'
    ];
}
