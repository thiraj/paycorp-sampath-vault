# Paycorp-Sampath-Vault


Paycorp-Sampath-Vault is a php package for making payment using Sampath bank Payment Gateway. This uses the PHP library of Sampath bank. In this package you can make:

  - Redirect Page Payment
  - Realtime Payment
  - Tokanized Payments

# Features!

  - Using with composer
  - Easy intergration
  - Compatible with Laravel

# Requirements

> PHP >= 5.6
> OpenSSL >= 1.0.1
> CUrl >= 7.34
> Composer

# Usage

### Installation


```sh
$ composer require createch/paycorp-sampath-vault
```

### Configurations

#### Laravel

After install via composer add Config values to .env file as following:

```sh
SAMPATH_SERVICE_ENDPOINT=
SAMPATH_AUTHTOKEN=
SAMPATH_HMAC=
SAMPATH_CURRENCY=
SAMPATH_TOKENIZE_CLIENT_ID=
SAMPATH_PURCHASE_CLIENT_ID=
SAMPATH_RETURN_URL=
```

### Methods

##### PaymentInit

Import package class in you class header:

```sh
use createch\PaycorpSampathVault\PaycorpSampathVault;
```
Sample InitRequest

```sh
    $paymentInit = new PaycorpSampathVault();
    $data['clientRef'] = $request->user()->id;
    $data['comment'] = "Your comment";
    $data['total_amount'] = 1010;
    $data['service_fee_amount'] = 1010;
    $data['payment_amount'] = 1010;
    $res = $paymentInit->initRequest($data);
    
    return response()->json($res);
```

You will receive reqid, payment_page_url for the redirect. When you redirected to the "payment_page_url" user can enter the card details and pay. Then paycorp will return the response to "SAMPATH_RETURN_URL" you configured in .env file. When get the correct response, you need to call PaymentCompleteRequest.

#### completeRequest

```sh
    $data['reqid'] = $_GET['reqid'];
    $data['clientRef'] = $_GET['clientRef'];
    $paymentComplete = new PaycorpSampathVault();
    $response = $paymentComplete->completeRequest($data);
    
    return response()->json($res);
```

#### Make Real Time Payments using Token

In Payment complete response you will get the "Token" and neccessary data. Using "Token" you can make instant payments without entering card details or redirecting user everytime to payment page. This is the special feature of Vault in paycorp.

```sh
    $payment = new PaycorpSampathVault();

    $data = [];
    $data['clientRef'] = "Clent Ref";
    $data['token'] = "token";
    $data['comment'] = "Your Comment";
    $data['amount'] = 1010; // in cents
    $data['expire_at'] = "Expiry Date of Card"; //1223
    $data['payment_amount'] = 1010;
    $response = $payment->realTimePayment($data);
    
    return $response;
```

# NOTE:

> Please read Paycorp Technical document and understand the workflow well before use this package. This package only for developers to save their life.


License
----

### MIT

**Free Software, Hell Yeah!**

