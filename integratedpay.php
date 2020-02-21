<?php
// Checking if this is being loaded through WHMCS
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

include "integratedpay/debug.php";              // Debugging helper functions

// Using library classes
use WHMCS\Module\Gateway\IntegratedPay\SecurePay;



////////////////////////////////////////////////////////////////////////////////////////////////////
/*                               WHMCS FUNCTIONALITY DECLARATIONS                                 */

function integratedpay_MetaData()               // Module settings
{ return IntegratedPay::$metadata; }

function integratedpay_config()                 // Module configurable admin options
{ return IntegratedPay::$config; }

function integratedpay_capture($params)         // Tells WHMCS we want to capture payment
{ return IntegratedPay::capture($params); }

function integratedpay_localbankdetails(){}     // Adds a "Add Bank Account" option to pay methods
function integratedpay_nolocalcc(){}            // Tells WH not to use a local credit card
function integratedpay_no_cc(){}                // Tells WH not to use a remote credit card

/**
 * @NOTE: MATTHEW ALEXANDER 20/02/21-09:30
 * Down the track I want to support credit card payments too, i'm hoping that WHMCS will support 
 *  this, if not I will probably have to create a module called, 'integratedcard,' and 'integrated-
 *  bank,' or something similar.
 * For now however this will do. 
 */



////////////////////////////////////////////////////////////////////////////////////////////////////
/*                                       WHMCS MODULE CODE                                        */

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
        "FriendlyName"  => [                    // The name, needed for backwards compatibility
            "Type"          => "System",
            "Value"         => "Integrated Pay"
        ],

        "merchantID"    => [                    // The ID of the merchant securepay will use
            "FriendlyName"  => "Merchant ID",
            "Type"          => "text",
            "Size"          => "7",
            "Default"       => ""
        ],

        "password"      => [                    // The password used for securepay authentication
            "FriendlyName"  => "Transaction Password",
            "Type"          => "text",
            "size"          => "35",
            "Default"       => ""
        ],

        "testMode"      => [                    // Wether we're running in test or production mode
            "FriendlyName"  => "Test Mode",
            "Type"          => "yesno",
            "Description"   => "Tick to enable test mode",
            "Default"       => "true"
        ]
    ];


    /**
     * Attempts to process payment
     */
    public static function capture(array $params): array
    {
        // We start by getting the bank information
        $bankname       = $params["bankname"];      // The name if the bank,    i.e 'ANZ'
        $banktype       = $params["banktype"];      // The type of bank,        i.e 'Savings'
        $bankcode       = $params["bankcode"];      // The routing code or BSB, i.e '012321'
        $bankacct       = $params["bankacct"];      // The account number,      i.e '123478965'

        // Now we need to get the properties from our config for securepay authentication
        $merchantid     = $params["merchantID"];    // The user set merchant ID for securepay
        $password       = $params["password"];      // The user set transaction password
        $testmode       = $params["testMode"];      // Whether we're using the test url

        $securepay = new SecurePay($merchantid, $password);
        $securepay->store_directdebit("WillBoop", $bankcode, $bankacct, $bankname, "no");

        

        return [
            "status"    => "pending",
            "rawdata"   => "DATA",
            "transid"   => "0",
        ];
    }

}

?>