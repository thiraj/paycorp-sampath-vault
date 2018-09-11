<?php

namespace createch\PaycorpSampathVault\Test;

use createch\PaycorpSampathVault\PaycorpSampathVaultFacade;
use createch\PaycorpSampathVault\PaycorpSampathVaultServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * Load package service provider
     * @param  \Illuminate\Foundation\Application $app
     * @return lcreatech\PaycorpSampathVault\PaycorpSampathVaultServiceProvider
     */
    protected function getPackageProviders($app)
    {
        return [PaycorpSampathVaultServiceProvider::class];
    }
    /**
     * Load package alias
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'PaycorpSampathVault' => PaycorpSampathVaultFacade::class,
        ];
    }
}