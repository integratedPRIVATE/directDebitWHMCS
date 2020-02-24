<?php
// Checking if this is being loaded through WHMCS
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


// Using library classes
use WHMCS\Module\Gateway\IntegratedPay\SecurePay;
use WHMCS\Module\Gateway\IntegratedPay\Log;



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
        Log::clear();

        // We start by getting the bank information
        $bankname       = $params["bankname"];      // The name if the bank,    i.e 'ANZ'
        $banktype       = $params["banktype"];      // The type of bank,        i.e 'Savings'
        $bankcode       = $params["bankcode"];      // The routing code or BSB, i.e '012321'
        $bankacct       = $params["bankacct"];      // The account number,      i.e '123478965'

        // Now we need to get the properties from our config for securepay authentication
        $merchantid     = $params["merchantID"];    // The user set merchant ID for securepay
        $password       = $params["password"];      // The user set transaction password
        $testmode       = $params["testMode"];      // Whether we're using the test url

        // Securepay information
        $credit         = $banktype === "Checking" ? "yes" : "no";
        $payorID        = $params["clientdetails"]["customfields1"];
        $amount         = (int)str_replace(".", "", $params["amount"]);
        $transref       = $params["invoiceid"];

        /**     @NOTE : MATTHEW ALEXANDER 20/02/24 02:45pm
         * $payorID is currently simply referencing the first custom field value in the passed 
         *  $params array, this should be replaced with a more robust checking system as right now
         *  if someone changes the order of the custom fields it could be... problematic.
         * An option is to try and use the Object model system */

        // Validating the payor ID
        if(preg_match('/^\w\w\w\w\d\d\d\d$/', $payorID) === false) {
            throw new Exception(
                "Invalid payor ID '$payorID', must start with 4 digits then 4 numbers." );
            return ["status" => "declined"];
        }
        

        // Creating SecurePay object and attempting to store bank
        $securepay = new SecurePay($merchantid, $password, $testmode === "on" ? false : true);
        $response = $securepay->store_directdebit(
            $payorID, $bankcode, $bankacct, $bankname, $credit, $amount );
        
        Log::append($response, Log::$JSON, "Storing Bank Response");
            
        // Getting the information from the response
        $periodicItem = $response["Periodic"]["PeriodicList"]["PeriodicItem"];
        $responsecode = $periodicItem["responseCode"];
        $responsetext = $periodicItem["responseText"];

        // If the code is not successful or duplicate client ID
        if($responsecode !== "00" && $responsecode !== "346") {
            throw new Exception("Payor not succesfully stored in SecurePay.
                responseCode: $responsecode
                responseText: $responsetext." 
            );
            return [
                "status"        => "declined",
                "declinereason" => $responsetext,
                "rawdata"       => $response
            ];
        }


        // Edit the bank details if the payor ID does not exist, just to be safe
        // Note that this is a workaround and while it should be fine, really isn't ideal
        if($responsecode === "346") {
            $response = $securepay->edit_directdebit(
                $payorID, $bankcode, $bankacct, $bankname, $credit
            );

            Log::append($response, Log::$JSON, "Edit Bank Response");

            // Getting information from the response
            $periodicItem = $response["Periodic"]["PeriodicList"]["PeriodicItem"];
            $responsecode = $periodicItem["responseCode"];
            $responsetext = $periodicItem["responseText"];

            if($responsecode !== "00") {
                throw new Exception("Failed to update bank details, response '$responsetext'.");
                return [
                    "status"        => "declined",
                    "declinereason" => $responsetext,
                    "rawdata"       => $response
                ];
            }
        }


        // Triggering the actual payment
        $response = $securepay->trigger_payment($payorID, $transref, $amount);

        Log::append($response, Log::$JSON, "Payment Response");
        
        // Getting information from response
        $periodicItem = $response["Periodic"]["PeriodicList"]["PeriodicItem"];
        $responsecode = $periodicItem["responseCode"];
        $responsetext = $periodicItem["responseText"];

        // If the payment did not go through, fail
        if($responsecode !== "00") {
            return [
                "status"            => "declined",
                "declinedreason"    => $responsetext,
                "rawdata"           => $response
            ];
        }

        // If the payment did go through, return success
        return [
            "status"    => "success",
            "transid"   => $periodicItem["txnID"],
            "rawdata"   => $response
        ];
    }

}

?>