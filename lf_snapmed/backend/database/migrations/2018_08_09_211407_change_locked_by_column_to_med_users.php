<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeLockedByColumnToMedUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->dropForeign('examinations_locked_by_foreign');
            $table->dropColumn('locked_by');
        });
        Schema::table('examinations', function (Blueprint $table) {
            $table->char('locked_by', 36)->nullable();
            $table->foreign('locked_by')->references('uuid')->on('med_users');
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
            $table->dropForeign('examinations_locked_by_foreign');
            $table->dropColumn('locked_by');
        });
        Schema::table('examinations', function (Blueprint $table) {
            $table->char('locked_by', 36)->nullable();
            $table->foreign('locked_by')->references('uuid')->on('users');
        });
    }
}
