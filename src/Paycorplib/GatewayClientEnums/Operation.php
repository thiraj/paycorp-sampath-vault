<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums;

class Operation {

    public static $PAYMENT_INIT = "PAYMENT_INIT";
    public static $PAYMENT_COMPLETE = "PAYMENT_COMPLETE";
    public static $PAYMENT_REAL_TIME = "PAYMENT_REAL_TIME";
    
    public static $VAULT_STORE_CARD = "VAULT_STORE_CARD";
    public static $VAULT_RETRIEVE_CARD = "VAULT_RETRIEVE_CARD";
    public static $VAULT_UPDATE_CARD = "VAULT_UPDATE_CARD";
    public static $VAULT_VERIFY_TOKEN = "VAULT_VERIFY_TOKEN";
    public static $VAULT_DELETE_TOKEN = "VAULT_DELETE_TOKEN";
    
    private function __construct() {
        
    }

}
