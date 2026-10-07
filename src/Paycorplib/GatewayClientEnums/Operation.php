<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums;

/**
 * Gateway operation names.
 *
 * Kept as public static properties rather than real PHP enums or constants:
 * callers reference them as Operation::$PAYMENT_INIT, and changing that to
 * Operation::PAYMENT_INIT would break every existing integration. A typed
 * enum is a 3.x change.
 *
 * $PAYMENT_BATCH is declared here for the first time. Payment::batch()
 * referenced it without it existing, so the method raised
 * "Access to undeclared static property" on every PHP version since 7.0.
 */
class Operation {

    public static $PAYMENT_INIT = "PAYMENT_INIT";
    public static $PAYMENT_COMPLETE = "PAYMENT_COMPLETE";
    public static $PAYMENT_REAL_TIME = "PAYMENT_REAL_TIME";
    public static $PAYMENT_BATCH = "PAYMENT_BATCH";

    public static $VAULT_STORE_CARD = "VAULT_STORE_CARD";
    public static $VAULT_RETRIEVE_CARD = "VAULT_RETRIEVE_CARD";
    public static $VAULT_UPDATE_CARD = "VAULT_UPDATE_CARD";
    public static $VAULT_VERIFY_TOKEN = "VAULT_VERIFY_TOKEN";
    public static $VAULT_DELETE_TOKEN = "VAULT_DELETE_TOKEN";

    private function __construct() {
    }

}
