<?php

namespace App\Http\Controllers;

use App\Activation_code;
use App\Http\Controllers\api\v1\AuthController;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class UserActivationsController extends AuthController
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(($user = $this->loggedInUserOrFail())){
            return $this->respondSuccessfully('حساب شما از پیش فعال است',$user);
        }

        if(!($user = $this->loggedInUserOrFailWithoutActivation())){
            return $this->respondBadRequest('شما هنوز ثبت نام نکرده اید.');
        }

        $rules = array(
            'activation_code' => array('required','min:5'),
        );
        $messages = [
            'activation_code.min' => 'کد فعالسازی باید حداقل 5 کاراکتر باشد',
            'activation_code.required' => 'لطفا فیلد کد فعالسازی را پر کنید',
        ];

        $validator = Validator::make($request->all(), $rules ,$messages);
        if( $validator->fails()){
            return  $this->respondBadRequest($validator->errors()->first());
        }

        $activate_obj = Activation_code::latest('created_at')->where('user_id',$user->id)->first();

        if( ! $activate_obj ) {
            return $this->respondNotFound('هیچ کدی برای شما وجود ندارد یک کد جدید بگیرید!!');
        }

        if( $activate_obj->activation_code == $request->activation_code ){
            $user->actived = 1;
            $user->save();
            return $this->respondSuccessfully('حساب با موفقیت فعال شد',$user);
        }

        return $this->respondBadRequest('متاسفانه مطابقت نداشت');

    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if(($user = $this->loggedInUserOrFail())){
            return $this->respondSuccessfully('حساب شما از پیش فعال است');
        }

        if(!($user = $this->loggedInUserOrFailWithoutActivation())){
            return $this->respondBadRequest('شما هنوز ثبت نام نکرده اید.');
        }

        $result = $this->maxActivation($user);

        if( $result['response'] != false ){
            return $result['response'];
        }

        $activation_code = random_int(10000,999999);

        $user->activation_codes()->create([ 'activation_code' => $activation_code ,'sent' => 1 ]);

        
        $to = $user->phone_number;
        $to = substr($to,1);
        $message = "کد فعالسازی حساب
        $activation_code";

        $this->sendSMS($to, $message);
	  

        return $this->respondSuccessfully('پیامک ارسال شد گوشی خود را چک کنید');


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @param $user
     * @return mixed
     */
    public function maxActivation($user)
    {
        $response = false;

        $activate_obj = Activation_code::where('user_id',$user->id)->where('created_at', '<', Carbon::now())->where('created_at', '>', Carbon::yesterday())->get();

        if (count($activate_obj) > 3) {
            $response = $this->respondCriticalError('شما بیش از مقدار مجاز درخواست ارسال کرده اید تا فردا صبر کنید');
            return ['response' => $response,'activation_obj'=>$activate_obj];
        }

        $activate_obj = Activation_code::where('user_id',$user->id)->where('created_at', '<', Carbon::now())->where('created_at', '>', Carbon::now()->subDay(-7))->get();

        if (count($activate_obj) > 8) {
            $response = $this->respondCriticalError('شما بیش از مقدار مجاز درخواست ارسال کرده اید تا هفته دیگر صبر کنید');
            return ['response' => $response,'activation_obj'=>$activate_obj];
        }

        $activate_obj = Activation_code::where('user_id',$user->id)->where('created_at', '<', Carbon::now())->where('created_at', '>', Carbon::now()->subDay(-30))->get();

        if (count($activate_obj) > 9) {
            $response = $this->respondCriticalError('شما بیش از مقدار مجاز درخواست ارسال کرده اید تا ماه دیگر صبر کنید');
            return ['response' => $response,'activation_obj'=>$activate_obj];
        }

        $activate_obj = Activation_code::where('user_id',$user->id)->where('created_at', '<', Carbon::now())->where('created_at', '>', Carbon::now()->subDay(-365))->get();

        if (count($activate_obj) > 19) {
            $response = $this->respondCriticalError('شما بیش از مقدار مجاز درخواست ارسال کرده اید تا سال دیگر صبر کنید');
            return ['response' => $response,'activation_obj'=>$activate_obj];
        }
        return ['response' => $response,'activation_obj'=>$activate_obj];
    }


}
