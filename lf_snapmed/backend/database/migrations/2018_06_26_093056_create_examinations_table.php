<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class CreateExaminationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('examinations', function (Blueprint $table) {
            $table->char('uuid', 36)->primary();
            $table->char('patient', 36);
            $table->string('who');
            $table->string('gender');
            $table->string('age');
            $table->string('category');
            $table->char('closeup', 36);
            $table->char('overview', 36);
            $table->string('duration');
            $table->mediumText('description');
            $table->dateTime('deadline');
            $table->boolean('diagnosed');
            $table->string('charged')->nullable();

            $table->foreign('patient')->references('uuid')->on('users');
            $table->foreign('closeup')->references('uuid')->on('images');
            $table->foreign('overview')->references('uuid')->on('images');
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
        Schema::table('examinations', function (Blueprint $table) {
            $table->dropForeign('examinations_patient_foreign');
            $table->dropForeign('examinations_closeup_foreign');
            $table->dropForeign('examinations_overview_foreign');
        });
        Schema::dropIfExists('examinations');
    }
}
