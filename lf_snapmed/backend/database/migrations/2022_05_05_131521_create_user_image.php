<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserImage extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_images', function (Blueprint $table) {
            $table->increments('id');
            $table->char('user', 36);
            $table->char('image', 36);
            $table->string('type');
            $table->foreign('user')->references('uuid')->on('users');
            $table->foreign('image')->references('uuid')->on('images');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_images');
    }
}
