<?php

namespace App\Http\Controllers\api\v1;


class TelegramSdk
{
    public $api_key = "";
    public $telegram_url = "";
    public $chat_id = null;
    public $admin_chat_id = null;

    public $first_name = null;
    public $last_name = null;
    public $username = null;
    public $photo = null;
    public $message = null;
    public $message_id = null;
    public $latitude = null;
    public $longitude = null;
    public $phone_number = null;
    public $type = null;
    public $callback_data = null;

    public function __construct($api_key, $chat_id = 68331230)
    {
        $this->api_key = $api_key;
        $this->admin_chat_id = $chat_id;
        $this->chat_id = $chat_id;
        $this->telegram_url = "https://api.telegram.org/bot$api_key/";
    }
    
    public function intitilize($update)
    {
        $update = json_decode($update, TRUE);

        $this->first_name = null;
        $this->last_name = null;
        $this->username = null;
        $this->photo = null;
        $this->message = null;
        $this->message_id = null;
        $this->latitude = null;
        $this->longitude = null;
        $this->phone_number = null;
        $this->callback_data = null;

        if (isset($update['callback_query']["message"]["chat"]["id"])){
            $this->type = "callback_query";
            $this->chat_id = $update['callback_query']["message"]["chat"]["id"];
        }
        $this->first_name = '';
        if (isset($update['callback_query']["message"]["chat"]["first_name"]))
            $this->first_name = $update['callback_query']["message"]["chat"]["first_name"];
        $this->last_name = '';
        if (isset($update['callback_query']["message"]["chat"]["last_name"]))
            $this->last_name = $update['callback_query']["message"]["chat"]["last_name"];
        $this->username = '';
        if (isset($update['callback_query']["message"]["chat"]["username"]))
            $this->username = $update['callback_query']["message"]["chat"]["username"];

        if (isset($update['callback_query']["message"]["message_id"]))
            $this->message_id = $update['callback_query']["message"]["message_id"];
        if (isset($update['callback_query']["data"]))
            $this->callback_data = $update['callback_query']["data"];

        if (isset($update["message"]["chat"]["id"])){
            $this->type = "message";
            $this->chat_id = $update["message"]["chat"]["id"];
        }

        if (isset($update["message"]["chat"]["first_name"]))
            $this->first_name = $update["message"]["chat"]["first_name"];

        if (isset($update["message"]["chat"]["last_name"]))
            $this->last_name = $update["message"]["chat"]["last_name"];

        if (isset($update["message"]["chat"]["username"]))
            $this->username = $update["message"]["chat"]["username"];


        if (isset($update["message"]["photo"]))
            $this->photo = $update["message"]["photo"];

        $this->message = '';
        if (isset($update["message"]["text"]))
            $this->message = $update["message"]["text"];

        if (isset($update["message"]["location"]["latitude"]))
            $this->latitude = $update["message"]["location"]["latitude"];

        if (isset($update["message"]["location"]["longitude"]))
            $this->longitude = $update["message"]["location"]["longitude"];

        if (isset($update["message"]["contact"]["phone_number"]))
            $this->phone_number = $update["message"]["contact"]["phone_number"];
    }



    public function savePhoto($file, $image_path = "/pics", $file_name = "temp_file.jpg")
    {
        $file_id = "";
        if(is_array($file)){
            $file_id = end($file)["file_id"];
            
        } else{
            $file_id = $file;
        }
        // $this->sendMessage(null, "[DEBUG] photo received $file_id.");
        $url = $this->telegram_url . "getFile?file_id=$file_id";

        $result = file_get_contents($url);

        $result_json = json_decode($result, TRUE);
        if (isset($result_json['result']['file_path'])) {

            $image_url = $this->telegram_url . '/' . $result_json['result']['file_path'];
            if (!is_dir(public_path() . '/' . $image_path . '/'))
                mkdir(public_path() . '/' . $image_path . '/', 0777, true);

            $data = @file_get_contents($image_url);
            // if (!$data === false) {
                
                @file_put_contents(public_path() . '/' . $image_path . '/' . $file_name, $data);
                return true;
            // } else {
                // return false;
            // }


        } else {
            return false;
        }
    }


    public function getUserPhotoFileId($chatId)
    {
        if(is_null($chatId)){
            $chatId = $this->chat_id;
        }
        $url = $this->telegram_url . "getUserProfilePhotos?user_id=$chatId";
        $result = @file_get_contents($url);
        $result_json = json_decode($result, TRUE);
        if (isset($result_json['result']['photos'][0][2]['file_id'])) {
            $file_id = $result_json['result']['photos'][0][2]['file_id'];
            return $file_id;
        } else {
            return null;
        }


    }

