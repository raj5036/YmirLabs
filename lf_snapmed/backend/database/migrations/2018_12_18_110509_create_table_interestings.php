<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableInterestings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interestings', function (Blueprint $table) {
            $table->increments('id');
            $table->char('examination', 36);
            $table->char('physician', 36);
            $table->boolean('has_interest');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('examination')->references('uuid')->on('examinations');
            $table->foreign('physician')->references('uuid')->on('med_users');
            $table->unique(['examination', 'physician']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('interestings');
    }
}
