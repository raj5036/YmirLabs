<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExaminationsImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->dropForeign('examinations_closeup_foreign');
            $table->dropForeign('examinations_overview_foreign');
            $table->dropColumn('closeup');
            $table->dropColumn('overview');
        });
        Schema::create('examinations_images', function (Blueprint $table) {
            $table->increments('id');
            $table->char('examination', 36);
            $table->char('image', 36);
            $table->string('type');

            $table->foreign('examination')->references('uuid')->on('examinations');
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
        Schema::table('examinations', function (Blueprint $table) {
            $table->char('closeup', 36);
            $table->char('overview', 36);
            $table->foreign('closeup')->references('uuid')->on('images');
            $table->foreign('overview')->references('uuid')->on('images');
        });
        Schema::dropIfExists('examinations_images');
    }
}
