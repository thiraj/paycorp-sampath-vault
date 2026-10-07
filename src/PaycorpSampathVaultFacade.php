<?php

namespace createch\PaycorpSampathVault;

use Illuminate\Support\Facades\Facade;

/**
 * Facade over the container-resolved PaycorpSampathVault singleton.
 *
 * The accessor string is unchanged, so `PaycorpSampathVault::initRequest()`
 * keeps working in applications that registered the alias by hand as well as
 * in those relying on package auto-discovery.
 *
 * @method static array initRequest(array $data)
 * @method static array realTimePayment(array $data)
 * @method static array completeRequest(array $data)
 * @method static string IPGLoaded()
 * @method static string version()
 * @method static \createch\PaycorpSampathVault\Configuration\GatewayConfiguration configuration()
 * @method static \createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient client()
 *
 * @see PaycorpSampathVault
 */
class PaycorpSampathVaultFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return PaycorpSampathVaultServiceProvider::CONTAINER_ALIAS;
    }
}
