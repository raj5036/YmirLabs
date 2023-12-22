<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCodesLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_code_logs', function (Blueprint $table) {
            $table->char('uuid', 36)->primary();
            $table->char('user_id', 36);
            $table->char('promo_id', 36);
            $table->string('promocode');
            $table->float('discount_value');
            $table->float('discount_fixed');
            $table->float('initial_amount');
            $table->float('amount_charged');
            $table->string('currency');
            $table->string('service');
            $table->float('deadline_chat');

            $table->foreign('user_id')->references('uuid')->on('users');
            $table->foreign('promo_id')->references('uuid')->on('promo_codes');
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
        Schema::dropIfExists('promo_codes_logs');
    }
}
