<?php

use Illuminate\Database\Seeder;

use Webpatser\Uuid\Uuid;
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Empty tables in the correct order
        DB::table('examinations')->delete();
        DB::table('images')->delete();
        DB::table('users')->delete();

        // Populate data
        $this->call('UsersSeeder');
        $this->call('ImagesSeeder');
        $this->call('ExaminationsSeeder');
    }
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Generate users
class UsersSeeder extends Seeder
{
    public function run()
    {
        // for ($i = 0; $i < 10; $i++) {
        //     App\User::create([
        //         'phonenumber' => '5554440' . $i,
        //         'password' => app('hash')->make('password')
        //     ]);
        // }
    }
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Generate images
class ImagesSeeder extends Seeder
{
    public function run()
    {
        // for ($i=0; $i < 20; $i++) {
        //     App\Image::create([
        //         'type' => 'image/jpeg',
        //         'size' => 123212,
        //         'suffix' => 'jpg'
        //     ]);
        // }
    }
}


// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Generate examinations
class ExaminationsSeeder extends Seeder
{
    public function run()
    {
        // $images = App\Image::all();
        // foreach (App\User::whereNull('email')->get() as $user) {
        //     $dt = new DateTime('now');
        //     // $dt->setTimezone(new DateTimeZone('Norway/Oslo'));
        //     $deadline = $dt->modify('+'.(rand(30, 600)).' minute');
        //     $closeup = $images->get(rand(0, 19))->uuid;
        //     $overview = $images->get(rand(0, 19))->uuid;
        //     $examination = App\Examination::create([
        //         'patient' => $user->uuid,
        //         'who' => 'me',
        //         'gender' => 'male',
        //         'age' => '30',
        //         'category' => 'mole',
        //         'duration' => 'days',
        //         'deadline' => $deadline,
        //         'description' => 'Whatever'
        //     ]);
        //     $examination->closeups()->attach($closeup, ['type' => 'closeup']);
        //     $examination->overviews()->attach($overview, ['type' => 'overview']);
        // }
    }
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
