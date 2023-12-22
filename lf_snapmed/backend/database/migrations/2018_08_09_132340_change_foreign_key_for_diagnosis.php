<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeForeignKeyForDiagnosis extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropForeign('diagnoses_performed_by_foreign');
        });
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->foreign('performed_by')->references('uuid')->on('med_users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropForeign('diagnoses_performed_by_foreign');
        });
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->foreign('performed_by')->references('uuid')->on('users');
        });
    }
}
