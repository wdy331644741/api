<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class apiTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }

    public function testGetRuleList(){
        $this->get('/rule/rulelist/3')
            ->seeJsonStructure([
                'error_code',
                'data' =>[
                    '*'=>['id','rule_type'],
                ],
            ]);
    }

    public function testRuleAdd(){
        $this->post('/rule/add/register',['activity_id'=>3,'max_time'=>'2016-03-03 15:16:17','min_time'=>'2016-02-03 15:16:17'])
            ->seeJsonStructure([
                'error_code',
                'data' =>['insert_id'],
            ]);
    }

    public function testRelease(){
        $this->post('/activity/release',['id'=>3])
            ->seeJson([
                'error_code'=>0,
            ]);
    }
}
