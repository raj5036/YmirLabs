<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromocodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->char('uuid', 36)->primary();
            $table->string('promocode')->index();
            $table->mediumText('description')->nullable();
            $table->float('discount_12hrs')->default(0.0);
            $table->float('discount_24hrs')->default(0.0);
            $table->float('discount_video')->default(0.0);

            // Whether the discount is fixed or prcentage discount
            $table->boolean('discount_fixed')->default(false);
            // Currencies for which the discount code is applicable
            $table->text('applicable_currencies');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('reusable')->default(true);
            $table->boolean('one_time_code')->default(false);
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
        Schema::dropIfExists('promocode');
    }
}
