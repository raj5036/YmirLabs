<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\User;
use \Symfony\Component\Console\Output\ConsoleOutput;

class FixQuickHackedUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $output = new ConsoleOutput();
        $users = User::where('phonenumber', 'like', '%OLD%')->get();
        $count = 0;
        foreach($users as $user) {
            $count++;
            $phonenumber = substr($user->phonenumber, 0, 14);
            $output->writeln('Fixing -> ' . $user->phonenumber . ' to ' . $phonenumber);
            $user->phonenumber = $phonenumber;
            $user->save();
        }
        $output->writeln('Fixed '.$count.' users ..');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $output = new ConsoleOutput();
            $output->writeln('This migration is not reversable.');
        });
    }
}
