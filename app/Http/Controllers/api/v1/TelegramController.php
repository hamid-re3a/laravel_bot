<?php

namespace App\Http\Controllers\api\v1;


class TelegramController extends ApiController {
    public function dokan() {
        try {
            $update   = @file_get_contents("php://input");
            $telegram = new TelegramSdk(env('TELEGRAM_DOKAN_API_KEY'));
            $telegram->intitilize($update);

            switch($telegram->type) {
                case "message":
                    $telegram->sendMessage(null, $telegram->message);
                    if ($telegram->message == "/start")
                        $telegram->sendKeyboardMessage(null, "Welcome!",
                                                       [["افزایش فالوور اینستاگرام"], ["پنل SMS"], ["راهنما", "ارتباط با ادمین"]]);
                    break;
                case "callback_query":
                    $telegram->sendMessage(null, "hello");
                    break;
            }
        } finally {
            return response()->json(['success' => true], 200);
        }
    }

    public function info() {
        $url = "https://api.telegram.org/bot" . env('TELEGRAM_DOKAN_API_KEY') . "/getMe";
        return @file_get_contents($url);
    }

}
