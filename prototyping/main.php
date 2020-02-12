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
    $doc = (\securepay\xml_message($credentials));

    // \securepay\utils\strip_xml($doc);
    print_r($doc->saveXML());
?>