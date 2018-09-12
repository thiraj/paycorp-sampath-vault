<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientConfig;

class ClientConfig {

    private $serviceEndpoint;
    private $proxyHost;
    private $proxyPort;
    private $authToken;
    private $hmacSecret;
    private $validateOnly;
    
    public function __construct() {
        $this->validateOnly = FALSE;
    }

    public function getServiceEndpoint() {
        return $this->serviceEndpoint;
    }

    public function setServiceEndpoint($serviceEndpoint) {
        $this->serviceEndpoint = $serviceEndpoint;
    }

    public function getProxyHost() {
        return $this->proxyHost;
    }

    public function setProxyHost($proxyHost) {
        $this->proxyHost = $proxyHost;
    }

    public function getProxyPort() {
        return $this->proxyPort;
    }

    public function setProxyPort($proxyPort) {
        $this->proxyPort = $proxyPort;
    }

    public function getAuthToken() {
        return $this->authToken;
    }

    public function setAuthToken($authToken) {
        $this->authToken = $authToken;
    }

    public function getHmacSecret() {
        return $this->hmacSecret;
    }

    public function setHmacSecret($hmacSecret) {
        $this->hmacSecret = $hmacSecret;
    }

    public function isValidateOnly() {
        return $this->validateOnly;
    }

    public function setValidateOnly($validateOnly) {
        $this->validateOnly = $validateOnly;
    }

}
