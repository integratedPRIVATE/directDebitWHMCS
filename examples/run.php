<?php

include "../integratedpay/lib/HTTPRequest.php";
include "../integratedpay/lib/SecurePay.php";

use WHMCS\Module\Gateway\IntegratedPay\HTTPRequest;
use WHMCS\Module\Gateway\IntegratedPay\SecurePay;

$url                = "https://test.api.securepay.com.au/xmlapi/periodic";
$merchantID         = "ABC0001";
$password           = "abc123";
$clientID           = "BOOP0001";



$message =  <<<XML
<SecurePayMessage>
    <MessageInfo>
        <messageID>2aedc3d9-fdad-1204-a15f-168cea59808a</messageID>
        <messageTimestamp>20202102071806998000+600</messageTimestamp>
        <timeoutValue>60</timeoutValue>
        <apiVersion>spxml-3.0</apiVersion>
    </MessageInfo>
    <MerchantInfo>
        <merchantID>ABC0001</merchantID>
        <password>abc123</password>
    </MerchantInfo>
    <RequestType>Periodic</RequestType>
    <Periodic>
        <PeriodicList count="1">
            <PeriodicItem ID="1">
                <actionType>add</actionType>
                <clientID>$clientID</clientID>
                <DirectEntryInfo>
                    <bsbNumber>111222</bsbNumber>
                    <accountNumber>230345678</accountNumber>
                    <accountName>ANZ</accountName>
                    <creditFlag>no</creditFlag>
                </DirectEntryInfo>
                <amount>100</amount>
                <periodicType>4</periodicType>
            </PeriodicItem>
        </PeriodicList>
    </Periodic>
</SecurePayMessage>
XML;


/*
$message =  <<<XML
<SecurePayMessage>
    <MessageInfo>
        <messageID>2aedc3d9-fdad-1204-a15f-168cea59808a</messageID>
        <messageTimestamp>20202102071806998000+600</messageTimestamp>
        <timeoutValue>60</timeoutValue>
        <apiVersion>spxml-3.0</apiVersion>
    </MessageInfo>
    <MerchantInfo>
        <merchantID>ABC0001</merchantID>
        <password>abc123</password>
    </MerchantInfo>
    <RequestType>Periodic</RequestType>
    <Periodic>
        <PeriodicList count="1">
            <PeriodicItem ID="1">
                <actionType>trigger</actionType>
                <transactionReference>OOgityBoogity</transactionReference>
                <clientID>$clientID</clientID>
                <amount>1000</amount>
            </PeriodicItem>
        </PeriodicList>
    </Periodic>
</SecurePayMessage>
XML;
*/


$headers = [
    "host: test.securepay.com.au",
    "content-type: text/xml",
    "content-length: " . strlen($message)
];

$request = new HTTPRequest($url, $headers, $message);
$request->POST();
$response = $request->response_as_xml();

print_r($response->saveXML());
