<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableExamine20180710 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examine', function (Blueprint $table) {
            $table->string('app_name')->after("id")->default('')->comment("包名");
            $table->tinyInteger('type',false,true)->after("versions")->default(0)->comment("类型1主包，2马甲包");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('examine', function (Blueprint $table) {
            $table->dropColumn(["app_name","type"]);
        });
    }
}
