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
    $message = (\securepay\xml_message($credentials, "Periodic"));


    // Add periodic item
    \securepay\message\add_item($message, [
        "actionType"        => "add",                   // We're adding a payor
        "clientID"          => "RichardIntegrated1",    // Client reference, no spaces
        "DirectEntryInfo"   => [                        // Bank account details
            "bsbNumber"     => "123456",
            "accountNumber" => "123456789",
            "accountName"   => "John Smith"
        ],
        "amount"            => 1000,                    // 
        "periodicType"      => 4

    ], "Periodic");


    // Dispatch the message
    $response = \http\xml\post($url_payment, [
        "headers" => [
            "host" => "test.securepay.com.au"
        ],

        "body"  => $message->saveXML()
    ]);

    

    print_r($message->saveXML());
    print_r("\n\n\n");
    print_r($response->saveXML());

?>