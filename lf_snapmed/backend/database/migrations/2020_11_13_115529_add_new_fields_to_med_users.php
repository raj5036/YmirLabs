<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewFieldsToMedUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('med_users', function (Blueprint $table) {
            $table->enum('currency', ['NOK', 'SEK', 'USD', 'EUR', 'GBP'])->after('otp');
            $table->enum('country', ['no', 'se', 'uk', 'de'])->after('otp');
            $table->boolean('is_doctor')->after('superadmin')->default(false);
            $table->string('token')->after('is_doctor')->nullable();
            $table->boolean('is_email_verified')->after('is_doctor')->default(false);
            $table->boolean('is_phone_verified')->after('is_doctor')->default(false);
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
            $table->dropColumn('is_doctor', 'country', 'currency');
        });
    }
}
