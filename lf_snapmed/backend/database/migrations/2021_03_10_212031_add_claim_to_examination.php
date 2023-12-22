<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClaimToExamination extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->char('partner_user_claim_number', 36)->nullable();
            $table->foreign('partner_user_claim_number')->references('uuid')->on('partner_user_claim_numbers');
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
            $table->dropColumn('partner_user_claim_number');
            $table->dropForeign(['partner_user_claim_number']);
        });
    }
}
