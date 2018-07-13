<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuestion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('q_questions', function (Blueprint $table) {
            //
            $table->string('icon')->comment('按钮类型');
            $table->tinyInteger('type')->default(0)->comment('按钮类型');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('q_questions', function (Blueprint $table) {
            //
            $table->dropColumn(["icon", "type"]);
        });
    }
}
