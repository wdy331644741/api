<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsadminToBbsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_users', function (Blueprint $table) {
            $table->tinyInteger('isadmin')->default(0)->comment('是否是后台用户 0:（默认）否，1:是');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bbs_users', function (Blueprint $table) {
            $table->dropColumn('isadmin');
        });
    }
}
