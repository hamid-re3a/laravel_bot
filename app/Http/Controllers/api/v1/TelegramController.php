<?php

namespace App\Http\Controllers\api\v1;


use App\InstagramAccount;
use App\TelegramUser;
use Carbon\Carbon;

class TelegramController extends ApiController {
    private static $s_init = "";
    private static $s_insta = "instagram";
    private static $s_insta_username = "instagram_username";
    private static $s_insta_password = "instagram_password";
    private static $s_insta_extend = "instagram_extend";

    private static $cmd_insta = "افزایش فالوور اینستاگرام";
    private static $cmd_insta_history = "تاریخچه";
    private static $cmd_insta_credit = "اعتبار باقیمانده";
    private static $cmd_insta_extend = "افزایش اعتبار";
    private static $cmd_sms = "پنل SMS";
    private static $cmd_help = "راهنما";
    private static $cmd_contact = "ارتباط با ادمین";
    private static $cmd_cancel = "انصراف";

    private static $init_buttons = [];
    private static $cancel_button = [];
    private static $insta_buttons = [];

    public function dokan() {
        try {
            TelegramController::$init_buttons  = [[TelegramController::$cmd_insta], [TelegramController::$cmd_sms],
                                                  [TelegramController::$cmd_help, TelegramController::$cmd_contact]];
            TelegramController::$cancel_button = [[TelegramController::$cmd_cancel]];
            TelegramController::$insta_buttons = [[TelegramController::$cmd_insta_history,
                                                   TelegramController::$cmd_insta_credit],
                                                  [TelegramController::$cmd_insta_extend,
                                                   TelegramController::$cmd_cancel]];

            $update   = @file_get_contents("php://input");
            $telegram = new TelegramSdk(env('TELEGRAM_DOKAN_API_KEY'));
            $telegram->intitilize($update);
//            $telegram->sendMessage(null, $update);

            switch ($telegram->type) {
                case "message":
                    $this->handleMessage($telegram);
                    break;
                case "callback_query":
                    $this->handleCallbackQuery($telegram);
                    break;
            }
        } catch (Exception $ex) {
            $telegram->sendMessage(null, "خطای نامشخص");
        } finally {
            return response()->json(['success' => true], 200);
        }
    }

    public function info() {
        $url = "https://api.telegram.org/bot" . env('TELEGRAM_DOKAN_API_KEY') . "/getMe";
        return @file_get_contents($url);
    }

    private function getTelegramUser($tel) {
        $tel_user = TelegramUser::where("telegram_id", $tel->chat_id)->first();
        if (is_null($tel_user)) {
            $tel_user              = new TelegramUser();
            $tel_user->telegram_id = $tel->chat_id;
            $tel_user->state       = TelegramController::$s_init;
            $tel_user->carry       = "";
        }
        $tel_user->username   = $tel->username;
        $tel_user->first_name = $tel->first_name;
        $tel_user->last_name  = $tel->last_name;
        $tel_user->save();
        return $tel_user;
    }

    private function resetTelegramUser($tel_user) {
        $tel_user->state = TelegramController::$s_init;
        $tel_user->carry = "";
        $tel_user->save();
    }

    private function handleMessage($tel) {
        $tel_user = $this->getTelegramUser($tel);
        // Stateless commands
        switch ($tel->message) {
            case TelegramController::$cmd_cancel:
                $this->resetTelegramUser($tel_user);
                $tel->sendKeyboardMessage(null, "فرآیند لغو شد.", TelegramController::$init_buttons);
                return;
            case "/debug":
                $tel->sendMessage(null, Carbon::now());
                return;
        }
        // Stateful commands
        switch ($tel_user->state) {
            case TelegramController::$s_init:
                $this->s_init($tel, $tel_user);
                break;
            case TelegramController::$s_insta:
            case TelegramController::$s_insta_username:
            case TelegramController::$s_insta_password:
                $this->s_insta($tel, $tel_user);
                break;
        }
    }

    private function handleCallbackQuery($tel) {
        $tel->sendMessage(null, "hello");
    }

    private function s_init($tel, $tel_user) {
        switch ($tel->message) {
            case "/start":
                $tel->sendKeyboardMessage(null, "به ربات تلگرام دکان خوش آمدید!", TelegramController::$init_buttons);
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
            case TelegramController::$cmd_insta:
                $instaAccounts = InstagramAccount::where("telegram_user_id", $tel_user->telegram_id)->get();
                if (count($instaAccounts) == 0) {
                    $tel_user->state = TelegramController::$s_insta_username;
                    $tel_user->save();
                    $tel->sendKeyboardMessage(null, "لطفاً نام کاربری اینستاگرام خود را وارد نمایید:",
                                              TelegramController::$cancel_button);
                } else {
                    $tel_user->state = TelegramController::$s_insta;
                    $tel_user->save();
                    $msg = "«« __سرویس افزایش فالوور اینستاگرام__ »»" . "\nیکی از موارد زیر را انتخاب نمایید:";
                    $msg .= "\n - " . TelegramController::$cmd_insta_history;
                    $msg .= "\n - " . TelegramController::$cmd_insta_credit;
                    $msg .= "\n - " . TelegramController::$cmd_insta_extend;
                    $tel->sendKeyboardMessage(null, $msg,
                                              TelegramController::$insta_buttons);
                }
                break;
        }
    }

    private function s_insta($tel, $tel_user) {
        // Handling intermediate states
        switch ($tel_user->state) {
            case TelegramController::$s_insta_username:
                $carry           = ["username" => $tel->message];
                $tel_user->carry = json_encode($carry);
                $tel_user->save();
                $tel->sendMessage(null, "لطفاً رمز عبور اینستاگرام خود را وارد نمایید:");
                $tel_user->state = TelegramController::$s_insta_password;
                $tel_user->save();
                return;
            case TelegramController::$s_insta_password:
                $carry                          = json_decode($tel_user->carry);
                $instaAccount                   = new InstagramAccount();
                $instaAccount->telegram_user_id = $tel->chat_id;
                $instaAccount->username         = $carry->username;
                $instaAccount->password         = $tel->message;
//                $instaAccount->paid_until       = Carbon::now();
                $instaAccount->save();
                $this->resetTelegramUser($tel_user);
                $tel->sendMessage(null, "اکانت اینستاگرام با موفقیت ثبت شد.");
                $tel->message = TelegramController::$cmd_insta;
                $this->s_init($tel, $tel_user);
                return;
        }
        // Handling commands
        switch ($tel->message) {
            case TelegramController::$cmd_insta_history:
                break;
            case TelegramController::$cmd_insta_credit:
                $account = InstagramAccount::where("telegram_user_id", $tel_user->telegram_id)->firstOrFail();
                $msg     = "حساب کاربری: " . $account->username
                           . "\nزمان پایان اعتبار: " . (is_null($account->paid_until) ? "-" : $account->paid_until);
                $tel->sendKeyboardMessage(null, $msg,
                                          TelegramController::$insta_buttons);
                break;
            case TelegramController::$cmd_insta_extend:
                $tel_user->state = TelegramController::$s_insta_extend;
                $tel_user->save();
                $msg = "لطفاً مبلغ " . "۴۰,۰۰۰"
                       . " تومان جهت تمدید حساب اینستاگرام به مدت یک ماه به شماره حساب زیر واریز نمایید"
                       . " و از صفحه پرداخت خود عکس گرفته و عکس را بفرستید."
                       . "\nxxxx-xxxx-xxxx-xxxx";
                $tel->sendKeyboardMessage(null, $msg,
                                          TelegramController::$cancel_button);
                break;
        }
    }
}
