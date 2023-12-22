<?php

use Illuminate\Database\Migrations\Migration;

class AddRegionDeInUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE users CHANGE COLUMN region region ENUM('no', 'se', 'uk', 'co', 'de') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
