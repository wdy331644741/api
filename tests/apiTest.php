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

    public function testRuleList(){
        $this->get('/activity/rule-list/1')
            ->seeJsonStructure([
                'error_code',
                'data'=> [
                  '*'=>['id','activity_id','rule_type']
                ],
            ]);
    }

    public function testPostRuleAdd(){
        $this->post('/activity/rule-add/register',['activity_id'=>2,'max_time'=>'2016-03-03 15:16:17','min_time'=>'2016-02-03 15:16:17'])
            ->seeJsonStructure([
                'error_code',
                'data' =>['insert_id'],
            ]);
    }

    public function testPostRelease(){
        $this->post('/activity/release',['id'=>2])
            ->seeJson([
                'error_code'=>0,
            ]);
    }
}
