<?php

namespace createch\PaycorpSampathVault\Support;

use createch\PaycorpSampathVault\Contracts\ClockInterface;
use createch\PaycorpSampathVault\Contracts\HttpTransportInterface;
use createch\PaycorpSampathVault\Contracts\MessageIdGeneratorInterface;
use createch\PaycorpSampathVault\Contracts\RedactorInterface;
use createch\PaycorpSampathVault\Contracts\SignerInterface;
use createch\PaycorpSampathVault\Http\CurlTransport;
use createch\PaycorpSampathVault\Security\SensitiveDataRedactor;
use createch\PaycorpSampathVault\Security\Sha256HmacSigner;

/**
 * The collaborators a gateway facade needs, bundled as one immutable
 * parameter object.
 *
 * A typed struct, not a service locator: every member is a declared
 * interface, resolved once at construction. It exists so that injecting the
 * transport for a test does not require threading five extra optional
 * arguments through GatewayClient, Payment and Vault.
 */
final class ClientRuntime
{
    /** @var HttpTransportInterface */
    private $transport;

    /** @var SignerInterface */
    private $signer;

    /** @var ClockInterface */
    private $clock;

    /** @var MessageIdGeneratorInterface */
    private $messageIds;

    /** @var RedactorInterface */
    private $redactor;

    public function __construct(
        HttpTransportInterface $transport,
        SignerInterface $signer,
        ClockInterface $clock,
        MessageIdGeneratorInterface $messageIds,
        RedactorInterface $redactor
    ) {
        $this->transport = $transport;
        $this->signer = $signer;
        $this->clock = $clock;
        $this->messageIds = $messageIds;
        $this->redactor = $redactor;
    }

    /**
     * Build the production default set for a given HMAC secret.
     *
     * @param  string                      $hmacSecret
     * @param  string|null                 $timezone
     * @param  HttpTransportInterface|null $transport
     * @return self
     */
    public static function forSecret($hmacSecret, $timezone = null, ?HttpTransportInterface $transport = null)
    {
        $redactor = new SensitiveDataRedactor();

        return new self(
            $transport ?: new CurlTransport($redactor),
            new Sha256HmacSigner($hmacSecret),
            new SystemClock($timezone),
            new RandomMessageIdGenerator(),
            $redactor
        );
    }

    /** @return HttpTransportInterface */
    public function transport()
    {
        return $this->transport;
    }

    /** @return SignerInterface */
    public function signer()
    {
        return $this->signer;
    }

    /** @return ClockInterface */
    public function clock()
    {
        return $this->clock;
    }

    /** @return MessageIdGeneratorInterface */
    public function messageIds()
    {
        return $this->messageIds;
    }

    /** @return RedactorInterface */
    public function redactor()
    {
        return $this->redactor;
    }

    /**
     * @param  HttpTransportInterface  $transport
     * @return self  A new instance; this one is unchanged.
     */
    public function withTransport(HttpTransportInterface $transport)
    {
        return new self($transport, $this->signer, $this->clock, $this->messageIds, $this->redactor);
    }

    /**
     * @param  SignerInterface  $signer
     * @return self
     */
    public function withSigner(SignerInterface $signer)
    {
        return new self($this->transport, $signer, $this->clock, $this->messageIds, $this->redactor);
    }
}
