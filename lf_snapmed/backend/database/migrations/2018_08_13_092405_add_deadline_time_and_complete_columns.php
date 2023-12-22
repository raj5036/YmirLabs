<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeadlineTimeAndCompleteColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->renameColumn('deadline', 'deadline_time');
        });
        Schema::table('examinations', function (Blueprint $table) {
            $table->unsignedTinyInteger('deadline')->nullable();
            $table->boolean('complete')->default(true);
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
            $table->dropColumn('deadline');
            $table->dropColumn('complete');
        });
        Schema::table('examinations', function (Blueprint $table) {
            $table->renameColumn('deadline_time', 'deadline');
        });
    }
}
