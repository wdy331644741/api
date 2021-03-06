<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->tinyInteger('type_id');
            $table->text('des')->nullable()->default(NULL);
            $table->softDeletes();//模型需要定义删除
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('activity_groups');
    }
}
