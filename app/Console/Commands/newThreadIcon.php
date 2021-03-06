<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\Models\Bbs\Thread;
use App\Models\Bbs\ThreadSection;
class newThreadIcon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newThreadIcon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Thread hot icon ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public static  function handle()
    {
        //
       //DB::
        //处理最热贴
        Thread::where(["new"=>1,"isverify"=>1])->update(["new"=>0]);
        $res = Thread::where(["isverify"=>1])->orderBy('updated_at','desc')->limit(1,1)->first();
        Thread::where(["id"=>$res->id])->update(["new"=>1]);

        //处理最hot
        $threadSectionInfo = ThreadSection::all()->toArray();

        foreach ($threadSectionInfo as $key=>$v){

            Thread::where(["ishot"=>1,"isverify"=>1,'type_id'=>$threadSectionInfo[$key]['id']])->update(["ishot"=>0]);

            $resHot = Thread::where(["isverify"=>1,"type_id"=>$threadSectionInfo[$key]['id']])->orderBy('views','desc')->limit(1,1)->first();

            Thread::where(["id"=>$resHot['id']])->update(["ishot"=>1]);
        }




    }
}
