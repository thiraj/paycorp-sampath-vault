<?php

namespace createch\PaycorpSampathVault;
use Illuminate\Support\ServiceProvider;

class PaycorpSampathVaultServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PaycorpSampathVault::class, function () {
            return new PaycorpSampathVault();
        });
        $this->app->alias(PaycorpSampathVault::class, 'paycorp-sampath-vault');
    }

}