<?php
    include "http.php";
    include "pprint.php";
    
    
    // Declaring Securepay variables
    $url_payment        = "https://test.api.securepay.com.au/xmlapi/periodic";
    $url_storage        = "https://test.api.securepay.com.au/xmlapi/token";
    $merchant_id        = "ABC0001";
    $transaction_pass   = "abc123";


    $response = \http\xml\post($url_payment, [
        "headers" => [
            "host" => "test.securepay.com.au"
        ],

        "body" => <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <SecurePayMessage>
            <MessageInfo>
                <messageID>8af793f9af34bea0ecd7eff71c94d6</messageID>
                <messageTimestamp>20040710050758444000+600</messageTimestamp>
                <timeoutValue>60</timeoutValue>
                <apiVersion>spxml-3.0</apiVersion>
            </MessageInfo>
            <MerchantInfo>
                <merchantID>$merchant_id</merchantID>
                <password>$transaction_pass</password>
            </MerchantInfo>
            <RequestType>Periodic</RequestType>
            <Periodic>
                <PeriodicList count="1">
                    <PeriodicItem ID="1">
                        <actionType>trigger</actionType>
                        <transactionReference>Boopity Boop</transactionReference>
                        <clientID>test3</clientID>
                        <amount>1400</amount>
                    </PeriodicItem>
                </PeriodicList>
            </Periodic>
        </SecurePayMessage>
        XML
    ]);

    \pprint\xml($response);    
?>