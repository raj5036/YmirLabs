<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartnerUserClaimNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partner_user_claim_numbers', function (Blueprint $table) {
            $table->char('uuid', 36)->primary();
            $table->char('user_id', 36);
            $table->string('partner');
            $table->string('claimnumber');
            $table->foreign('user_id')->references('uuid')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partner_user_claim_numbers');
    }
}
