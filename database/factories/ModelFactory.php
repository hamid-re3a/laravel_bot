<?php

use \Illuminate\Database\Eloquent\Factory;
/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'mobile_no' => '09'.$faker->randomNumber(9),
        'password' => bcrypt('123'),
        'username' => $faker->unique()->username(),
        'active' => $faker->boolean
    ];
});

$factory->define(App\ServiceProviderType::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->dateTimeBetween(),
    ];
});


$factory->define(App\ServiceProvider::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->word,
        'user_id' => $faker->numberBetween(1,50),
        'service_provider_type_id'=> $faker->numberBetween(1,10),
        'gender' => array(null,"male","female")[$faker->numberBetween(0,2)]

    ];
});

