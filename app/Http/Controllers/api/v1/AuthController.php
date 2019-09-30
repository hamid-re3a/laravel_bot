<?php

namespace App\Http\Controllers\api\v1;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;


class AuthController extends ApiController
{
    private $client_id = 2;
    private $client_secret = "Kv7McrWHfBRx0e0ce2XBM2GeImPqgVz6iu29Ydbu";

    /**
     * @return int
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function refresh_token(Request $request)
    {
        $rules = array(
            'refresh_token' => array('required', 'string'),
        );

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->respondBadRequest($validator->errors()->first());
        }

        $request->request->add([
                'grant_type' => 'refresh_token',
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'refresh_token' => $request->get('refresh_token'),
                'scope' => '',
            ]
        );
        $re = $request->create('oauth/token', 'post');
        $response = Route::dispatch($re);

        return $this->authResponse($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request)
    {

        $messages = [
            'phone_number.required' => 'لطفا فیلد شماره همراه را پر کنید',
            'phone_number.regex' => 'شماره همراه معتبر وارد کنید',
            'phone_number.required' => 'لطفا فیلد شماره همراه را پر کنید',
            'phone_number.unique' => 'شماره دیگری انتخاب کنید این شماره قبلا استفاده شده است',
            'password.min' => 'رمز عبور باید حداقل 6 کاراکتر باشد',
            'password.required' => 'لطفا فیلد رمز عبور را پر کنید',
            'password.confirmed' => 'فیلد تایید رمز عبور همخوانی ندارد',
        ];
        $rules = array(
            'phone_number' => array('required', 'regex:/^[ ]*(?:0|\+|00)?(?:98)?9\d{9}[ ]*$/', 'exists:users'),
            'password' => array('required', 'min:6'),
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->respondBadRequest($validator->errors()->first());
        }

        $request->request->add([
                'grant_type' => 'password',
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'username' => $request->get('phone_number'),
                'password' => $request->get('password'),
                'scope' => '',
            ]
        );
        $re = $request->create('oauth/token', 'post');
        $response = Route::dispatch($re);

        return $this->authResponse($response);

    }

    /**
     * @param $response
     * @return mixed
     */
    public function authResponse($response)
    {
        if (!empty($response->getContent())) {
            $json_response = json_decode((string)$response->getContent(), true);
            return $this->setStatusCode($response->getStatusCode())->respondWithMessage(null, $json_response);
        } else {
            return $this->respondServerSideError();
        }
    }


    /**
     * @return bool|\Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function loggedInUserOrFail()
    {

        if ($user = Auth::check()) {
            if (Auth::user()->actived == 1) {
                return Auth::user();
            } else {
                return false;
            }
        } elseif (Auth::guard('api')->check()) {
            if (Auth::guard('api')->user()->actived == 1) {
                return Auth::guard('api')->user();
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @return bool|\Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function loggedInUserOrFailWithoutActivation()
    {
        if ($user = Auth::check()) {
            return Auth::user();
        } elseif (Auth::guard('api')->check()) {
            return Auth::guard('api')->user();
        } else {
            return false;
        }
    }
}
