<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Faker\Factory as Faker;

use App\Models\User;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        $_user_id = $faker->uuid;

        $user_id = DB::table('users')->insertGetId([
            'id' => $_user_id,
            'name' => 'Adrian Valbuena',
            'username' => 'adrianva',
            'email' => 'adrianva@cloudstaff.com',
            'password' => Hash::make('bn54dk6c7i'),
            'meta' => json_encode([
                'first_name' => 'Adrian',
                'last_name' => 'Valbuena',
                'initials' => 'AV',
                'image' => "https://via.placeholder.com/96/"
                   . substr($faker->hexcolor, 1)
                   . "/FFFFFF/?text=CD",
                'bg_color' => $faker->hexcolor,
                'timezone' => 'Asia/Manila'
            ]),
            'status' => 'active',
            'role' => 'admin',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }
}
