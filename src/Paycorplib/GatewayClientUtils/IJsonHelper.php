<?php
namespace createch\PaycorpSampathVault\Paycorplib\GatewayClientUtils;

interface IJsonHelper {

    public function fromJson($json);

    public function toJson($instance);
    
}
