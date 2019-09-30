<?php

use Illuminate\Database\Seeder;

class ServiceProviderTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\ServiceProviderType::class, 10)->create();
    }
}
