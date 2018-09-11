<?php
/**
 * Created by PhpStorm.
 * User: zmadmin
 * Date: 9/11/18
 * Time: 2:56 PM
 */

namespace createch\PaycorpSampathVault;

use Illuminate\Support\Facades\Facade;


class PaycorpSampathVaultFacade extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'paycorp-sampath-vault';
    }

}