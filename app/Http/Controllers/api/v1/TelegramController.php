<?php

namespace App\Http\Controllers\api\v1;


use App\InstagramAccount;
use App\SmsReceiver;
use App\TelegramUser;
use Carbon\Carbon;

class TelegramController extends ApiController {
    private static $state_init                    = "";
    private static $state_insta                   = "instagram";
    private static $state_insta_username          = "instagram_username";
    private static $state_insta_password          = "instagram_password";
    private static $state_insta_extend            = "instagram_extend";
    private static $state_sms                     = "sms";
    private static $state_sms_contacts            = "sms_contacts";
    private static $state_sms_contacts_new_name   = "sms_contacts_new_name";
    private static $state_sms_contacts_new_mobile = "sms_contacts_new_mobile";
    private static $state_sms_contacts_remove     = "sms_contacts_remove";

    private static $cmd_insta               = "سرویس افزایش فالوور اینستاگرام";
    private static $cmd_insta_history       = "تاریخچه";
    private static $cmd_insta_credit        = "اعتبار باقیمانده";
    private static $cmd_insta_extend        = "افزایش اعتبار";
    private static $cmd_sms                 = "سرویس ارسال SMS";
    private static $cmd_sms_contacts        = "لیست مشتریان";
    private static $cmd_sms_sendToClients   = "ارسال گروهی برای مشتریان";
    private static $cmd_sms_sendToNear      = "ارسال گروهی برای منطقه";
    private static $cmd_sms_contacts_new    = "افزودن مشتری";
    private static $cmd_sms_contacts_remove = "حذف مشتری";
    private static $cmd_help                = "راهنما";
    private static $cmd_contact             = "ارتباط با ادمین";
    private static $cmd_cancel              = "انصراف";

    private static $btn_init         = [];
    private static $btn_cancel       = [];
    private static $btn_insta        = [];
    private static $btn_sms          = [];
    private static $btn_sms_contacts = [];

    public function dokan() {
        try {
            TelegramController::$btn_init         = [[TelegramController::$cmd_insta],
                                                     [TelegramController::$cmd_sms],
                                                     [TelegramController::$cmd_help, TelegramController::$cmd_contact]];
            TelegramController::$btn_cancel       = [[TelegramController::$cmd_cancel]];
            TelegramController::$btn_insta        = [[TelegramController::$cmd_insta_history,
                                                      TelegramController::$cmd_insta_credit],
                                                     [TelegramController::$cmd_insta_extend,
                                                      TelegramController::$cmd_cancel]];
            TelegramController::$btn_sms          = [[TelegramController::$cmd_sms_contacts,
                                                      TelegramController::$cmd_sms_sendToClients],
                                                     [TelegramController::$cmd_sms_sendToNear,
                                                      TelegramController::$cmd_cancel]];
            TelegramController::$btn_sms_contacts = [[TelegramController::$cmd_sms_contacts_new,
                                                      TelegramController::$cmd_sms_contacts_remove],
                                                     [TelegramController::$cmd_cancel]];

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
            $tel_user->state       = TelegramController::$state_init;
            $tel_user->carry       = "";
        }
        $tel_user->username   = $tel->username;
        $tel_user->first_name = $tel->first_name;
        $tel_user->last_name  = $tel->last_name;
        $tel_user->save();
        return $tel_user;
    }

    private function resetTelegramUser($tel_user) {
        $tel_user->state = TelegramController::$state_init;
        $tel_user->carry = "";
        $tel_user->save();
    }

    private function handleMessage($tel) {
        $tel_user = $this->getTelegramUser($tel);
        // Stateless commands
        switch ($tel->message) {
            case TelegramController::$cmd_cancel:
                $btn = $tel_user->state == TelegramController::$state_sms_contacts
                    ? TelegramController::$btn_sms : TelegramController::$btn_init;
                if (tel_user->state != TelegramController::$state_sms_contacts)
                    $this->resetTelegramUser($tel_user);
                $tel->sendKeyboardMessage(null, "فرآیند لغو شد.", $btn);
                return;
            case "/debug":
                $tel->sendMessage(null, Carbon::now());
                return;
        }
        // Stateful commands
        switch ($tel_user->state) {
            case TelegramController::$state_init:
                $this->state_init($tel, $tel_user);
                break;
            case TelegramController::$state_insta:
            case TelegramController::$state_insta_username:
            case TelegramController::$state_insta_password:
            case TelegramController::$state_insta_extend:
                $this->state_insta($tel, $tel_user);
                break;
            case TelegramController::$state_sms:
            case TelegramController::$state_sms_contacts:
            case TelegramController::$state_sms_contacts_new_name:
            case TelegramController::$state_sms_contacts_new_mobile:
            case TelegramController::$state_sms_contacts_remove:
                $this->state_sms($tel, $tel_user);
                break;
        }
    }

