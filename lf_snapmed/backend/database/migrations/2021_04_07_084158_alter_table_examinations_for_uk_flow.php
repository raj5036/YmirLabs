<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableExaminationsForUkFlow extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->mediumText('body_part')->after('other_description')->nullable();
            $table->string('family_history', 3)->after('allergy_description')->nullable();
            $table->mediumText('family_history_description')->after('family_history')->nullable();
            $table->string('treatment', 3)->after('family_history_description')->nullable();
            $table->mediumText('treatment_description')->after('treatment')->nullable();
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
            $table->dropColumn(['body_part', 'family_history','family_history_description', 'treatment','treatment_description']);
        });
    }
}



