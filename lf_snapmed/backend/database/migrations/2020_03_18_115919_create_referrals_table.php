<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReferralsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->char('uuid', 36)->primary();
            $table->char('examination', 36);
            $table->char('diagnosis', 36);
            $table->string('name');
            $table->string('type');
            $table->string('suffix');
            $table->integer('size_in_kb')->unsigned();

            $table->foreign('examination')->references('uuid')->on('examinations');
            $table->foreign('diagnosis')->references('uuid')->on('diagnoses');
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
        Schema::dropIfExists('referrals');
    }
}
