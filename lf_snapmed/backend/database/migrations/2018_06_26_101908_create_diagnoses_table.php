<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDiagnosesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->char('uuid', 36)->primary();
            $table->char('examination', 36);
            $table->char('performed_by', 36);
            $table->string('category');
            $table->mediumText('description');

            $table->foreign('examination')->references('uuid')->on('examinations');
            $table->foreign('performed_by')->references('uuid')->on('users');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
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
            $table->dropForeign('diagnoses_examination_foreign');
            $table->dropForeign('diagnoses_performed_by_foreign');
        });
        Schema::dropIfExists('diagnoses');
    }
}
