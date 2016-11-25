<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use \GuzzleHttp\Client;
use Config;
use App\Models\Cqssc as CqsscModel;
use Illuminate\Foundation\Bus\DispatchesJobs;

class Cqssc extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    use DispatchesJobs;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
            
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
        $url = Config::get('oneyuan.cqssc');
        $client = new Client(); 
        $res = $client->request('GET', $url);
        $jsonRes = json_decode($res->getBody(), true);
        if(!isset($jsonRes['data']) || !is_array($jsonRes['data'])){
            var_dump($jsonRes);
            return;
        }
        foreach($jsonRes['data'] as $value) {
            $res = CqsscModel::where('expect', $value['expect'])->first();
            if($res) {
                // 如果上次更新和本次更新相差小于30秒, 丢掉任务
                if(time() - strtotime($res->updated_at) < 30) {
                    return;    
                }
                $res->updated_at = date('Y-m-d H:i:s');
                $res->save();
                continue;
            }
            $codeArr = explode(',', $value['opencode']);
            $openCode = implode('', $codeArr);
            
            CqsscModel::create([
                'expect'=> $value['expect'], 
                'opencode' => $openCode, 
                'opentime' => $value['opentime'],
                'opentimestamp' => $value['opentimestamp']
            ]);
        }
        $this->dispatch((new Cqssc())->onQueue('oneyuan')->delay(60));
    }
}
