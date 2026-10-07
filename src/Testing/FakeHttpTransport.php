<?php

namespace createch\PaycorpSampathVault\Testing;

use createch\PaycorpSampathVault\Configuration\TransportOptions;
use createch\PaycorpSampathVault\Contracts\HttpTransportInterface;
use createch\PaycorpSampathVault\Exceptions\TransportException;
use createch\PaycorpSampathVault\Http\HttpResponse;
use LogicException;

/**
 * An in-memory transport for tests. Shipped with the package, not only used
 * by it, so an application can assert against its own payment flows without
 * ever reaching the live gateway.
 *
 *     $transport = (new FakeHttpTransport())->willRespondWithJson(array(
 *         'responseData' => array('responseCode' => '00', 'txnReference' => 'T1'),
 *     ));
 *
 *     $gateway = new PaycorpSampathVault($config, null, $transport);
 *     $gateway->realTimePayment($data);
 *
 *     $transport->lastRequestPayload();  // decoded outbound JSON
 *     $transport->lastHeaders();         // including the computed HMAC
 */
final class FakeHttpTransport implements HttpTransportInterface
{
    /** @var array<int,HttpResponse|TransportException> */
    private $queue = array();

    /**
     * @var array<int,array{url:string,body:string,headers:array<int,string>,options:TransportOptions}>
     */
    private $requests = array();

    /**
     * @param  array<string,mixed>  $payload
     * @param  int                  $statusCode
     * @return $this
     */
    public function willRespondWithJson(array $payload, $statusCode = 200)
    {
        $body = json_encode($payload);

        if ($body === false) {
            throw new LogicException(
                'The queued response payload could not be encoded as JSON: ' . json_last_error_msg()
            );
        }

        return $this->willRespondWith($body, $statusCode);
    }

    /**
     * @param  string  $body
     * @param  int     $statusCode
     * @return $this
     */
    public function willRespondWith($body, $statusCode = 200)
    {
        $this->queue[] = new HttpResponse($statusCode, $body);

        return $this;
    }

    /**
     * Queue a transport-level failure, the condition that used to masquerade
     * as a successful empty response.
     *
     * @param  string  $message
     * @param  int     $curlErrorNumber
     * @return $this
     */
    public function willFail($message = 'simulated network failure', $curlErrorNumber = 7)
    {
        $this->queue[] = TransportException::connectionFailed($message, $curlErrorNumber);

        return $this;
    }

    /**
     * @param  string             $url
     * @param  string             $body
     * @param  array<int,string>  $headers
     * @param  TransportOptions   $options
     * @return HttpResponse
     */
    public function post($url, $body, array $headers, TransportOptions $options)
    {
        $this->requests[] = array(
            'url' => (string) $url,
            'body' => (string) $body,
            'headers' => array_values(array_map('strval', $headers)),
            'options' => $options,
        );

        if ($this->queue === array()) {
            throw new LogicException(
                'FakeHttpTransport received an unexpected request to ' . $url
                . '. Queue a response with willRespondWithJson() or willFail() first.'
            );
        }

        $next = array_shift($this->queue);

        if ($next instanceof TransportException) {
            throw $next;
        }

        return $next;
    }

    /** @return int */
    public function requestCount()
    {
        return count($this->requests);
    }

    /**
     * @param  int  $index
     * @return array{url:string,body:string,headers:array<int,string>,options:TransportOptions}
     */
    public function request($index = 0)
    {
        if (! isset($this->requests[$index])) {
            throw new LogicException('No request was recorded at index ' . $index . '.');
        }

        return $this->requests[$index];
    }

    /** @return string */
    public function lastRequestBody()
    {
        return $this->request($this->lastIndex())['body'];
    }

    /**
     * The outbound request body, decoded.
     *
     * @return array<string,mixed>
     */
    public function lastRequestPayload()
    {
        $decoded = json_decode($this->lastRequestBody(), true);

        return is_array($decoded) ? $decoded : array();
    }

    /** @return array<int,string> */
    public function lastHeaders()
    {
        return $this->request($this->lastIndex())['headers'];
    }

    /**
     * Read one outbound header by name, e.g. header('HMAC').
     *
     * @param  string  $name
     * @return string|null
     */
    public function header($name)
    {
        foreach ($this->lastHeaders() as $line) {
            $parts = explode(':', $line, 2);

            if (count($parts) === 2 && strcasecmp(trim($parts[0]), $name) === 0) {
                return trim($parts[1]);
            }
        }

        return null;
    }

    /** @return string */
    public function lastUrl()
    {
        return $this->request($this->lastIndex())['url'];
    }

    /** @return TransportOptions */
    public function lastOptions()
    {
        return $this->request($this->lastIndex())['options'];
    }

    /** @return int */
    private function lastIndex()
    {
        if ($this->requests === array()) {
            throw new LogicException('No request has been recorded yet.');
        }

        return count($this->requests) - 1;
    }
}
