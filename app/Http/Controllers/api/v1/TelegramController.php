<?php

namespace App\Http\Controllers\api\v1;


use App\InstagramAccount;
use App\TelegramUser;

class TelegramController extends ApiController {
    private static $states;

    public function dokan() {
        TelegramController::$states = [];
        TelegramController::$states->init = "";
        TelegramController::$states->instaUsername = "instagram_username";
        TelegramController::$states->instaPassword = "instagram_password";
        try {
            $update   = @file_get_contents("php://input");
            $telegram = new TelegramSdk(env('TELEGRAM_DOKAN_API_KEY'));
            $telegram->intitilize($update);
//            $telegram->sendMessage(null, $update);

            $telegram->sendMessage(null, json_encode(InstagramAccount::all()));

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
        $tel_user = TelegramUser::firstOrCreate(['telegram_id' => $tel->chat_id]);
        $tel_user->username = $tel->username;
        $tel_user->first_name = $tel->first_name;
        $tel_user->last_name = $tel->last_name;
        $tel_user->state = TelegramController::$states->init;
        $tel_user->carry = "";
        $tel_user->save();
        return $tel_user;
    }

    private function handleMessage($tel) {
        $tel_user = $this->getTelegramUser($tel);
        switch ($tel_user->state) {
            case TelegramController::$states->init:
                $this->s_idle($tel, $tel_user);
                break;
            case TelegramController::$states->instaUsername:
                $tel->sendMessage(null, "[DEBUG] In state instaUsername");
                $carry = ["username" => $tel->message];
                $tel_user->carry = json_encode($carry);
                $tel_user->save();
                $tel->sendMessage(null, "[DEBUG] Carry saved");
                $tel->sendMessage(null, "لطفاً رمز عبور اینستاگرام خود را وارد نمایید:");
                $tel_user->state = TelegramController::$states->instaPassword;
                $tel_user->save();
                break;
            case TelegramController::$states->instaPassword:
                $tel->sendMessage(null, "[DEBUG] In state instaPassword");
                $carry = json_decode($tel_user->carry);
                $tel->sendMessage(null, "[DEBUG] Carry decoded");
                $instaAccount = new InstagramAccount();
                $instaAccount->telegram_user_id = $tel->chat_id;
                $instaAccount->username = $carry->username;
                $instaAccount->password = $tel->message;
                $instaAccount->paid_until = date_default_timezone_get();
                $instaAccount->save();
                $tel->sendMessage(null, "[DEBUG] Insta account created");
                $tel_user->state = TelegramController::$states->init;
                $tel_user->save();
                $tel->message = "افزایش فالوور اینستاگرام";
                $this->s_idle($tel, $tel_user);
                break;
        }
    }

    private function handleCallbackQuery($tel) {
        $tel->sendMessage(null, "hello");
    }

    private function s_idle($tel, $tel_user) {
        $instaBtn = "افزایش فالوور اینستاگرام";
        $smsBtn = "پنل SMS";
        $helpBtn = "راهنما";
        $contactBtn = "ارتباط با ادمین";
        $btns = [[$instaBtn], [$smsBtn], [$helpBtn, $contactBtn]];
        switch ($tel->message) {
            case "/start":
                $tel->sendKeyboardMessage(null, "به ربات تلگرام دکان خوش آمدید!", $btns);
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
            case $instaBtn:
                $instaAccounts = InstagramAccount::where("telegram_user_id", $tel_user->telegram_id)->get();
                $tel->sendMessage(null, json_encode($instaAccounts));
                if (count($instaAccounts) == 0) {
                    $tel_user->state = TelegramController::$states->instaUsername;
                    $tel_user->save();
                    $tel->sendMessage(null, "لطفاً نام کاربری اینستاگرام خود را وارد نمایید:");
                } else
                    $tel->sendMessage(null, "You already have an instagram account");
                break;
        }
    }
}
