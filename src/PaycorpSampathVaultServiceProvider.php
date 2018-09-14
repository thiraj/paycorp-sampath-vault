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
        $this->publishes([
            __DIR__ . '/config' => config_path('paycorp-sampath-vault'),
        ]);
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/PaycorpSampathVault.php', 'paycorp-sampath-vault'
        );

        $this->app->singleton(PaycorpSampathVault::class, function () {
            return new PaycorpSampathVault();
        });
        $this->app->alias(PaycorpSampathVault::class, 'paycorp-sampath-vault');
    }

}