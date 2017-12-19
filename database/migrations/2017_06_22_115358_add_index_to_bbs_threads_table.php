<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToBbsThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_threads', function (Blueprint $table) {
            $table->index('type_id');
            $table->index('istop');
            $table->index('isgreat');
            $table->index('ishot');
            $table->index('isverify');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bbs_threads', function (Blueprint $table) {
            //
        });
    }
}
