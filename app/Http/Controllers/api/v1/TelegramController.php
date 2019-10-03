<?php

namespace App\Http\Controllers\api\v1;


use App\TelegramUser;

class TelegramController extends ApiController {
    private static $idleState;

    public function dokan() {
        TelegramController::$idleState = "";
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

    private function getTelegramUser($tel) {
        $tel_user = new TelegramUser();
        try {
            $tel_user = TelegramUser::query()->when('telegram_id', $tel->chat_id)->firstOrFail();
        } catch (Exception $e) {
            $tel->sendMessage(null, "[DEBUG] TelegramUser fetch failed");
            $tel_user->telegram_id = $tel->chat_id;
            $tel_user->username    = $tel->username;
            $tel_user->first_name  = $tel->first_name;
            $tel_user->last_name   = $tel->last_name;
            $tel_user->state       = TelegramController::$idleState;
            $tel_user->carry       = "";
            $tel_user->save();
            $tel->sendMessage(null, "[DEBUG] TelegramUser saved");
        }
        return $tel_user;
    }

    private function handleMessage($tel) {
        $tel->sendMessage(null, "[DEBUG] handleMessage invoked");
        $tel_user = $this->getTelegramUser($tel);
        $tel->sendMessage(null, "[DEBUG] TelegramUser fetched");
        switch ($tel_user->state) {
            case TelegramController::$idleState:
                $this->s_idle($tel);
                break;
        }
    }

    private function handleCallbackQuery($tel) {
        $tel->sendMessage(null, "hello");
    }

    private function s_idle($tel) {
        $tel->sendMessage(null, "[DEBUG] In idle state");
        switch ($tel->message) {
            case "/start":
                $tel->sendKeyboardMessage(null, "به ربات تلگرام دکان خوش آمدید!",
                                          [["افزایش فالوور اینستاگرام"], ["پنل SMS"], ["راهنما", "ارتباط با ادمین"]]);
                break;
            case "/mirror":
                $info = [
                    "chatId" => $tel->chat_id,
                    "firstName" => $tel->first_name,
                    "lastName" => $tel->last_name,
                    "username" => $tel->username,
                    "phoneNumber" => $tel->phone_number,
                ];
                $tel->sendMessage(null, json_encode($info));
                break;
        }
    }
}
