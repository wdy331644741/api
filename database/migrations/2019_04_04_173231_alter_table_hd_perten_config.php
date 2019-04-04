<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableHdPertenConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_perten_config', function (Blueprint $table) {
            $table->decimal('guess_award',20, 2)->unsigned()->default(0)->comment('天天猜瓜分金额')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_perten_config', function (Blueprint $table) {
            $table->decimal('guess_award',10, 2)->unsigned()->default(0)->comment('天天猜瓜分金额')->change();
        });
    }
}
