<?php

use Illuminate\Database\Seeder;

class ServiceProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\ServiceProvider::class, 20)->create();
    }
}
