<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InstagramLog extends Model {
    protected $table    = 'isntagram_logs';
    protected $fillable = [
        'instagram_id', 'start_time'
    ];
}
