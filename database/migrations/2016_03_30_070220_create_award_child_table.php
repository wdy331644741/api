<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAwardChildTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //加息券
        Schema::create('award_coupon', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',40);
            $table->float('rate');
            $table->string('length');//ALL , LOCAL
            $table->timestamp('local_satrt'); //LOCAL
            $table->timestamp('local_end'); //BETWEEN
            $table->string('validity'); //MARK ,DAYS ,BETWEEN
            $table->timestamp('days_number');
            $table->timestamp('validity_satrt');
            $table->timestamp('validity_end');
            $table->integer('entry');
            $table->integer('term_type'); // MONTH ,DAY
            $table->string('month_value');
            $table->integer('term_day_min');
            $table->integer('term_day_max');
            $table->string('project_type');//多选
            $table->integer('repayment_type');//单选
            $table->integer('interest_type');//--
            $table->integer('product_type');//category ，appoint
            $table->string('category_value');
            $table->string('appoint_value');
            $table->string('platform');
            $table->string('channel');
            $table->timestamps();
        });

        //百分比红包
        Schema::create('award_proportion_redpack', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',40);
            $table->float('max_money');
            $table->float('ratio_value');
            $table->string('validity'); //MARK ,DAYS ,BETWEEN
            $table->timestamp('days_number');
            $table->timestamp('validity_satrt');
            $table->timestamp('validity_end');
            $table->integer('entry');
            $table->integer('term_type'); // MONTH ,DAY
            $table->string('month_value');
            $table->integer('term_day_min');
            $table->integer('term_day_max');
            $table->string('project_type');//多选
            $table->integer('repayment_type');//单选
            $table->integer('interest_type');//--
            $table->integer('product_type');//category ，appoint
            $table->string('category_value');
            $table->string('appoint_value');
            $table->string('platform');
            $table->string('channel');
            $table->timestamps();
        });


        //满减红包
        Schema::create('award_redpack', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',40);
            $table->float('money');
            $table->string('validity'); //MARK ,DAYS ,BETWEEN
            $table->timestamp('days_number');
            $table->timestamp('validity_satrt');
            $table->timestamp('validity_end');
            $table->integer('entry');
            $table->integer('term_type'); // MONTH ,DAY
            $table->string('month_value');
            $table->integer('term_day_min');
            $table->integer('term_day_max');
            $table->string('project_type');//多选
            $table->integer('repayment_type');//单选
            $table->integer('interest_type');//--
            $table->integer('product_type');//category ，appoint
            $table->string('category_value');
            $table->string('appoint_value');
            $table->string('platform');
            $table->string('channel');
            $table->timestamps();
        });
        //体验金
        Schema::create('award_trial_gold', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',40);
            $table->float('money');
            $table->integer('lever');
            $table->string('validity'); //MARK ,DAYS ,BETWEEN
            $table->timestamp('days_number');
            $table->timestamp('validity_satrt');
            $table->timestamp('validity_end');
            $table->string('platform');
            $table->string('channel');
            $table->timestamps();
        });

        //用户积分
        Schema::create('award_user_numerical', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',40);
            $table->float('money');
            $table->integer('lever');
           /*$table->string('validity'); //MARK ,DAYS ,BETWEEN
            $table->timestamp('days_number');
            $table->timestamp('validity_satrt');
            $table->timestamp('validity_end');
            $table->string('platform');
            $table->string('channel');*/
            $table->timestamps();
        });

        //实物
        Schema::create('award_goods', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',40);
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
        Schema::drop('award_coupon');
        Schema::drop('award_proportion_redpack');
        Schema::drop('award_redpack');
        Schema::drop('award_trial_gold');
        Schema::drop('award_user_numerical');
        Schema::drop('award_goods');
    }
}
