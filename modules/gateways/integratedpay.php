<?php
// Checking if this is being loaded through WHMCS
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

include "integratedpay/debug.php";




use WHMCS\User\Client;
$client = Client::find(1);
$methods = $client->payMethods;

$funcs = get_class_methods($methods);
$funcs_match = [];
foreach($funcs as $func) {
    if(strpos(strtolower($func), "pay") !== false) {
        array_push($funcs_match, $func);
    }
}
// var_dump($funcs);
// var_dump(get_class_vars("WHMCS\Payment\PayMethod\Collection"));
// var_dump($methods);

// var_dump(\integratedpay\debug\find_functions("bank"));
// var_dump(ReflectionFunction::export("getpaymethodbankdetails"));

// var_dump(getpaymethodbankdetails($methods[0]));




///////////////////////////////////////////////////////////////////////////////////////////////////
/*                               WHMCS FUNCTIONALITY DECLARATIONS                                */

function integratedpay_MetaData()               // Module settings
{ return IntegratedPay::$metadata; }

function integratedpay_config()                 // Module configurable admin options
{ return IntegratedPay::$config; }

function integratedpay_capture($params)         // Tells WHMCS we want to capture payment
{ return IntegratedPay::capture($params); }

function integratedpay_localbankdetails(){}     // Adds a "Add Bank Account" option to pay methods
function integratedpay_nolocalcc(){}
function integratedpay_no_cc(){}



///////////////////////////////////////////////////////////////////////////////////////////////////
/*                                       WHMCS MODULE CODE                                       */

class IntegratedPay
{
    /**
     * Declares the modules related capabilities and settings.
     */
    public static $metadata    = [
        "DisplayName"   => "Integrated Pay",
        "APIVersion"    => "1.1",
        "gatewayType"   => "Bank"               // Must be "Bank", allows _capture to use bank
    ];

    /**
     * Gateway configuration options, presented to the administrator for configuring module
     */
    public static $config      = [
        "FriendlyName"  => [
            "Type"          => "System",
            "Value"         => "Integrated Pay"
        ],

        "merchantID"    => [
            "FriendlyName"  => "Merchant ID",
            "Type"          => "text",
            "Size"          => "7",
            "Default"       => ""
        ],

        "password"      => [
            "FriendlyName"  => "Transaction Password",
            "Type"          => "text",
            "size"          => "35",
            "Default"       => ""
        ]
    ];


    /**
     * Attempts to process payment
     */
    public static function capture(array $params): array
    {
        $test = "";

        return [
            "status"    => "pending",
            "rawdata"   => "DATA",
            "transid"   => "0",
        ];
    }

}

?>