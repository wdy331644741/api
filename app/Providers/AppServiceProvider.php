<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        \DB::listen(function(QueryExecuted $event) {
            $sql = str_replace("?", "'%s'", $event->sql);

            $log = vsprintf($sql, $event->bindings);

            $log = '[' . date('Y-m-d H:i:s') . '] ' . $log . "\r\n";
            $filepath = storage_path('logs'.DIRECTORY_SEPARATOR . date('Y-m-d') .'.sql.log');
            file_put_contents($filepath, $log, FILE_APPEND);
        });

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
