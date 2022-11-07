<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Customer::factory()->create([
            'merchant_id' => 504238736,
            'email' => 'sandboxcssfeedmanager@gmail.com',
            'json_auth' => '/auth.json',
        ]);
    }
}
