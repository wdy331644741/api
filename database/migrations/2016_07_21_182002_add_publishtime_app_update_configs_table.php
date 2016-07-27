<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPublishtimeAppUpdateConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_update_configs', function (Blueprint $table) {
            $table->dateTime('publish_time')->nullable()->defaullt(NUll);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_update_configs', function (Blueprint $table) {
            $table->dropColumn('publish_time');
        });
    }
}
