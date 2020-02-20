<?php

include "integratedpay/securepay.php";          // Import for securepay access

// Securepay Credentials
$merchantID     = "ABC0001";
$password       = "abc123";

// Creating a request object
$request = new \securepay\Request($merchantID, $password);

/*
$request->add_payor("card", "boopity123", [
    "cardNumber"    => 4444333322221111,
    "cvv"           => 123,
    "expiryDate"    => "08/23"
]);
*/

$request->add_payor("bank", "boopity1234", [
    "bsbNumber"     => "123456",
    "accountNumber" => "123456789",
    "accountName"   => "John Smith",
]);


print_r($request->message->saveXML() . "\n");
print_r($request->response->saveXML() . "\n");

/*

SecurePay interactions that we need

store a payor (card or bank)
store a token (card)
lookup token

delete a payor or token

trigger a payment

schedule a payment (unsure if priority)

*/