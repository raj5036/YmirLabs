<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateMedUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('med_users', function (Blueprint $table) {
            $table->string('display_name')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('deleted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('med_users', function (Blueprint $table) {
            $table->dropColumn('display_name');
            $table->dropColumn('active');
            $table->dropColumn('deleted');
        });
    }
}
