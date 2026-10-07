<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils;

use createch\PaycorpSampathVault\Configuration\TransportOptions;
use createch\PaycorpSampathVault\Http\CurlTransport;
use createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig\ClientConfig;

/**
 * Backwards-compatible shim over CurlTransport.
 *
 * The signature is unchanged for integrations that call it directly, but the
 * behaviour is now safe:
 *
 *   - TLS certificates are verified. The original hard-coded
 *     CURLOPT_SSL_VERIFYPEER to false, exposing every PAN and CVV it sent to
 *     an active man-in-the-middle.
 *   - A failed exchange throws TransportException rather than returning false.
 *     Returning false was the root of the silent-success bug: the false flowed
 *     into json_decode(), became null, and every response parser read it as a
 *     complete set of empty fields.
 *   - There is a total timeout as well as a connect timeout.
 *
 * @deprecated Inject an HttpTransportInterface instead; a static method cannot
 *             be substituted in a test.
 */
class RestClient {

    private function __construct() {
    }

    /**
     * @param  ClientConfig       $config
     * @param  string             $jsonRequest
     * @param  array<int,string>  $headers
     * @return string             The raw response body.
     *
     * @throws \createch\PaycorpSampathVault\Exceptions\TransportException
     */
    public static function sendRequest($config, $jsonRequest, $headers) {
        $options = ($config instanceof ClientConfig)
            ? $config->getTransportOptions()
            : new TransportOptions();

        $transport = new CurlTransport();

        $response = $transport->post(
            $config->getServiceEndpoint(),
            $jsonRequest,
            $headers,
            $options
        );

        return $response->body();
    }

}
