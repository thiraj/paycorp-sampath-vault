<?php

namespace createch\PaycorpSampathVault\Test;

use createch\PaycorpSampathVault\PaycorpSampathVaultFacade;
use createch\PaycorpSampathVault\PaycorpSampathVaultServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Boots a real Laravel application around the package.
 *
 * Written against only the Testbench APIs that are stable from 3.5 to 11
 * (getPackageProviders, getPackageAliases, getEnvironmentSetUp), so the same
 * file runs on every Laravel version in the support matrix.
 */
abstract class TestCase extends OrchestraTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return string[]
     */
    protected function getPackageProviders($app)
    {
        return array(PaycorpSampathVaultServiceProvider::class);
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string,string>
     */
    protected function getPackageAliases($app)
    {
        return array('PaycorpSampathVault' => PaycorpSampathVaultFacade::class);
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('paycorp-sampath-vault.service_endpoint', 'https://sampath.example.test/proxy');
        $app['config']->set('paycorp-sampath-vault.authtoken', 'test-auth-token');
        $app['config']->set('paycorp-sampath-vault.hmac_secret', 'test-hmac-secret');
        $app['config']->set('paycorp-sampath-vault.currency', 'LKR');
        $app['config']->set('paycorp-sampath-vault.return_url', 'https://merchant.example.test/return');
        $app['config']->set('paycorp-sampath-vault.tokenize_client_id', '99990001');
        $app['config']->set('paycorp-sampath-vault.purchase_client_id', '99990002');
    }
}
