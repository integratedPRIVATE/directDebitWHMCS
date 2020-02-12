<?php
    include "securepay.php";    
    include "http.php";
    
    // Declaring Securepay variables
    $url_payment        = "https://test.api.securepay.com.au/xmlapi/periodic";
    $url_storage        = "https://test.api.securepay.com.au/xmlapi/token";
    $credentials = [
        "merchantID"    => "ABC0001",
        "password"      => "abc123"
    ];


    // Getting the xml base document
    $message = (\securepay\xml_message($credentials, "addToken"));

    // Adding a token item to the message
    \securepay\message\add_tokenitem($message, [
        "cardNumber"  => 4444333322221111,
        "expiryDate" => "11/25",
        "tokenType" => 1,
        "amount" => 1100,
        "transactionReference" => "MyCustomer"
    ]);

    // Dispatch the message
    $response = \http\xml\post($url_storage, [
        "headers" => [
            "host" => "test.securepay.com.au"
        ],

        "body"  => $message->saveXML()
    ]);


    // Print to screen for debugging
    print_r($response->saveXML());

?>