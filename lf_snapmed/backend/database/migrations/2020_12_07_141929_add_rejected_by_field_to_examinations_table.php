<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRejectedByFieldToExaminationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->char('rejected_by', 36)->nullable()->after('reject_reason');

            $table->foreign('rejected_by')->references('uuid')->on('med_users');
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
            $table->dropForeign('examinations_rejected_by_foreign');
            $table->dropColumn('rejected_by');
        });
    }
}
