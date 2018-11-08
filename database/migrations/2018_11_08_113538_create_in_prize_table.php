<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInPrizeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('in_prizes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('type_id')->index();
            $table->string('name');
            $table->integer('price');
            $table->integer('stock')->comment("库存");
            $table->string('list_img')->comment("列表图");
            $table->string('detail_img')->comment("详情页头图");
            $table->string('des_img')->nullable()->default(NULL)->comment("商品介绍图");
            $table->tinyInteger('istop')->default(0)->comment("推荐");
            $table->tinyInteger('sort')->default(0)->comment("排序");
            $table->softDeletes();
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
        Schema::drop('in_prizes');
    }
}