    public function sendInlineKeyboardMessage($chatId, $message, $photo = "")
    {
        if(is_null($chatId)){
            $chatId = $this->chat_id;
        }

        $arg_list = func_get_args();
        $callback_query_index = 3;
        $button_text_index = 4;
        $final_array = [];
        $counter = 0;
        $first_array = [];
        $second_array = [];
        while (isset($arg_list[$callback_query_index]) && isset($arg_list[$button_text_index])) {

            if (preg_match('/^url_(?P<url>.*?)$/', $arg_list[$callback_query_index], $result)) {
                if ($counter % 2 == 0)
                    $first_array = array("text" => $arg_list[$button_text_index], "url" => $result['url']);
                else
                    $second_array = array("text" => $arg_list[$button_text_index], "url" => $result['url']);
            } else {
                if ($counter % 2 == 0)
                    $first_array = array("text" => $arg_list[$button_text_index], "callback_data" => $arg_list[$callback_query_index]);
                else
                    $second_array = array("text" => $arg_list[$button_text_index], "callback_data" => $arg_list[$callback_query_index]);
            }

            if ($counter % 2 == 1) {
                $final_array[] = [$second_array, $first_array];
                $first_array = [];
                $second_array = [];
            }


            $counter++;
            $callback_query_index += 2;
            $button_text_index += 2;
        }

        if (count($first_array) && $counter % 2 == 1) {
            $final_array[] = [$first_array];
        }
        $keyboard = array(
            "inline_keyboard" => $final_array
        );
        $keyboard = json_encode($keyboard, true);
        if ($photo == "") {
            $url = $this->telegram_url . "sendmessage?chat_id=$chatId&text=" . urlencode($message)
                . "&parse_mode=HTML&reply_markup=$keyboard";
        } else {
            $url = $this->telegram_url . "sendPhoto?chat_id=$chatId&photo=" . urlencode($photo) . "&caption=" . urlencode($message)
                . "&parse_mode=HTML&reply_markup=$keyboard";
        }

        @file_get_contents($url);
    }


    public function updateInlineKeyboardMessage($chatId, $messageId, $message = null, $callback_query_id = null, $popup_message = null, $column_number = 2)
    {
        if(is_null($chatId)){
            $chatId = $this->chat_id;
        }

        $arg_list = func_get_args();
        $callback_num = 5;
        $buttonText_num = 6;
        $final_array = [];

        if ($column_number == 1) {

            while (isset($arg_list[$callback_num]) && isset($arg_list[$buttonText_num])) {

                if (preg_match('/^url_(?P<url>.*?)$/', $arg_list[$callback_num], $result)) {
                    $final_array[] = [array("text" => $arg_list[$buttonText_num], "url" => $result['url'])];
                } else {
                    $final_array[] = [array("text" => $arg_list[$buttonText_num], "callback_data" => $arg_list[$callback_num])];
                }

                $callback_num += 2;
                $buttonText_num += 2;
            }
        } else {
            $counter = 0;
            $first_array = [];
            $second_array = [];
            while (isset($arg_list[$callback_num]) && isset($arg_list[$buttonText_num])) {

                if (preg_match('/^url_(?P<url>.*?)$/', $arg_list[$callback_num], $result)) {
                    if ($counter % 2 == 0)
                        $first_array = array("text" => $arg_list[$buttonText_num], "url" => $result['url']);
                    else
                        $second_array = array("text" => $arg_list[$buttonText_num], "url" => $result['url']);
                } else {
                    if ($counter % 2 == 0)
                        $first_array = array("text" => $arg_list[$buttonText_num], "callback_data" => $arg_list[$callback_num]);
                    else
                        $second_array = array("text" => $arg_list[$buttonText_num], "callback_data" => $arg_list[$callback_num]);
                }

                if ($counter % 2 == 1) {
                    $final_array[] = [$second_array, $first_array];
                    $first_array = [];
                    $second_array = [];
                }


                $counter++;
                $callback_num += 2;
                $buttonText_num += 2;
            }

            if (count($first_array) && $counter % 2 == 1) {
                $final_array[] = [$first_array];
            }
        }

        $keyboard = array(
            "inline_keyboard" => $final_array
        );

        $keyboard = json_encode($keyboard, true);
        if (!is_null($message)) {
            $url = $this->telegram_url . "editMessageText?text=" . urlencode($message) . "&message_id=$messageId&chat_id=$chatId&parse_mode=HTML&reply_markup=$keyboard";
            @file_get_contents($url);
        } else {
            $url = $this->telegram_url . "editMessageReplyMarkup?message_id=$messageId&chat_id=$chatId&parse_mode=HTML&reply_markup=$keyboard";
            @file_get_contents($url);
        }
        if (!is_null($callback_query_id) && !is_null($popup_message) && isset($popup_message)) {
            $url = $this->telegram_url . "AnswerCallbackQuery?callback_query_id=" . $callback_query_id . "&text=" . urlencode($popup_message);
            @file_get_contents($url);
        }

    }

    public function sendKeyboardMessage($chatId, $message, $buttonsArray)
    {
        if(is_null($chatId)){
            $chatId = $this->chat_id;
        }

        $keyboard = [
            'keyboard' => $buttonsArray,
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
            'selective' => true
        ];
        $keyboard = json_encode($keyboard, true);
        $url = $this->telegram_url . "sendMessage?chat_id=$chatId&text=" . urlencode($message). "&parse_mode=HTML&reply_markup=$keyboard";
        @file_get_contents($url);
    }


    public function sendMessage($chatId, $message)
    {
        
        if(is_null($chatId) || !is_string($chatId)){
            $chatId = $this->chat_id;
        }

        $url = $this->telegram_url . "sendMessage?chat_id=" . urlencode($chatId)
               . "&text=" . urlencode($message) . "&parse_mode=HTML";

        @file_get_contents($url);
    }

}