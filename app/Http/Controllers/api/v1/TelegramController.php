<?php

namespace App\Http\Controllers\api\v1;

use App\Reservina\Transformers\UserTransformer;
use App\User;
use Faker\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class TelegramController extends ApiController
{
    public function dokan()
    {
        $update = @file_get_contents("php://input");
        $telegram = new TelegramSdk(env('TELEGRAM_DOKAN_API_KEY'));
        $telegram->intitilize($update);

        $telegram->sendMessage($telegram->chat_id,"hello");



    }
}