    private function handleCallbackQuery($tel) {
        $tel->sendMessage(null, "خطای نامشخص");
    }

    private function state_init($tel, $tel_user) {
        switch ($tel->message) {
            case "/start":
                $tel->sendKeyboardMessage(null, "به ربات تلگرام دکان خوش آمدید!", TelegramController::$btn_init);
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
                    $tel_user->state = TelegramController::$state_insta_username;
                    $tel_user->save();
                    $tel->sendKeyboardMessage(null, "لطفاً نام کاربری اینستاگرام خود را وارد نمایید:",
                                              TelegramController::$btn_cancel);
                } else {
                    $tel_user->state = TelegramController::$state_insta;
                    $tel_user->save();
                    $msg = "«« سرویس افزایش فالوور اینستاگرام »»" . "\nیکی از موارد زیر را انتخاب نمایید:";
                    $msg .= "\n • " . TelegramController::$cmd_insta_history;
                    $msg .= "\n • " . TelegramController::$cmd_insta_credit;
                    $msg .= "\n • " . TelegramController::$cmd_insta_extend;
                    $msg .= "\n.";
                    $tel->sendKeyboardMessage(null, $msg,
                                              TelegramController::$btn_insta);
                }
                break;
            case TelegramController::$cmd_sms:
                $tel_user->state = TelegramController::$state_sms;
                $tel_user->save();
                $msg = "«« سرویس ارسال SMS »»" . "\nیکی از موارد زیر را انتخاب نمایید:";
                $msg .= "\n • " . TelegramController::$cmd_sms_contacts;
                $msg .= "\n • " . TelegramController::$cmd_sms_sendToClients;
                $msg .= "\n • " . TelegramController::$cmd_sms_sendToNear;
                $msg .= "\n.";
                $tel->sendKeyboardMessage(null, $msg,
                                          TelegramController::$btn_sms);
                break;
            case TelegramController::$cmd_help:
                $msg = "به ربات تلگرام دکان خوش آمدید" . "\n"
                       . "این ربات با ارائه خدمات افزایش فالوورهای اینستاگرام و ارسال دسته جمعی SMS به منظور افزایش بهره‌وری کسب و کارها طراحی شده است.";
                $tel->sendMessage(null, $msg);
                break;
            case TelegramController::$cmd_contact:
                $msg = "شما می‌توانید از طریق آدرس زیر با مدیران دکان ارتباط برقرار نمایید:"
                       . "\n" . "@Hamidre3a";
                $tel->sendMessage(null, $msg);
                break;
            default:
                $tel->sendKeyboardMessage(null, "پیام قابل فهم نیست.", TelegramController::$btn_init);
        }
    }

    private function state_insta($tel, $tel_user) {
        // Handling intermediate states
        switch ($tel_user->state) {
            case TelegramController::$state_insta_username:
                $carry           = ["username" => $tel->message];
                $tel_user->carry = json_encode($carry);
                $tel_user->save();
                $tel->sendMessage(null, "لطفاً رمز عبور اینستاگرام خود را وارد نمایید:");
                $tel_user->state = TelegramController::$state_insta_password;
                $tel_user->save();
                return;
            case TelegramController::$state_insta_password:
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
                $this->state_init($tel, $tel_user);
                return;
            case TelegramController::$state_insta_extend:
                $tel->sendMessage(null, "هنوز پیاده‌سازی نشده است.");
                return;
        }
        // Handling commands
        switch ($tel->message) {
            case TelegramController::$cmd_insta_history:
                $tel->sendMessage(null, "هنوز پیاده‌سازی نشده است.");
                break;
            case TelegramController::$cmd_insta_credit:
                $account = InstagramAccount::where("telegram_user_id", $tel_user->telegram_id)->firstOrFail();
                $msg     = "حساب کاربری: " . $account->username
                           . "\nزمان پایان اعتبار: " . (is_null($account->paid_until) ? "-" : $account->paid_until);
                $tel->sendKeyboardMessage(null, $msg,
                                          TelegramController::$btn_insta);
                break;
            case TelegramController::$cmd_insta_extend:
                $tel_user->state = TelegramController::$state_insta_extend;
                $tel_user->save();
                $msg = "لطفاً مبلغ " . "۴۰,۰۰۰"
                       . " تومان جهت تمدید حساب اینستاگرام به مدت یک ماه به شماره حساب زیر واریز نمایید"
                       . " و از صفحه پرداخت خود عکس گرفته و عکس را بفرستید."
                       . "\nxxxx-xxxx-xxxx-xxxx";
                $tel->sendKeyboardMessage(null, $msg,
                                          TelegramController::$btn_cancel);
                break;
            default:
                $tel->sendKeyboardMessage(null, "پیام قابل فهم نیست.", TelegramController::$btn_insta);
        }
    }

    private function state_sms($tel, $tel_user) {
        // Handling intermediate states
        switch ($tel_user->state) {
            case TelegramController::$state_sms_contacts_new_name:
                $carry           = ["name" => $tel->message];
                $tel_user->carry = json_encode($carry);
                $tel_user->save();
                $tel->sendMessage(null, "لطفاً شماره همراه مشتری را وارد نمایید:");
                $tel_user->state = TelegramController::$state_sms_contacts_new_mobile;
                $tel_user->save();
                return;
            case TelegramController::$state_sms_contacts_new_mobile:
                $carry                         = json_decode($tel_user->carry);
                $smsReceiver                   = new SmsReceiver();
                $smsReceiver->telegram_user_id = $tel->chat_id;
                $smsReceiver->name             = $carry->name;
                $smsReceiver->mobile           = $tel->message;
                $smsReceiver->save();
                $this->resetTelegramUser($tel_user);
                $tel_user->state = TelegramController::$state_sms;
                $tel_user->save();
                $tel->sendKeyboardMessage(null, "مشتری با موفقیت ثبت شد.",
                                          TelegramController::$btn_sms);
                return;
            case TelegramController::$state_sms_contacts_remove:
                $contact = SmsReceiver::where('mobile', $tel->message)->first();
                if (is_null($contact)) {
                    $msg = "مشتری مورد نظر یافت نشد.";
                } else {
                    $contact->delete();
                    $msg = "مشتری مورد نظر حذف.";
                }
                $this->resetTelegramUser($tel_user);
                $tel_user->state = TelegramController::$state_sms;
                $tel_user->save();
                $tel->sendKeyboardMessage(null, $msg,
                                          TelegramController::$btn_sms);
                return;
        }
        // Handling commands
        switch ($tel->message) {
            case TelegramController::$cmd_sms_contacts:
                $contacts = SmsReceiver::where('telegram_user_id', $tel_user->telegram_id)->get();
                if (count($contacts) > 0) {
                    $msg = "لیست مشتریان:";
                    foreach ($contacts as $contact)
                        $msg .= "\n" . $contact->name . ": " . $contact->mobile;
                } else
                    $msg = "هنوز مشتری‌ای ثبت نشده است.";
                $tel->sendKeyboardMessage(null, $msg,
                                          TelegramController::$btn_sms_contacts);
                $tel_user->state = TelegramController::$state_sms_contacts;
                $tel_user->save();
                break;
            case TelegramController::$cmd_sms_sendToClients:
                $tel->sendMessage(null, "هنوز پیاده‌سازی نشده است.");
                break;
            case TelegramController::$cmd_sms_sendToNear:
                $tel->sendMessage(null, "هنوز پیاده‌سازی نشده است.");
                break;
            case TelegramController::$cmd_sms_contacts_new:
                $tel_user->state = TelegramController::$state_sms_contacts_new_name;
                $tel_user->save();
                $tel->sendKeyboardMessage(null, "لطفاً نام مشتری را وارد نمایید:",
                                          TelegramController::$btn_cancel);
                break;
            case TelegramController::$cmd_sms_contacts_remove:
                $tel_user->state = TelegramController::$state_sms_contacts_remove;
                $tel_user->save();
                $tel->sendKeyboardMessage(null, "لطفاً شماره همراه مشتری را وارد نمایید:",
                                          TelegramController::$btn_cancel);
                break;
            default:
                $tel->sendKeyboardMessage(null, "پیام قابل فهم نیست.", TelegramController::$btn_sms);
        }
    }
}
