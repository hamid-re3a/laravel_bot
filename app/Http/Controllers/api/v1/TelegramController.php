<?php

namespace App\Http\Controllers\api\v1;


use App\InstagramAccount;
use App\InstagramTransaction;
use App\SmsReceiver;
use App\TelegramUser;
use Carbon\Carbon;

class TelegramController extends ApiController {
    private static $state_init                    = "";
    private static $state_insta                   = "instagram";
    private static $state_insta_username          = "instagram_username";
    private static $state_insta_password          = "instagram_password";
    private static $state_insta_update            = "instagram_update";
    private static $state_insta_update_username   = "instagram_update_username";
    private static $state_insta_update_password   = "instagram_update_password";
    private static $state_insta_update_comment    = "instagram_update_comment";
    private static $state_insta_update_follow     = "instagram_update_follow";
    private static $state_insta_extend            = "instagram_extend";
    private static $state_sms                     = "sms";
    private static $state_sms_contacts            = "sms_contacts";
    private static $state_sms_contacts_new_name   = "sms_contacts_new_name";
    private static $state_sms_contacts_new_mobile = "sms_contacts_new_mobile";
    private static $state_sms_contacts_remove     = "sms_contacts_remove";

    private static $cmd_insta                 = "سرویس افزایش فالوور اینستاگرام";
    private static $cmd_insta_detail          = "جزئیات";
    private static $cmd_insta_update          = "اصلاح مشخصات";
    private static $cmd_insta_extend          = "افزایش اعتبار";
    private static $cmd_insta_update_username = "نام کاربری";
    private static $cmd_insta_update_password = "رمز عبور";
    private static $cmd_insta_update_comment  = "امکان کامنت‌گذاری";
    private static $cmd_insta_update_follow   = "امکان فالو کردن";
    private static $cmd_sms                   = "سرویس ارسال SMS";
    private static $cmd_sms_contacts          = "لیست مشتریان";
    private static $cmd_sms_sendToClients     = "ارسال گروهی برای مشتریان";
    private static $cmd_sms_sendToNear        = "ارسال گروهی برای منطقه";
    private static $cmd_sms_contacts_new      = "افزودن مشتری";
    private static $cmd_sms_contacts_remove   = "حذف مشتری";
    private static $cmd_help                  = "راهنما";
    private static $cmd_contact               = "ارتباط با ادمین";
    private static $cmd_cancel                = "انصراف";
    private static $cmd_active                = "فعال";
    private static $cmd_deactive              = "غیرفعال";

    private static $cmd_instagramTransactionConfirm = "instagramTransactionConfirm";
    private static $cmd_instagramTransactionDeny    = "instagramTransactionDeny";

    private static $btn_init         = [];
    private static $btn_cancel       = [];
    private static $btn_active       = [];
    private static $btn_insta        = [];
    private static $btn_insta_update = [];
    private static $btn_sms          = [];
    private static $btn_sms_contacts = [];

    private static $admin_chat_id = "67015729";

