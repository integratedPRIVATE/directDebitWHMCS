<?php

include "integratedpay/securepay.php";          // Import for securepay access

// Securepay Credentials
$merchantID     = "ABC0001";
$password       = "abc123";

// Creating a request object
$request = new \securepay\Request($merchantID, $password);
print_r($request->message->saveXML());
print_r("\n");
print_r($request->response->saveXML());


/*

SecurePay interactions that we need

store a payor (card or bank)
store a token (card)
lookup token

delete a payor or token

trigger a payment

schedule a payment (unsure if priority)

*/