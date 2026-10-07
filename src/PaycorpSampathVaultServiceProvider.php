<?php

namespace createch\PaycorpSampathVault;

use createch\PaycorpSampathVault\Configuration\ConfigurationFactory;
use createch\PaycorpSampathVault\Configuration\GatewayConfiguration;
use createch\PaycorpSampathVault\Contracts\ClockInterface;
use createch\PaycorpSampathVault\Contracts\EncoderInterface;
use createch\PaycorpSampathVault\Contracts\HostedPaymentGatewayInterface;
use createch\PaycorpSampathVault\Contracts\HttpTransportInterface;
use createch\PaycorpSampathVault\Contracts\MessageIdGeneratorInterface;
use createch\PaycorpSampathVault\Contracts\RealTimePaymentGatewayInterface;
use createch\PaycorpSampathVault\Contracts\RedactorInterface;
use createch\PaycorpSampathVault\Contracts\SignerInterface;
use createch\PaycorpSampathVault\Http\CurlTransport;
use createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Security\Latin1Encoder;
use createch\PaycorpSampathVault\Security\SensitiveDataRedactor;
use createch\PaycorpSampathVault\Security\Sha256HmacSigner;
use createch\PaycorpSampathVault\Support\ClientRuntime;
use createch\PaycorpSampathVault\Support\RandomMessageIdGenerator;
use createch\PaycorpSampathVault\Support\SystemClock;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the gateway with the Laravel container.
 *
 * Compatible with Laravel 5.5 through 13: it uses only ServiceProvider APIs
 * that have been stable across that whole range (mergeConfigFrom, publishes,
 * singleton, bind, alias), and is auto-discovered via composer.json's
 * extra.laravel.providers. Registering it manually in config/app.php as well
 * is harmless -- the container short-circuits a provider it has already
 * registered -- so existing installations need no edit.
 *
 * WHAT CHANGED
 * ------------
 * publishes() used to target config_path('paycorp-sampath-vault'), a
 * DIRECTORY. Laravel only autoloads top-level config/*.php files, so the
 * published file was never read by anything; and there was no publish tag, so
 * `vendor:publish --tag=...` could not find it either. It now publishes a
 * single file to config/paycorp-sampath-vault.php under the
 * "paycorp-sampath-vault-config" tag.
 *
 * Every interface is bound to its implementation so an application can
 * override any single collaborator -- the transport, the clock, the redactor
 * -- without touching this package.
 */
class PaycorpSampathVaultServiceProvider extends ServiceProvider
{
    /** Published config file name, and the config repository key. */
    const CONFIG_KEY = 'paycorp-sampath-vault';

    /** Container alias kept for the facade and for existing app()->make() calls. */
    const CONTAINER_ALIAS = 'paycorp-sampath-vault';

    /**
     * Bootstrap the package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                array($this->configFilePath() => $this->publishedConfigPath()),
                self::CONFIG_KEY . '-config'
            );
        }
    }

    /**
     * Register the package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->configFilePath(), self::CONFIG_KEY);

        $this->registerConfiguration();
        $this->registerCollaborators();
        $this->registerGateways();
    }

    /**
     * Services this provider provides, so Laravel can defer it where supported.
     *
     * @return string[]
     */
    public function provides()
    {
        return array(
            self::CONTAINER_ALIAS,
            GatewayConfiguration::class,
            ClientConfig::class,
            GatewayClient::class,
            ClientRuntime::class,
            PaycorpSampathVault::class,
            PaycorpSampathRealTimePayment::class,
            HttpTransportInterface::class,
            SignerInterface::class,
            ClockInterface::class,
            MessageIdGeneratorInterface::class,
            RedactorInterface::class,
            EncoderInterface::class,
            HostedPaymentGatewayInterface::class,
            RealTimePaymentGatewayInterface::class,
        );
    }

    /**
     * @return void
     */
    private function registerConfiguration()
    {
        // Resolved from the config repository, never from env() at request
        // time: env() returns null once `php artisan config:cache` has run.
        $this->app->singleton(GatewayConfiguration::class, function ($app) {
            $values = $app['config']->get(self::CONFIG_KEY, array());

            return ConfigurationFactory::resolve(is_array($values) ? $values : array());
        });

        $this->app->singleton(ClientConfig::class, function ($app) {
            return ClientConfig::fromGatewayConfiguration($app->make(GatewayConfiguration::class));
        });
    }

    /**
     * @return void
     */
    private function registerCollaborators()
    {
        $this->app->singleton(EncoderInterface::class, function () {
            return new Latin1Encoder();
        });

        $this->app->singleton(RedactorInterface::class, function () {
            return new SensitiveDataRedactor();
        });

        $this->app->singleton(HttpTransportInterface::class, function ($app) {
            return new CurlTransport($app->make(RedactorInterface::class));
        });

        $this->app->singleton(SignerInterface::class, function ($app) {
            return new Sha256HmacSigner(
                $app->make(GatewayConfiguration::class)->hmacSecret(),
                $app->make(EncoderInterface::class)
            );
        });

        $this->app->singleton(ClockInterface::class, function ($app) {
            return new SystemClock($app->make(GatewayConfiguration::class)->timezone());
        });

        $this->app->singleton(MessageIdGeneratorInterface::class, function () {
            return new RandomMessageIdGenerator();
        });

        $this->app->singleton(ClientRuntime::class, function ($app) {
            return new ClientRuntime(
                $app->make(HttpTransportInterface::class),
                $app->make(SignerInterface::class),
                $app->make(ClockInterface::class),
                $app->make(MessageIdGeneratorInterface::class),
                $app->make(RedactorInterface::class)
            );
        });
    }

    /**
     * @return void
     */
    private function registerGateways()
    {
        $this->app->singleton(GatewayClient::class, function ($app) {
            return new GatewayClient(
                $app->make(ClientConfig::class),
                $app->make(ClientRuntime::class)
            );
        });

        $this->app->singleton(PaycorpSampathVault::class, function ($app) {
            return new PaycorpSampathVault(
                $app->make(GatewayConfiguration::class),
                $app->make(GatewayClient::class)
            );
        });

        $this->app->singleton(PaycorpSampathRealTimePayment::class, function ($app) {
            return new PaycorpSampathRealTimePayment(
                $app->make(GatewayConfiguration::class),
                $app->make(GatewayClient::class)
            );
        });

        // Depend on the narrow interface, not the concrete class.
        $this->app->alias(PaycorpSampathVault::class, HostedPaymentGatewayInterface::class);
        $this->app->alias(PaycorpSampathVault::class, RealTimePaymentGatewayInterface::class);

        // The string alias the facade resolves, unchanged since 1.x.
        $this->app->alias(PaycorpSampathVault::class, self::CONTAINER_ALIAS);
    }

    /**
     * @return string
     */
    private function configFilePath()
    {
        return dirname(__DIR__) . '/config/' . self::CONFIG_KEY . '.php';
    }

    /**
     * config_path() is unavailable in some bootstrapped contexts, so fall back
     * to the application base path rather than failing to register.
     *
     * @return string
     */
    private function publishedConfigPath()
    {
        $file = self::CONFIG_KEY . '.php';

        if (function_exists('config_path')) {
            return config_path($file);
        }

        return $this->app->basePath('config' . DIRECTORY_SEPARATOR . $file);
    }
}
