<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQuestionsAllergiesMedications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->mediumText('allergy_description')->after('other_description')->nullable();
            $table->string('allergy', 3)->after('other_description')->nullable();
            $table->mediumText('medication_description')->after('other_description')->nullable();
            $table->string('medication', 3)->after('other_description')->nullable();
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
            $table->dropColumn(['allergy_description', 'allergy', 'medication_description', 'medication']);
        });
    }
}
