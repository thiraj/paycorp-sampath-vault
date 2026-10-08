<?php

/**
 * The fixed inputs both sides of the wire-parity harness feed in, in order.
 *
 * Index N here is capture NNN.json on both runs. Nothing in here may vary
 * between runs or the comparison is meaningless -- no random values, no
 * timestamps, no autoincrementing references.
 */

return array(
    'init' => array(
        'clientRef' => 'ORDER-PARITY',
        'comment' => 'parity harness',
        'total_amount' => 150000,
        'service_fee_amount' => 2500,
        'payment_amount' => 147500,
    ),
    'realTimeToken' => array(
        'token' => 'TOK-PARITY-1',
        'expire_at' => '12/28',
        'amount' => 150000,
        'clientRef' => 'ORDER-PARITY',
        'comment' => 'parity harness',
    ),
    'complete' => array(
        'reqid' => 'REQ-PARITY-1',
    ),
    'realTimeRawCard' => array(
        'card_type' => 'VISA',
        'card_holder_name' => 'PARITY HARNESS',
        'card_number' => '4111111111111111',
        'expire_at' => '12/28',
        'secure_id' => '123',
        'amount' => 150000,
        'clientRef' => 'ORDER-PARITY',
        'comment' => 'parity harness',
    ),
    'storeCard' => array(
        'clientId' => '99990001',
        'clientRef' => 'ORDER-PARITY',
        'card_type' => 'VISA',
        'card_holder_name' => 'PARITY HARNESS',
        'card_number' => '4111111111111111',
        'expire_at' => '12/28',
        'secure_id' => '123',
    ),
    'retrieveCard' => array(
        'clientId' => '99990001',
        'token' => 'TOK-PARITY-1',
    ),
    'updateCard' => array(
        'clientId' => '99990001',
        'token' => 'TOK-PARITY-1',
        'expiry_date' => '01/30',
    ),
    'verifyToken' => array(
        'clientId' => '99990001',
        'token' => 'TOK-PARITY-1',
    ),
    'deleteToken' => array(
        'clientId' => '99990001',
        'token' => 'TOK-PARITY-1',
    ),

    // ---------------------------------------------------------------------
    // Edge inputs. One happy path per operation would leave the interesting
    // cases unproven, and these are where a rewrite is most likely to drift.
    // ---------------------------------------------------------------------

    // Non-ASCII in a signed field. 1.x ran the payload through utf8_decode();
    // 2.x reimplements that bit-exactly in Latin1Encoder because utf8_decode()
    // is going away in PHP 9. The golden vectors pin the encoder in isolation
    // -- this pins it through the whole stack, in a real signed request.
    'realTimeNonAscii' => array(
        'token' => 'TOK-PARITY-1',
        'expire_at' => '12/28',
        'amount' => 150000,
        'clientRef' => 'ORDER-CAFÉ',
        'comment' => "Côte d'Ivoire — café ☕ 日本語",
    ),

    // Empty optional strings. 1.x wrote these as `$data['x'] ? $data['x'] : ''`.
    'realTimeEmptyOptionals' => array(
        'token' => 'TOK-PARITY-1',
        'expire_at' => '12/28',
        'amount' => 1,
        'clientRef' => '',
        'comment' => '',
    ),

    // Zero service fee, and a total that equals the payment amount.
    'initZeroFee' => array(
        'clientRef' => 'ORDER-ZERO',
        'comment' => '',
        'total_amount' => 100,
        'service_fee_amount' => 0,
        'payment_amount' => 100,
    ),
);
