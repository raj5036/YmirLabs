<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStripeColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe')->nullable();
        });
        Schema::table('examinations', function (Blueprint $table) {
            $table->dateTime('deadline')->nullable()->change();
            $table->dateTime('charged')->nullable()->change();
            $table->string('stripe')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->dropColumn('stripe');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('stripe');
        });
    }
}
