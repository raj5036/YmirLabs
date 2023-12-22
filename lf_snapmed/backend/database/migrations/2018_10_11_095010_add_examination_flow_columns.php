<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExaminationFlowColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->renameColumn('description', 'other_description')->nullable()->change();
        });
        Schema::table('examinations', function (Blueprint $table) {
            $table->mediumText('other_description')->nullable()->change();
            $table->string('mole_size')->nullable();
            $table->string('mole_symmetri')->nullable();
            $table->string('mole_color')->nullable();
            $table->string('mole_change')->nullable();
            $table->string('mole_others')->nullable();
            $table->mediumText('mole_others_description')->nullable();
            $table->string('mole_doctor')->nullable();
            $table->mediumText('mole_description')->nullable();
            $table->string('rash_same')->nullable();
            $table->string('rash_cold')->nullable();
            $table->string('rash_drugs')->nullable();
            $table->mediumText('rash_drugs_description')->nullable();
            $table->string('rash_doctor')->nullable();
            $table->mediumText('rash_description')->nullable();
            $table->string('skin_cancer_change')->nullable();
            $table->mediumText('skin_cancer_change_description')->nullable();
            $table->string('skin_cancer_size')->nullable();
            $table->string('skin_cancer_doctor')->nullable();
            $table->mediumText('skin_cancer_description')->nullable();
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
            $table->dropColumn('mole_size');
            $table->dropColumn('mole_symmetri');
            $table->dropColumn('mole_color');
            $table->dropColumn('mole_change');
            $table->dropColumn('mole_others');
            $table->dropColumn('mole_others_description');
            $table->dropColumn('mole_doctor');
            $table->dropColumn('mole_description');
            $table->dropColumn('rash_same');
            $table->dropColumn('rash_cold');
            $table->dropColumn('rash_drugs');
            $table->dropColumn('rash_drugs_description');
            $table->dropColumn('rash_doctor');
            $table->dropColumn('rash_description');
            $table->dropColumn('skin_cancer_change');
            $table->dropColumn('skin_cancer_change_description');
            $table->dropColumn('skin_cancer_size');
            $table->dropColumn('skin_cancer_doctor');
            $table->dropColumn('skin_cancer_description');
        });
        Schema::table('examinations', function (Blueprint $table) {
            $table->renameColumn('other_description', 'description');
        });
    }
}
