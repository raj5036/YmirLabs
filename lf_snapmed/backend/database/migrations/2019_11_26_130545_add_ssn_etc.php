<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\User;

class AddSsnEtc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('ssn')->nullable();
            $table->string('login_method')->nullable();
        });

        // since all existing users use phone at this point set that Login method
        DB::table('users')->update(['login_method' => User::LOGIN_METHOD_PHONE]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ssn');
            $table->dropColumn('login_method');
        });
    }
}
