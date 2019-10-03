<?php

namespace App\Http\Controllers\api\v1;


class TelegramController extends ApiController {
    public function dokan() {
        try {
            $update   = @file_get_contents("php://input");
            $telegram = new TelegramSdk(env('TELEGRAM_DOKAN_API_KEY'));
            $telegram->intitilize($update);

            switch ($telegram->type) {
                case "message":
                    $this->handleMessage($telegram);
                    break;
                case "callback_query":
                    $this->handleCallbackQuery($telegram);
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

    private function handleMessage($tel) {
        switch($tel->message) {
            case "/start":
                $tel->sendKeyboardMessage(null, "Welcome!",
                                          [["افزایش فالوور اینستاگرام"], ["پنل SMS"], ["راهنما", "ارتباط با ادمین"]]);
                break;
            case "/mirror":
                $info = [
                    "firstName" => $tel->first_name,
                    "lastName" => $tel->last_name,
                    "username" => $tel->username,
                    "phoneNumber" => $tel->phone_number,
                ];
                $tel->sendMessage(null, json_encode($info));
                break;
        }
    }

    private function handleCallbackQuery($tel) {
        $tel->sendMessage(null, "hello");
    }

}
