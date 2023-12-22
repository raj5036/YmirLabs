<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRegionToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('region', ['no', 'se', 'uk', 'co'])->after('locale');
        });

        // Update existing records based on their locale (nb => no, en => co), at present we have two domain
        DB::statement(
            DB::raw(
                "UPDATE users SET region =
                            (
                                CASE
                                    WHEN locale='nb'
                                    THEN ?
                                    WHEN locale='en'
                                    THEN ?
                                END
                            )"
            ),
            array('no', 'co')
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('region');
        });
    }
}
