<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBbsCommentsCover extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_comments', function (Blueprint $table) {
            $table->text('cover')->nullable()->default(NULL)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bbs_comments', function (Blueprint $table) {
            $table->text('cover')->change();
        });
    }
}
