# Paycorp Sampath Vault

Sampath Bank (Paycorp) Internet Payment Gateway client for Laravel and plain PHP:
hosted redirect payments, real-time payments, and card tokenisation (vault).

[![tests](https://github.com/thiraj/paycorp-sampath-vault/actions/workflows/tests.yml/badge.svg)](https://github.com/thiraj/paycorp-sampath-vault/actions/workflows/tests.yml)

---

## Compatibility

**One release line covers every supported Laravel version.** There is no separate
tag per framework version to pick between — install `^2.0` and Composer resolves it
against whatever Laravel you are on.

| Package | PHP | Laravel | Status |
|---|---|---|---|
| `^2.0` | 7.3 – 8.4 | **5.5 · 6 · 7 · 8 · 9 · 10 · 11 · 12 · 13** | Supported |
| `^1.4` | 5.6 – 8.1 | 5.5 – 8 | End of life, see the security notice below |

This breadth is possible because the package touches only `ServiceProvider`,
`Facade` and the config repository — APIs that have not changed across that whole
range. The CI matrix runs the suite on PHP 7.4 / 8.0 / 8.1 / 8.2 / 8.3 / 8.4
against Laravel 8 through 13, with both `--prefer-lowest` and `--prefer-stable`.
Laravel 5.5 – 7 remain within the declared Composer constraint and are syntax- and
static-analysis-checked, but are not exercised by the integration suite.

---

## Security notice for 1.x users

Releases up to and including `v1.4` committed **live production credentials** to
the repository, and those tags are published on Packagist. If you ever installed
this package at 1.x, treat the following as compromised and ask Sampath/Paycorp to
rotate them:

- the `authtoken` and `hmac_secret` that shipped in `src/config/PaycorpSampathVault.php`
- the merchant client ids `14002149` and `14002150`
- the credentials in the `src/Paycorplib/GatewayIT/*` sample scripts, one of which
  pointed at the **production** endpoint

Rewriting git history does not undo this — the tags are already mirrored by
Packagist and by everyone who ran `composer install`. Rotation is the only fix.

1.x also disabled TLS certificate verification (`CURLOPT_SSL_VERIFYPEER => false`),
so every card number and CVV it sent travelled over a connection that accepted any
certificate. 2.x verifies certificates by default. See [UPGRADE.md](UPGRADE.md).

---

## Requirements

- PHP 7.3+
- `ext-curl`, `ext-json`
- Composer

## Installation

```bash
composer require createch/paycorp-sampath-vault
```

The service provider and the `PaycorpSampathVault` facade alias are registered
automatically by Laravel package discovery. If you previously listed them by hand
in `config/app.php`, you can leave those entries in place — duplicate
registration is a no-op.

### Configuration

Add to `.env`:

```dotenv
SAMPATH_SERVICE_ENDPOINT=https://sampath.paycorp.com.au/rest/service/proxy
SAMPATH_AUTHTOKEN=
SAMPATH_HMAC=
SAMPATH_CURRENCY=LKR
SAMPATH_TOKENIZE_CLIENT_ID=
SAMPATH_PURCHASE_CLIENT_ID=
SAMPATH_RETURN_URL=https://your-app.test/payments/return
```

To change timeouts, pin a timezone, or supply a CA bundle, publish the config file:

```bash
php artisan vendor:publish --tag=paycorp-sampath-vault-config
```

Every setting and what it does is documented inline in
[`config/paycorp-sampath-vault.php`](config/paycorp-sampath-vault.php).

> `env()` is called **only** inside that config file. Do not read `SAMPATH_*` with
> `env()` from your own application code: under `php artisan config:cache` it
> returns `null`. That was a real bug in 1.x and it only showed up in production.

---

## Usage

### Hosted payment page

Open a session, redirect the cardholder, then settle when they return.

```php
use createch\PaycorpSampathVault\PaycorpSampathVault;

$gateway = app(PaycorpSampathVault::class);

$result = $gateway->initRequest([
    'clientRef'          => (string) $order->id,
    'comment'            => 'Order ' . $order->id,
    'total_amount'       => 1010,   // cents
    'service_fee_amount' => 0,
    'payment_amount'     => 1010,
]);

if (! $result['status']) {
    report(new RuntimeException($result['msg']));
    return back()->withErrors('Payment could not be started.');
}

session(['paycorp_reqid' => $result['reqid']]);

return redirect()->away($result['payment_page_url']);
```

Paycorp redirects back to `SAMPATH_RETURN_URL`. Settle the session there:

```php
$result = $gateway->completeRequest(['reqid' => $request->query('reqid')]);

if ($result['status'] && $result['ResponseCode'] === '00') {
    // Store $result['Token'] to charge this card again later without the PAN.
    $order->markPaid($result['TxnReference'], $result['Token']);
}
```

### Real-time payment against a stored token

The `Token` from `completeRequest()` lets you charge the same card again without
ever handling the card number — this is the point of the vault.

```php
$result = $gateway->realTimePayment([
    'clientRef' => (string) $order->id,
    'comment'   => 'Renewal ' . $order->id,
    'token'     => $customer->paycorp_token,
    'expire_at' => $customer->paycorp_expiry,  // e.g. 1228
    'amount'    => 1010,                       // cents
]);
```

### Handling the three possible outcomes

This is the most important change in 2.x. A payment call has **three** results,
not two:

```php
$result = $gateway->realTimePayment($data);

if ($result['status']) {
    // The gateway answered. Inspect ResponseCode: '00' is approved,
    // anything else is a decline with ResponseText explaining why.
    return $result['ResponseCode'] === '00' ? 'approved' : 'declined';
}

if ($result['outcome'] === 'unknown') {
    // The request may or may not have been processed: a dropped connection,
    // a timeout, an unreadable response. DO NOT retry blindly and DO NOT
    // tell the customer it failed. Queue it for reconciliation against the
    // Paycorp settlement report.
    ReconcilePaycorpTransaction::dispatch($order, $data['clientRef']);
    return 'pending';
}

// outcome === 'failed': the gateway definitively rejected it, no money moved.
return 'failed';
```

In 1.x a dropped connection returned `'status' => true` with blank fields, so a
network outage was indistinguishable from a completed payment.

### Exceptions instead of status arrays

For new code, typed exceptions are easier to get right than checking array keys:

```dotenv
SAMPATH_THROW_ON_ERROR=true
```

```php
use createch\PaycorpSampathVault\Exceptions\ConfigurationException;
use createch\PaycorpSampathVault\Exceptions\GatewayErrorException;
use createch\PaycorpSampathVault\Exceptions\MalformedResponseException;
use createch\PaycorpSampathVault\Exceptions\PaycorpException;
use createch\PaycorpSampathVault\Exceptions\TransportException;

try {
    $result = $gateway->realTimePayment($data);
} catch (TransportException | MalformedResponseException $e) {
    // Outcome UNKNOWN. Reconcile.
} catch (GatewayErrorException $e) {
    // The gateway rejected the request: bad HMAC, unknown clientId.
    Log::warning('Paycorp rejected the request', ['code' => $e->getErrorCode()]);
} catch (ConfigurationException $e) {
    // Credentials missing, or a non-HTTPS endpoint.
} catch (PaycorpException $e) {
    // Anything else from this package.
}
```

Exception messages are passed through the redactor, so they are safe to log.

### Raw card details

`PaycorpSampathRealTimePayment` accepts a PAN and CVV directly. **This brings your
whole application into PCI DSS scope.** Prefer the tokenised flow above.

```php
use createch\PaycorpSampathVault\PaycorpSampathRealTimePayment;

$result = app(PaycorpSampathRealTimePayment::class)->realTimePayment([
    'card_type'        => 'VISA',
    'card_holder_name' => 'A N OTHER',
    'card_number'      => $request->input('card_number'),
    'secure_id'        => $request->input('cvv'),
    'expire_at'        => '1228',
    'amount'           => 1010,
    'clientRef'        => (string) $order->id,
]);
```

Never log, cache, or persist these values anywhere.

### Vault operations

`storeCard`, `retrieveCard`, `updateCard`, `verifyToken` and `deleteToken` are on
the low-level client:

```php
use createch\PaycorpSampathVault\Paycorplib\GatewayClient\GatewayClient;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientVault\DeleteTokenRequest;

$request = new DeleteTokenRequest();
$request->setClientId(config('paycorp-sampath-vault.tokenize_client_id'));
$request->setToken($customer->paycorp_token);

$response = app(GatewayClient::class)->getVault()->deleteToken($request);
```

`deleteToken`, `updateCard` and `verifyToken` were **fatal on PHP 8** in 1.x.

---

## Testing your own integration

The package ships a fake transport, so you can test your payment flows without
touching the live gateway:

```php
use createch\PaycorpSampathVault\Contracts\HttpTransportInterface;
use createch\PaycorpSampathVault\Testing\FakeHttpTransport;

public function test_a_successful_payment_marks_the_order_paid(): void
{
    $transport = (new FakeHttpTransport())->willRespondWithJson([
        'responseData' => [
            'txnReference' => 'TXN-1',
            'responseCode' => '00',
            'responseText' => 'APPROVED',
        ],
    ]);

    $this->app->instance(HttpTransportInterface::class, $transport);

    $this->post('/checkout', [...])->assertOk();

    $this->assertTrue($this->order->fresh()->isPaid());

    // Assert on what actually went to the gateway.
    $this->assertSame(1010, $transport->lastRequestPayload()['requestData']['transactionAmount']['paymentAmount']);
}
```

`willFail()` simulates the network failure that used to look like a success:

```php
$transport = (new FakeHttpTransport())->willFail('connection reset');
// ... assert your code reconciles rather than marking the order paid or failed.
```

---

## Architecture

Every collaborator is an interface bound in the container, so you can replace any
one of them without forking the package:

| Contract | Default | Purpose |
|---|---|---|
| `HttpTransportInterface` | `CurlTransport` | The only class that touches the network |
| `SignerInterface` | `Sha256HmacSigner` | Request HMAC |
| `EncoderInterface` | `Latin1Encoder` | Bit-exact `utf8_decode()` replacement |
| `ClockInterface` | `SystemClock` | `requestDate` stamping |
| `MessageIdGeneratorInterface` | `RandomMessageIdGenerator` | CSPRNG `msgId` |
| `RedactorInterface` | `SensitiveDataRedactor` | Strips PANs, CVVs, tokens, secrets |

```php
$this->app->bind(HttpTransportInterface::class, MyInstrumentedTransport::class);
```

### Why `Latin1Encoder` exists

Every HMAC this package has ever sent was computed over `utf8_decode($payload)`,
not over the UTF-8 bytes. `utf8_decode()` was deprecated in PHP 8.2 and **removed
in PHP 9**. Signing the UTF-8 bytes instead would change the digest for every
request containing a non-ASCII comment or cardholder name, and the gateway would
reject them.

`Latin1Encoder` reimplements PHP's `php_next_utf8_char()` state machine, including
the non-obvious part: how many bytes a decode failure consumes depends on whether
the following bytes look like continuation bytes or a fresh character. It is
verified byte-identical to `utf8_decode()` across 163,488 well-formed and
malformed inputs, and 90 frozen HMAC golden vectors — captured from the real
`utf8_decode()` — gate every change to it.

`mb_convert_encoding()` is **not** a drop-in substitute: it agrees on well-formed
input but diverges on malformed input, and it depends on the global
`mbstring.substitute_character` ini setting, which an application can change
underneath you and so alter every signature.

---

## Development

```bash
composer install
vendor/bin/phpunit                      # all suites
vendor/bin/phpunit --testsuite=Unit     # Unit | Contract | Integration
```

Static analysis runs at two levels: the whole package at PHPStan level 5, and
everything written for 2.x at level 9 (maximum).

```bash
phpstan analyse -c phpstan.neon.dist
phpstan analyse -c phpstan-strict.neon.dist
```

No test contacts the live gateway. Response fixtures are recorded once and
scrubbed; the suite is driven entirely through `FakeHttpTransport`.

---

## Note

Read the Paycorp technical specification and understand the workflow before using
this package. It handles the transport and the signing; the payment logic and
reconciliation are yours.

The `PAYMENT_BATCH` operation is deliberately not implemented. It never worked in
1.x and now throws `UnsupportedOperationException` rather than sending a guessed
wire format for a batch of real debits. Open an issue with the Paycorp batch
specification if you need it.

## License

MIT
