<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDoctorImageInMedUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('med_users', function (Blueprint $table) {
            $table->char('display_image', 36);
            $table->boolean('show_doctor')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('med_users', function (Blueprint $table) {
            $table->dropColumn('display_image');
            $table->dropColumn('show_doctor');
        });
    }
}
