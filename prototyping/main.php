<?php
    include "securepay.php";    
    
    // Declaring Securepay variables
    $url_payment        = "https://test.api.securepay.com.au/xmlapi/periodic";
    $url_storage        = "https://test.api.securepay.com.au/xmlapi/token";
    $credentials = [
        "merchantID"    => "ABC0001",
        "password"      => "abc123"
    ];


    // Getting the xml base document
    $message = (\securepay\xml_message($credentials, "addToken"));

    // Printing the doc to screen
    print_r($message->saveXML());
?>