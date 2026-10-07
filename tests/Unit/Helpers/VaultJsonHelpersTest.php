<?php

namespace createch\PaycorpSampathVault\Test\Unit\Helpers;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\DeleteTokenJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\RetrieveCardJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\StoreCardJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\UpdateCardJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\VerifyTokenJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils\IJsonHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * REGRESSION: three of these helpers read $responseData[responseData][responseCode]
 * with the array keys UNQUOTED. PHP 7 resolved the bare words to strings with a
 * notice, so they happened to work; PHP 8 made undefined constants a fatal
 * Error, so deleteToken(), updateCard() and verifyToken() were completely
 * broken on PHP 8 before this fix.
 */
class VaultJsonHelpersTest extends TestCase
{
    /**
     * @dataProvider responseCodeHelpers
     */
    #[DataProvider('responseCodeHelpers')]
    public function testItParsesAResponseWithoutRaisingAnUndefinedConstantError($helperClass)
    {
        $helper = new $helperClass();

        $response = $helper->fromJson(array(
            'responseData' => array('responseCode' => '00', 'responseText' => 'APPROVED'),
        ));

        $this->assertSame('00', $response->getResponseCode());
        $this->assertSame('APPROVED', $response->getResponseText());
    }

    /**
     * @dataProvider responseCodeHelpers
     */
    #[DataProvider('responseCodeHelpers')]
    public function testItToleratesAnEnvelopeWithNoFields($helperClass)
    {
        $helper = new $helperClass();

        $response = $helper->fromJson(array('responseData' => array()));

        $this->assertNull($response->getResponseCode());
        $this->assertNull($response->getResponseText());
    }

    /**
     * @dataProvider allHelpers
     */
    #[DataProvider('allHelpers')]
    public function testEveryHelperDeclaresTheInterfaceBaseFacadeReliesOn($helperClass)
    {
        $this->assertInstanceOf(IJsonHelper::class, new $helperClass());
    }

    /**
     * @return array<string,array{0:string}>
     */
    public static function responseCodeHelpers()
    {
        return array(
            'delete token (was fatal on PHP 8)' => array(DeleteTokenJsonHelper::class),
            'update card (was fatal on PHP 8)' => array(UpdateCardJsonHelper::class),
            'verify token (was fatal on PHP 8)' => array(VerifyTokenJsonHelper::class),
            'retrieve card' => array(RetrieveCardJsonHelper::class),
        );
    }

    /**
     * @return array<string,array{0:string}>
     */
    public static function allHelpers()
    {
        return array_merge(self::responseCodeHelpers(), array(
            'store card' => array(StoreCardJsonHelper::class),
        ));
    }

    public function testStoreCardParsesTheIssuedToken()
    {
        $helper = new StoreCardJsonHelper();

        $response = $helper->fromJson(array(
            'responseData' => array('token' => 'TOK123', 'responseCode' => '00', 'responseText' => 'OK'),
        ));

        $this->assertSame('TOK123', $response->getToken());
    }
}
