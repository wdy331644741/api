<?php

namespace App\Console\Commands;

use App\Service\GlobalAttributes;
use App\Service\PerBaiService;
use Illuminate\Console\Command;

class Perbai extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Perbai';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取深证成指数';

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
    public function handle()
    {
        $key = PerBaiService::PERBAI_VERSION_END . PerBaiService::PERBAI_VERSION;

        $attr = GlobalAttributes::getItem($key);
        if ($attr && $attr['number'] == 0) {
            $price = PerBaiService::curlSina();
            if ($price) {
                $price = $price * 100;
                GlobalAttributes::setItem($key, $price);
            }
        }
    }
}
