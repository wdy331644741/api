<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ProxyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if(!env('PROXY_ENABLE', false)){
            return;
        }

        $request = $this->app['request'];
        $proxyStr = env('PROXIES', '');

        if( $proxyStr === '*' )
        {
            $proxies = array( $request->getClientIp() );
        }else {
            $proxies=explode(',', $proxyStr);
        }
        
        $request->setTrustedProxies( $proxies );
        //
    }
}
