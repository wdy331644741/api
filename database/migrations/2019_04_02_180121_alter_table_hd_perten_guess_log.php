<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableHdPertenGuessLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_perten_guess_log', function (Blueprint $table) {
            $table->text('remark')->default('')->comment('备注');
            $table->decimal('money', 20, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_perten_guess_log', function (Blueprint $table) {
            $table->dropColumn('remark');
            $table->decimal('money')->default(0)->change();
        });
    }
}
