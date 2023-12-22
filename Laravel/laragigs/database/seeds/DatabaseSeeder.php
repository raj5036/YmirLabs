<?php

use App\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //1. For manually seeding(creating) a row
        // DB::table('users')->insert([
        //     'name' => Str::random(10),
        //     'email' => Str::random(10).'@ymirlabs.com',
        //     'password' => Hash::make('password'),
        // ]);

        //For seeding 50 rows at once
        factory(App\User::class, 50)->create();
    }
}
