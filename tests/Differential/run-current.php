<?php

/**
 * Current side of the wire-parity harness: drives 2.x's public API with the
 * identical inputs, in the identical order, as run-legacy.php.
 *
 * msgId and requestDate are the only two fields 1.x generates
 * nondeterministically -- a GUID from mt_rand(), and date('Y-m-d H:i:s'). They
 * are replayed here from the legacy captures, per operation, so that every
 * other byte has to match on its own merit. Replaying them is also what makes
 * the HMAC header comparable: the digest covers msgId and requestDate, so
 * without replay every signature would differ for an uninteresting reason and
 * the comparison would prove nothing.
 *
 * Nothing else is replayed. Key order, key names, value types, quoting,
 * amounts, client ids, the auth token header and the signature itself are all
 * produced independently by 2.x and must come out identical.
 */

require __DIR__ . '/../../vendor/autoload.php';

use createch\PaycorpSampathVault\Configuration\GatewayConfiguration;
use createch\PaycorpSampathVault\Contracts\ClockInterface;
use createch\PaycorpSampathVault\Contracts\MessageIdGeneratorInterface;
use createch\PaycorpSampathVault\Http\CurlTransport;
use createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientComponent\CreditCard;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\DeleteTokenRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\RetrieveCardRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\StoreCardRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\UpdateCardRequest;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\VerifyTokenRequest;
use createch\PaycorpSampathVault\PaycorpSampathRealTimePayment;
use createch\PaycorpSampathVault\PaycorpSampathVault;
use createch\PaycorpSampathVault\Security\SensitiveDataRedactor;
use createch\PaycorpSampathVault\Security\Sha256HmacSigner;
use createch\PaycorpSampathVault\Support\ClientRuntime;

/** Replays a recorded sequence of values, one per call, in order. */
final class ReplaySequence
{
    /** @var string[] */
    private $values;

    /** @var int */
    private $cursor = 0;

    /** @param string[] $values */
    public function __construct(array $values)
    {
        $this->values = array_values($values);
    }

    /** @return string */
    public function next()
    {
        if (! array_key_exists($this->cursor, $this->values)) {
            fwrite(STDERR, "replay sequence exhausted at index {$this->cursor}\n");
            exit(1);
        }

        return $this->values[$this->cursor++];
    }
}

final class ReplayClock implements ClockInterface
{
    /** @var ReplaySequence */
    private $dates;

    public function __construct(ReplaySequence $dates)
    {
        $this->dates = $dates;
    }

    /** @return string */
    public function requestTimestamp()
    {
        return $this->dates->next();
    }
}

final class ReplayMessageIds implements MessageIdGeneratorInterface
{
    /** @var ReplaySequence */
    private $ids;

    public function __construct(ReplaySequence $ids)
    {
        $this->ids = $ids;
    }

    /** @return string */
    public function generate()
    {
        return $this->ids->next();
    }
}

$legacyDir = getenv('LEGACY_CAPTURES');

if ($legacyDir === false || ! is_dir($legacyDir)) {
    fwrite(STDERR, "LEGACY_CAPTURES is not set or not a directory\n");
    exit(1);
}

$files = glob($legacyDir . '/*.json');
sort($files);

$msgIds = array();
$dates = array();

foreach ($files as $file) {
    $capture = json_decode((string) file_get_contents($file), true);
    $body = json_decode(isset($capture['body']) ? $capture['body'] : '', true);

    if (! is_array($body) || ! isset($body['msgId'], $body['requestDate'])) {
        fwrite(STDERR, "legacy capture {$file} has no msgId/requestDate\n");
        exit(1);
    }

    $msgIds[] = $body['msgId'];
    $dates[] = $body['requestDate'];
}

if ($msgIds === array()) {
    fwrite(STDERR, "no legacy captures found in {$legacyDir}\n");
    exit(1);
}

$clock = new ReplayClock(new ReplaySequence($dates));
$messageIds = new ReplayMessageIds(new ReplaySequence($msgIds));

$values = array(
    'service_endpoint' => (string) getenv('SAMPATH_SERVICE_ENDPOINT'),
    'authtoken' => (string) getenv('SAMPATH_AUTHTOKEN'),
    'hmac_secret' => (string) getenv('SAMPATH_HMAC'),
    'return_url' => (string) getenv('SAMPATH_RETURN_URL'),
    'tokenize_client_id' => (string) getenv('SAMPATH_TOKENIZE_CLIENT_ID'),
    'purchase_client_id' => (string) getenv('SAMPATH_PURCHASE_CLIENT_ID'),
    'currency' => (string) getenv('SAMPATH_CURRENCY'),
    // The harness talks to a local capture server over plain HTTP. This flag
    // exists precisely so that is a deliberate, declared choice.
    'allow_insecure_endpoint' => true,
);

$configuration = new GatewayConfiguration($values);

$runtime = new ClientRuntime(
    new CurlTransport(new SensitiveDataRedactor()),
    new Sha256HmacSigner($configuration->hmacSecret()),
    $clock,
    $messageIds,
    new SensitiveDataRedactor()
);

$clientConfig = ClientConfig::fromGatewayConfiguration($configuration);
$client = new GatewayClient($clientConfig, $runtime);

$ops = require __DIR__ . '/operations.php';

$vault = new PaycorpSampathVault($configuration, $client);
$rawCard = new PaycorpSampathRealTimePayment($configuration, $client);

// 1 -- hosted redirect init
$vault->initRequest($ops['init']);

// 2 -- real-time payment against a stored token
$vault->realTimePayment($ops['realTimeToken']);

// 3 -- complete
$vault->completeRequest($ops['complete']);

// 4 -- real-time payment with a raw card
$rawCard->realTimePayment($ops['realTimeRawCard']);

// 5-9 -- the vault facade
$card = new CreditCard();
$card->setType($ops['storeCard']['card_type']);
$card->setHolderName($ops['storeCard']['card_holder_name']);
$card->setNumber($ops['storeCard']['card_number']);
$card->setExpiry($ops['storeCard']['expire_at']);
$card->setSecureId($ops['storeCard']['secure_id']);
$card->setSecureIdSupplied(true);

$store = new StoreCardRequest();
$store->setClientId($ops['storeCard']['clientId']);
$store->setClientRef($ops['storeCard']['clientRef']);
$store->setCreditCard($card);
$client->getVault()->storeCard($store);

$retrieve = new RetrieveCardRequest();
$retrieve->setClientId($ops['retrieveCard']['clientId']);
$retrieve->setToken($ops['retrieveCard']['token']);
$client->getVault()->retrieveCard($retrieve);

$update = new UpdateCardRequest();
$update->setClientId($ops['updateCard']['clientId']);
$update->setToken($ops['updateCard']['token']);
$update->setExpiryDate($ops['updateCard']['expiry_date']);
$client->getVault()->updateCard($update);

$verify = new VerifyTokenRequest();
$verify->setClientId($ops['verifyToken']['clientId']);
$verify->setToken($ops['verifyToken']['token']);
$client->getVault()->verifyToken($verify);

$delete = new DeleteTokenRequest();
$delete->setClientId($ops['deleteToken']['clientId']);
$delete->setToken($ops['deleteToken']['token']);
$client->getVault()->deleteToken($delete);

// 10-12 -- edge inputs: non-ASCII in a signed field, empty optional strings,
// and a zero service fee.
$vault->realTimePayment($ops['realTimeNonAscii']);
$vault->realTimePayment($ops['realTimeEmptyOptionals']);
$vault->initRequest($ops['initZeroFee']);

echo "current run complete\n";
