<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBbsPmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bbs_pms', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collate = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('from_user_id')->comment('from_user_id为0时，为官方回复消息');
            $table->integer('tid')->nullable()->default(NULL);
            $table->integer('cid')->nulllable()->default(NULL)->comment('回复配置id,cid为0时，content为评论内容');
            $table->string('content');
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
        Schema::drop('bbs_pms');
    }
}
