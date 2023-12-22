<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProfessionFieldsToMedUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('med_users', function (Blueprint $table) {
            $table->string('profession_code')->after('is_email_verified')->nullable();
            $table->string('profession')->after('is_email_verified')->nullable();
            $table->string('makeplans_resource_id')->after('is_email_verified')->nullable();
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
            $table->dropColumn('makeplans_resource_id', 'profession', 'profession_code');
        });
    }
}
