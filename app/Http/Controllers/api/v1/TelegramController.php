<?php

namespace App\Http\Controllers\api\v1;



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
