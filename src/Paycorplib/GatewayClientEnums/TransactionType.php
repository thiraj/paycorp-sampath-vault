<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientEnums;

class TransactionType {

    public static $PURCHASE = "PURCHASE";
    public static $AUTHORISATION = "AUTHORISATION";
    public static $COMPLETION = "COMPLETION";
    public static $REFUND = "REFUND";
    public static $ORPHANED_REFUND = "ORPHANED_REFUND";
    public static $REVERSAL = "REVERSAL";
    public static $TOKEN = "TOKEN";
    public static $CREDIT = "CREDIT";
    public static $DEBIT = "DEBIT";

    private function __construct() {
        
    }

}