    public function dokan() {
        $update = @file_get_contents("php://input");
        $telegram = new TelegramSdk(env('TELEGRAM_DOKAN_API_KEY'));
        $telegram->intitilize($update);

        try {
            TelegramController::$btn_init = [[TelegramController::$cmd_insta],
                                             [TelegramController::$cmd_help, TelegramController::$cmd_contact]];
            TelegramController::$btn_cancel = [[TelegramController::$cmd_cancel]];
            TelegramController::$btn_active = [[TelegramController::$cmd_active,
                                                TelegramController::$cmd_deactive],
                                               [TelegramController::$cmd_cancel]];
            TelegramController::$btn_insta = [[TelegramController::$cmd_insta_detail,
                                               TelegramController::$cmd_insta_update],
                                              [TelegramController::$cmd_insta_extend,
                                               TelegramController::$cmd_cancel]];
            TelegramController::$btn_insta_update = [[TelegramController::$cmd_insta_update_username,
                                                      TelegramController::$cmd_insta_update_password],
                                                     [TelegramController::$cmd_insta_update_comment,
                                                      TelegramController::$cmd_insta_update_follow],
                                                     [TelegramController::$cmd_cancel]];
            TelegramController::$btn_sms = [[TelegramController::$cmd_sms_contacts,
                                             TelegramController::$cmd_sms_sendToClients],
                                            [TelegramController::$cmd_sms_sendToNear,
                                             TelegramController::$cmd_cancel]];
            TelegramController::$btn_sms_contacts = [[TelegramController::$cmd_sms_contacts_new,
                                                      TelegramController::$cmd_sms_contacts_remove],
                                                     [TelegramController::$cmd_cancel]];

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
            $tel_user = TelegramUser::create(["telegram_id", $tel->chat_id]);
            //$tel_user->telegram_id = $tel->chat_id;
            $tel_user->state = TelegramController::$state_init;
            $tel_user->carry = "";
        }
        $tel_user->username = $tel->username;
        $tel_user->first_name = $tel->first_name;
        $tel_user->last_name = $tel->last_name;
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
//            case TelegramController::$cmd_cancel:
//                $btn = $tel_user->state == TelegramController::$state_sms_contacts
//                    ? TelegramController::$btn_sms : TelegramController::$btn_init;
//                if ($tel_user->state != TelegramController::$state_sms_contacts)
//                    $this->resetTelegramUser($tel_user);
//                else {
//                    $tel_user->state = TelegramController::$state_sms;
//                    $tel_user->save();
//                }
//                $tel->sendKeyboardMessage(null, "فرآیند لغو شد.", $btn);
//                return;
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
            case TelegramController::$state_insta_update:
            case TelegramController::$state_insta_update_username:
            case TelegramController::$state_insta_update_password:
            case TelegramController::$state_insta_update_comment:
            case TelegramController::$state_insta_update_follow:
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
        preg_match('/(?P<command>\w+)@(?P<payload>\d+)/', $tel->callback_data, $matches);
        $command = isset($matches["command"]) ? $matches["command"] : null;
        $payload = isset($matches["payload"]) ? $matches["payload"] : null;
        if (isset($command)) {
            switch ($command) {
                case TelegramController::$cmd_instagramTransactionConfirm:
                    $trans_id = $payload;
                    $trans = InstagramTransaction::where('id', $trans_id)->firstOrFail();
                    $trans->confirm = true;
                    $trans->save();
                    $tel_id = $trans->telegram_user_id;
                    $tel->sendMessage($tel_id, "پرداخت شما توسط ادمین تأیید شد.");
                    break;
                case TelegramController::$cmd_instagramTransactionDeny:
                    $trans_id = $payload;
                    $trans = InstagramTransaction::where('id', $trans_id)->firstOrFail();
                    $tel_id = $trans->telegram_user_id;
                    $tel->sendMessage($tel_id, "پرداخت شما توسط ادمین رد شد.");
                    break;
            }
            // Removing inline keyboard
            $new_msg = $tel->callback_caption . "\n-----------------------\nFinal result: "
                       . ($command == TelegramController::$cmd_instagramTransactionConfirm ? "Confirm" : "Deny");
            $tel->removeInlineKeyboard(null, $tel->message_id, $new_msg);
        }
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
                    $msg = "«« سرویس افزایش فالوور اینستاگرام »»";
                    $msg .= "\nیکی از موارد زیر را انتخاب نمایید:";
                    $msg .= "\n • " . TelegramController::$cmd_insta_detail;
                    $msg .= "\n • " . TelegramController::$cmd_insta_update;
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
                if ($tel->message == TelegramController::$cmd_cancel) {
                    $this->resetTelegramUser($tel_user);
                    $tel->sendKeyboardMessage(null, "فرآیند لغو شد.", TelegramController::$btn_init);
                } else {
                    $carry = ["username" => $tel->message];
                    $tel_user->carry = json_encode($carry);
                    $tel_user->save();
                    $tel->sendMessage(null, "لطفاً رمز عبور اینستاگرام خود را وارد نمایید:");
                    $tel_user->state = TelegramController::$state_insta_password;
                    $tel_user->save();
                }
                return;
            case TelegramController::$state_insta_password:
                if ($tel->message == TelegramController::$cmd_cancel) {
                    $this->resetTelegramUser($tel_user);
                    $tel->sendKeyboardMessage(null, "فرآیند لغو شد.", TelegramController::$btn_init);
                } else {
                    $carry = json_decode($tel_user->carry);
                    $instaAccount = new InstagramAccount();
                    $instaAccount->telegram_user_id = $tel->chat_id;
                    $instaAccount->username = $carry->username;
                    $instaAccount->password = $tel->message;
//                $instaAccount->paid_until       = Carbon::now();
                    $instaAccount->save();
                    $this->resetTelegramUser($tel_user);
                    $tel->sendMessage(null, "اکانت اینستاگرام با موفقیت ثبت شد.");
                    $tel->message = TelegramController::$cmd_insta;
                    $this->state_init($tel, $tel_user);
                }
                return;
            case TelegramController::$state_insta_update:
                switch ($tel->message) {
                    case TelegramController::$cmd_insta_update_username:
                        $tel_user->state = TelegramController::$state_insta_update_username;
                        $tel_user->save();
                        $tel->sendKeyboardMessage(null, "نام کاربری اینستاگرام را وارد نمایید:",
                                                  TelegramController::$btn_cancel);
                        return;
                    case TelegramController::$cmd_insta_update_password:
                        $tel_user->state = TelegramController::$state_insta_update_password;
                        $tel->sendKeyboardMessage(null, "رمز عبور اینستاگرام را وارد نمایید:",
                                                  TelegramController::$btn_cancel);
                        $tel_user->save();
                        return;
                    case TelegramController::$cmd_insta_update_comment:
                        $tel_user->state = TelegramController::$state_insta_update_comment;
                        $tel_user->save();
                        $tel->sendKeyboardMessage(null, "امکان کامنت‌گذاری را تعیین نمایید:",
                                                  TelegramController::$btn_active);
                        return;
                    case TelegramController::$cmd_insta_update_follow:
                        $tel_user->state = TelegramController::$state_insta_update_follow;
                        $tel_user->save();
                        $tel->sendKeyboardMessage(null, "امکان فالو کردن را تعیین نمایید:",
                                                  TelegramController::$btn_active);
                        return;
                    case TelegramController::$cmd_cancel:
                        $tel_user->state = TelegramController::$state_insta;
                        $tel_user->save();
                        $tel->sendKeyboardMessage(null, "فرآیند لغو شد.",
                                                  TelegramController::$btn_insta);
                        return;
                    default:
                        $tel->sendKeyboardMessage(null, "پیام قابل فهم نیست.", TelegramController::$btn_insta_update);
                }
                return;
            case TelegramController::$state_insta_update_username:
                if ($tel->message == TelegramController::$cmd_cancel) {
                    $tel->message = TelegramController::$cmd_insta_update;
                    break;
                } else {
                    $account = InstagramAccount::where("telegram_user_id", $tel_user->telegram_id)->firstOrFail();
                    $account->username = $tel->message;
                    $account->save();
                    $tel->sendMessage(null, "اکانت اینستاگرام با موفقیت اصلاح شد.");
                    $tel->message = TelegramController::$cmd_insta_update;
                    break;
                }
            case TelegramController::$state_insta_update_password:
                if ($tel->message == TelegramController::$cmd_cancel) {
                    $tel->message = TelegramController::$cmd_insta_update;
                    break;
                } else {
                    $account = InstagramAccount::where("telegram_user_id", $tel_user->telegram_id)->firstOrFail();
                    $account->password = $tel->message;
                    $account->save();
                    $tel->sendMessage(null, "اکانت اینستاگرام با موفقیت اصلاح شد.");
                    $tel->message = TelegramController::$cmd_insta_update;
                    break;
                }
            case TelegramController::$state_insta_update_comment:
                if ($tel->message == TelegramController::$cmd_active ||
                    $tel->message == TelegramController::$cmd_deactive) {
                    $account = InstagramAccount::where("telegram_user_id", $tel_user->telegram_id)->firstOrFail();
                    $account->comment = $tel->message == TelegramController::$cmd_active;
                    $account->save();
                    $tel->sendMessage(null, "اکانت اینستاگرام با موفقیت اصلاح شد.");
                    $tel->message = TelegramController::$cmd_insta_update;
                    break;
                } else if ($tel->message == TelegramController::$cmd_cancel) {
                    $tel->message = TelegramController::$cmd_insta_update;
                    break;
                } else {
                    $tel->sendKeyboardMessage(null, "پیام قابل فهم نیست.",
                                              TelegramController::$btn_active);
                    return;
                }
            case TelegramController::$state_insta_update_follow:
                if ($tel->message == TelegramController::$cmd_active ||
                    $tel->message == TelegramController::$cmd_deactive) {
                    $account = InstagramAccount::where("telegram_user_id", $tel_user->telegram_id)->firstOrFail();
                    $account->follow = $tel->message == TelegramController::$cmd_active;
                    $account->save();
                    $tel->sendMessage(null, "اکانت اینستاگرام با موفقیت اصلاح شد.");
                    $tel->message = TelegramController::$cmd_insta_update;
                    break;
                } else if ($tel->message == TelegramController::$cmd_cancel) {
                    $tel->message = TelegramController::$cmd_insta_update;
                    break;
                } else {
                    $tel->sendKeyboardMessage(null, "پیام قابل فهم نیست.",
                                              TelegramController::$btn_active);
                    return;
                }
            case TelegramController::$state_insta_extend:
                if ($tel->message != TelegramController::$cmd_cancel) {
                    $pic = $tel->photo;
                    if (is_null($pic))
                        $tel->sendKeyboardMessage(null, "لطفاً تصویر بفرستید.",
                                                  TelegramController::$btn_cancel);
                    else {
                        $file_id = end($pic)["file_id"];
                        $picture_name = $file_id . ".jpg";
                        $tel->savePhoto($pic, "/images/payment/dokan_bot_pics/{$tel->chat_id}", $picture_name);
                        $account = InstagramAccount::where("telegram_user_id", $tel_user->telegram_id)->firstOrFail();
                        $trans = new InstagramTransaction();
                        $trans->telegram_user_id = $tel->chat_id;
                        $trans->instagram_id = $account->id;
                        $trans->amount = 40000;
                        $trans->description = "بابت تمدید سرویس افزایش فالوور اینستاگرام";
                        $trans->photo = $picture_name;
                        $trans->save();
                        $tel->sendKeyboardMessage(null, "با تشکر. پرداخت شما در انتظار تأیید ادمین می‌باشد.",
                                                  TelegramController::$btn_insta);
                        $msgForAdmin = "New payment"
                                       . "\nid: {$trans->id}"
                                       . "\ntelegram_user_id: {$trans->telegram_user_id}"
                                       . "\ninstagram_id: {$trans->instagram_id}"
                                       . "\namount: {$trans->amount}"
                                       . "\ndescription: {$trans->description}"
                                       . "\nfirst name: {$tel->first_name}"
                                       . "\nlast name: {$tel->last_name}"
                                       . "\nusername: {$tel->username}";
                        $confirm_btn = TelegramController::$cmd_instagramTransactionConfirm . "@{$trans->id}";
                        $deny_btn = TelegramController::$cmd_instagramTransactionDeny . "@{$trans->id}";
                        $tel->sendInlineKeyboardMessage(TelegramController::$admin_chat_id,
                                                        $msgForAdmin, $file_id,
                                                        $confirm_btn, 'Confirm',
                                                        $deny_btn, 'Deny');
                        $tel_user->state = TelegramController::$state_insta;
                        $tel_user->save();
                    }
                } else {
                    $tel_user->state = TelegramController::$state_insta;
                    $tel_user->save();
                    $tel->sendKeyboardMessage(null, "فرآیند لغو شد.",
                                              TelegramController::$btn_insta);
                }
                return;
        }
        // Handling commands
        switch ($tel->message) {
            case TelegramController::$cmd_insta_detail:
                $account = InstagramAccount::where("telegram_user_id", $tel_user->telegram_id)->firstOrFail();
                $msg = "نام کاربری: " . $account->username;
                $msg .= "\nزمان پایان اعتبار: " . (is_null($account->paid_until) ? "غیرفعال" : $account->paid_until);
                $msg .= "\nامکان کامنت‌گذاری: " . ($account->comment ? "فعال" : "غیرفعال");
                $msg .= "\nامکان فالو کردن: " . ($account->follow ? "فعال" : "غیرفعال");
                $msg .= "\nصحت نام کاربری و رمز عبور: " . ($account->is_credentials_valid ? "صحیح" : "ناصحیح (یا هنوز بررسی نشده)");
                $msg .= "\nقعال بودن اعتبارسنجی دو مرحله‌ای: " . ($account->is_two_step_verification_valid ? "غیرفعال" : "فعال (یا هنوز بررسی نشده)");
                $tel->sendKeyboardMessage(null, $msg,
                                          TelegramController::$btn_insta);
                break;
            case TelegramController::$cmd_insta_update:
                $tel_user->state = TelegramController::$state_insta_update;
                $tel_user->save();
                $msg = "یکی از موارد زیر را برای اصلاح انتخاب کنید:";
                $msg .= "\n • " . TelegramController::$cmd_insta_update_username;
                $msg .= "\n • " . TelegramController::$cmd_insta_update_password;
                $msg .= "\n • " . TelegramController::$cmd_insta_update_comment;
                $msg .= "\n • " . TelegramController::$cmd_insta_update_follow;
                $tel->sendKeyboardMessage(null, $msg,
                                          TelegramController::$btn_insta_update);
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
            case TelegramController::$cmd_cancel:
                $this->resetTelegramUser($tel_user);
                $tel->sendKeyboardMessage(null, "فرآیند لغو شد.", TelegramController::$btn_init);
                break;
            default:
                $tel->sendKeyboardMessage(null, "پیام قابل فهم نیست.", TelegramController::$btn_insta);
        }
    }

    private function state_sms($tel, $tel_user) {
        // Handling intermediate states
        switch ($tel_user->state) {
            case TelegramController::$state_sms_contacts_new_name:
                $carry = ["name" => $tel->message];
                $tel_user->carry = json_encode($carry);
                $tel_user->save();
                $tel->sendMessage(null, "لطفاً شماره همراه مشتری را وارد نمایید:");
                $tel_user->state = TelegramController::$state_sms_contacts_new_mobile;
                $tel_user->save();
                return;
            case TelegramController::$state_sms_contacts_new_mobile:
                $carry = json_decode($tel_user->carry);
                $smsReceiver = new SmsReceiver();
                $smsReceiver->telegram_user_id = $tel->chat_id;
                $smsReceiver->name = $carry->name;
                $smsReceiver->mobile = $tel->message;
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

    public function paymentConfirm($tel_id) {
        return "Confirm {$tel_id}";
    }

    public function paymentDeny($tel_id) {
        return "Deny {$tel_id}";
    }
}
