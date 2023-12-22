<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUserIdNullablePromoCodeLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_code_logs', function (Blueprint $table) {
            $table->float('deadline_chat')->nullable()->change();
            $table->dropForeign('promo_code_logs_user_id_foreign');
            DB::statement('ALTER TABLE `promo_code_logs` MODIFY `user_id` CHAR(36) NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_code_logs', function (Blueprint $table) {
            //
        });
    }
}
