<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('clinic_id', 36);
            $table->string('name');
            $table->string('code');
            $table->time('duration');
            $table->json('med_users');
            $table->string('description');

            $table->foreign('clinic_id')->references('id')->on('clinics');
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
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign('services_clinic_id_foreign');
        });
        Schema::dropIfExists('services');
    }
}
