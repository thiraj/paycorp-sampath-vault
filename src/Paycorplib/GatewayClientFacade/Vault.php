<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientFacade;

use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums\Operation;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\DeleteTokenJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\RetrieveCardJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\StoreCardJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\UpdateCardJsonHelper;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientHelpers\VerifyTokenJsonHelper;
use createch\PaycorpSampathVault\Support\ClientRuntime;

/**
 * Card vault operations: store, retrieve, update, verify and delete a token.
 */
final class Vault extends BaseFacade {

    /**
     * @param  ClientConfig        $config
     * @param  ClientRuntime|null  $runtime
     */
    public function __construct($config, ?ClientRuntime $runtime = null) {
        parent::__construct($config, $runtime);
    }

    /**
     * @param  \createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\StoreCardRequest  $request
     * @return \createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\StoreCardResponse
     */
    public function storeCard($request) {
        return $this->process($request, Operation::$VAULT_STORE_CARD, new StoreCardJsonHelper());
    }

    /**
     * @param  \createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\RetrieveCardRequest  $request
     * @return \createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\RetrieveCardResponse
     */
    public function retrieveCard($request) {
        return $this->process($request, Operation::$VAULT_RETRIEVE_CARD, new RetrieveCardJsonHelper());
    }

    /**
     * @param  \createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\UpdateCardRequest  $request
     * @return \createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\UpdateCardResponse
     */
    public function updateCard($request) {
        return $this->process($request, Operation::$VAULT_UPDATE_CARD, new UpdateCardJsonHelper());
    }

    /**
     * @param  \createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\VerifyTokenRequest  $request
     * @return \createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\VerifyTokenResponse
     */
    public function verifyToken($request) {
        return $this->process($request, Operation::$VAULT_VERIFY_TOKEN, new VerifyTokenJsonHelper());
    }

    /**
     * @param  \createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\DeleteTokenRequest  $request
     * @return \createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\DeleteTokenResponse
     */
    public function deleteToken($request) {
        return $this->process($request, Operation::$VAULT_DELETE_TOKEN, new DeleteTokenJsonHelper());
    }

}
