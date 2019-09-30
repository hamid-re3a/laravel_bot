<?php

namespace App\Http\Controllers\api\v1;

use App\Reservina\Transformers\UserTransformer;
use App\User;
use Faker\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class UserController extends ApiController
{
    /**
     * @var UserTransformer
     */
    protected $userTransformer;

    /**
     * ServiceProviderController constructor.
     * @param $userTransformer
     */
    public function __construct(UserTransformer $userTransformer)
    {
        $this->userTransformer = $userTransformer;
    }


    /**
     * @return mixed
     */
    public function index()
    {

        $_users = User::paginate(15);

        $_transformed_data = $this->userTransformer->transformPaginationCollection($_users);
        $_response_data = $this->mergeDataWithPaginationInfo($_users, $_transformed_data);
        $message = trans('api.messages.fetched_successfully', ['className' => trans('controllers.' . class_basename($this) . '.name')]);
        return $this->respondSuccessfully($message, $_response_data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $rules = array(
            'phone_number' => array('required', 'regex:/^[ ]*(?:0|\+|00)?(?:98)?9\d{9}[ ]*$/', 'unique:users'),
            'password' => array('required', 'min:6', 'confirmed'),
            'username' => array('unique:users', 'regex:/^[a-zA-Z\d_]{3,100}$/'),
            'name' => array('required', 'string', 'max:255'),
        );
        $messages = [
            'phone_number.required' => 'لطفا فیلد شماره همراه را پر کنید',
            'phone_number.regex' => 'شماره همراه معتبر وارد کنید',
            'phone_number.required' => 'لطفا فیلد شماره همراه را پر کنید',
            'phone_number.unique' => 'شماره دیگری انتخاب کنید این شماره قبلا استفاده شده است',
            'password.min' => 'رمز عبور باید حداقل 6 کاراکتر باشد',
            'password.required' => 'لطفا فیلد رمز عبور را پر کنید',
            'password.confirmed' => 'فیلد تایید رمز عبور همخوانی ندارد',
            'username.regex' => 'نام کاربری باید بیش از سه کاراکتر و تنها با حروف لاتین ،عدد و زیر خط باشد',
            'username.unique' => 'نام کاربری دیگری انتخاب کنید این نام قبلا انتخاب شده است',
            'name.string' => 'نام باید یک رشته باشد',
            'name.max' => 'نام باید ماکزیمم 255 کاراکتر باشد',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->respondBadRequest($validator->errors()->first());
        }

        $input = $request->only('phone_number', 'password', 'username', 'name');

        if (isset($input['username']))
            $input['username'] = $this->createUniqueUsername($input['username'], $input['name']);
        else
            $input['username'] = $this->createUniqueUsername("", $input['name']);
        $user = User::create($input);
        $message = 'حساب شما با موفقیت ساخته شد به خانواده Adak اسپا خوش آمدید';
        $this->sendSMS($input['phone_number'], $message);
        return $this->respondSuccessfully($message, $user);

    }

    /**
     * @param $requestedUsername
     * @param $name
     * @return mixed|string
     */
    private function createUniqueUsername($requestedUsername, $name)
    {
        $faker = Factory::create('fa_IR');
        if (is_null($requestedUsername) || empty($requestedUsername)) {
            $requestedUsername = str_replace(" ", "_", trim($name));
        }

        $ready_to_use_username = $requestedUsername;
        $max_tries_num = 100;
        while ($max_tries_num--) {
            if (User::whereUsername($requestedUsername)->first()) {
                $ready_to_use_username = $requestedUsername . "_" . $faker->randomNumber(3);
            } else {
                return $ready_to_use_username;
            }
        }

        return $ready_to_use_username;
    }
}
