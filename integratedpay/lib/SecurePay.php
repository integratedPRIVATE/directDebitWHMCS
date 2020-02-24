<?php namespace WHMCS\Module\Gateway\IntegratedPay;

// Using library classes

include "integratedpay/debug.php";              // Debugging helper functions

use Exception;
use WHMCS\Module\Gateway\IntegratedPay\HTTPRequest;
use WHMCS\Module\Gateway\IntegratedPay\SecurePayMessage;
use integratedpay\debug\Log;


class SecurePay
{
    // INSTANCE
    public $merchantID  = "";
    public $password    = "";
    public $testmode    = false;
    

    function __construct(string $merchantID, string $password, bool $testmode=false)
    {
        // Setting member variables
        $this->merchantID = $merchantID;    
        $this->password = $password;
        $this->testmode = $testmode;
    }

    
    /**
     * Stores a payor's bank details in Securepay
     * @param string $id The payor ID used for triggering future payments
     * @param string $bsb The Route or BSB number for the bank 
     * @param string $number The account number 
     * @param string $name The name of the bank
     * @param string $credit whether the bank is charging credit or not, type of [yes|no]
     * @param int $amount The amount that will be charged, doesn't appear to do anything
     */
    public function store_directdebit(
        string $id, string $bsb, string $number, string $name, string $credit, int $amount)
    {
        // Creating payload
        $payload = [
            "PeriodicList"  => [
                "@count"            => "1",
                "PeriodicItem"      => [
                    "@ID"           => "1",
                    "actionType"        => "add",
                    "clientID"          => $id,
                    "DirectEntryInfo"   => [
                        "bsbNumber"         => $bsb,
                        "accountNumber"     => $number,
                        "accountName"       => $name,
                        "creditFlag"        => $credit
                    ],
                    "amount"            => $amount,
                    "periodicType"      => "4"
                ]
            ]
        ];

        // Dispatching payload and returning result
        return $this->dispatch_payload($payload, "Periodic");
    }


    /**
     * Edits a payor's bank details in Securepay
     * @param string $id The payor ID used for triggering future payments
     * @param string $bsb The Route or BSB number for the bank 
     * @param string $number The account number 
     * @param string $name The name of the bank
     * @param string $credit whether the bank is charging credit or not, type of [yes|no]
     */
    public function edit_directdebit(
        string $id, string $bsb, string $number, string $name, string $credit)
    {
        // Creating payload
        $payload = [
            "PeriodicList"  => [
                "@count"            => "1",
                "PeriodicItem"      => [
                    "@ID"           => "1",
                    "actionType"        => "edit",
                    "clientID"          => $id,
                    "DirectEntryInfo"   => [
                        "bsbNumber"         => $bsb,
                        "accountNumber"     => $number,
                        "accountName"       => $name,
                        "creditFlag"        => $credit
                    ]
                ]
            ]
        ];

        // Dispatching payload and returning result
        return $this->dispatch_payload($payload, "Periodic");
    }


    /**
     * Triggers a payment for a given payor ID
     * @param string $id The payor ID that will be used to trigger a payment
     * @param string $reference The transaction reference, usually associated with the invoice
     * @param int $amount The amount of money to charge
     */
    public function trigger_payment(string $id, string $reference, int $amount)
    {
        // Creating payload
        $payload = [
            "PeriodicList"  => [
                "@count"    => "1",
                "PeriodicItem"  => [
                    "@ID"       => "1",
                    "actionType"    => "trigger",
                    "transactionReference" => $reference,
                    "clientID"      => $id,
                    "amount"        => $amount
                ]
            ]
        ];

        // Dispatching payload and returning result
        return $this->dispatch_payload($payload, "Periodic");
    }


    private function dispatch_payload(array $payload, string $name)
    {
        // Getting base messsage
        $message_arr = $this->fill_message();
        $message_arr["RequestType"] = "Periodic";

        // Filling in payload
        $message_arr[$name] = $payload;

        // Generating XML object and getting string
        $message_xml = SecurePay::list_to_xml($message_arr, "SecurePayMessage");
        $message = $message_xml->saveXML();

        // Creating headers
        $headers = [
            "host: test.securepay.com.au",
            "content-type: text/xml",
            "content-length: " . strlen($message)
        ];

        // Getting URL
        $url = $this->testmode === true ? SecurePay::$url_live : SecurePay::$url_test;

        // Creating request
        $request = new HTTPRequest($url, $headers, $message);
        $request->POST();
        $response = SecurePay::xml_to_list($request->response_as_xml());

        // Validating response
        $valid = SecurePay::validate_response($message_arr, $response);
        if($valid === false) {
            return false;
        }

        // @DEBUG
        Log::append($message, null, "MESSAGE SENT TO SECUREPAY");
        
        return $response;
    }


    private function fill_message(): array
    {
        $message = SecurePay::$message;
        $message["MessageInfo"]["messageID"] = SecurePay::get_uuid();
        $message["MessageInfo"]["messageTimestamp"] = SecurePay::get_timestamp();
        $message["MerchantInfo"]["merchantID"] = $this->merchantID;
        $message["MerchantInfo"]["password"] = $this->password;

        return $message;
    }



    // STATIC
    private static $url_test = "https://test.api.securepay.com.au/xmlapi/periodic";
    private static $url_live = "https://api.securepay.com.au/xmlapi/periodic";

    public static $message = [
        "MessageInfo"       => [
            "messageID"         => "",
            "messageTimestamp"  => "",
            "timeoutValue"      => "60",
            "apiVersion"        => "spxml-3.0"
        ],
        "MerchantInfo"      => [
            "merchantID"        => "",
            "password"          => ""
        ],
        "RequestType"       => ""
    ];


    private static function list_to_xml(array $list, string $rootname=null): \DOMDocument
    {
        // Creating DOM object and setting it's properties for human readability
        $doc = new \DOMDocument("1.0", "UTF-8");
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        // Declaring stack for breadth first recursion
        $stack = [ ["parent"    => $doc, "children"  => $list] ];

        // If a root name is given, we create a new element as use that as root
        if($rootname !== null) {
            $root = $doc->appendChild($doc->createElement($rootname));
            $stack[0]["parent"] = $root;
        }

        // Breadth first while loop
        while(true) {
            $item = array_shift($stack);        // Getting first item in stack
            $pare = $item["parent"];            // Getting the parent
            $chil = $item["children"];          // And getting the list of children

            // Looping through each child
            foreach($chil as $key => $value) {
                // Setting an attribute if the key starts with "@"
                if(substr($key, 0, 1) === "@") {
                    $pare->setAttribute(substr($key, 1), $value);
                    continue;
                }
                
                // Creating a node
                $node = $pare->appendChild($doc->createElement($key));

                // Setting node value if it doesn't have children
                if(gettype($value) !== "array") {
                    $node->nodeValue = $value;
                    continue;
                }

                // Adding to the stack if the node has children
                array_push($stack, [
                    "parent"    => $node,
                    "children"  => $value
                ]);
            }

            // Checking if we've reached the end of the stack
            if(count($stack) < 1) {break;}
        }

        return $doc;                            // Returning DOMDocument object
    }


    private static function xml_to_list(\DOMDocument $dom, bool $includeroot=false): array
    {
        // Creating root and recursively looping through the passed $dom
        $root = $includeroot === false ? $dom->firstChild : $dom;
        $array = SecurePay::xml_to_list_recursion($root);

        return $array;
    }


    private static function xml_to_list_recursion($node)
    {
        // Getting node propertis
        $sublist = $node->childNodes;
        $attlist = $node->attributes;

        // If the node has no childnodes
        if(count($sublist) === 0) {
            return null;
        }

        // If the node contains one subnode and it's text
        if(count($sublist) === 1 && $sublist[0]->nodeType === 3) {
            // If it has attributes
            if(count($attlist) > 0) {
                $value = [$sublist[0]->nodeValue];
                foreach($attlist as $att) {
                    $value["@$att->name"] = $att->value;
                }
                return $value;
            }

            // If not
            return $sublist[0]->nodeValue;
        }

        // If it contains children
        $list = [];

        foreach($attlist as $att) {         // Getting each attribite
            $list["@$att->name"] = $att->value;
        }

        foreach($sublist as $subnode) {     // Getting each child
            $key = $subnode->nodeName;
            $value = SecurePay::xml_to_list_recursion($subnode);

            $list[$key] = $value;
        }

        return $list;
    }


    private static function validate_response(array $request, array $response): bool
    {
        // The base error message to keep consistancy and allow shorthand
        $error_message = "Error. %s does not match.\nRequest   => %s\nResponse  => %s";

        // Creating list of properties to check
        $list = [
            "messageID"     => [
                $request["MessageInfo"]["messageID"],
                $response["MessageInfo"]["messageID"]
            ],
            "merchantID"    => [
                $request["MerchantInfo"]["merchantID"],
                $response["MerchantInfo"]["merchantID"]
            ]
        ];

        // Looping through each property in the list and erroring out if it doesn't match
        foreach($list as $key => $val) {
            if($val[0] !== $val[1]) {
                throw new Exception(sprintf($error_message, $key, $val[0], $val[1]));
                return false;
            }
        }

        return true;
    }


    /**
     * Generates and returns a universal unique identifier (UUID) (version 1) for use in "messageID"  
     * *Complies to the RFC 4122 standard*  
     */
    private static function get_uuid(): string
    {
        // Generating a unique seed based on time
        $seed = (double)microtime(true) * 1000;
        mt_srand($seed);                            // Applying seed to the rng

        /** UUID Format compliant to the RFC standard UUID version 1 (8-4-4-4-12)
         * '%' specifies an insertion point
         * '04' indicates this should be 4 digits long, padded or cut
         * 'x' tells sprintf to convert decimal to hex with lowercase letters
         */
        $format = "%04x%04x-%04x-%04x-%04x-%04x%04x%04x";

        // Generating the digits
        $digits = [
            mt_rand(0,      65535),         // #0000 - #ffff
            mt_rand(0,      65535),         // #0000 - #ffff
            mt_rand(0,      65535),         // #0000 - #ffff
            mt_rand(4096,   6553),          // #1000 - #1999, 3rd digit must start with a '1'
            mt_rand(40960,  43417),         // #a000 - #a999, 4th digit must start with a 'b'
            mt_rand(0,      65535),         // #0000 - #ffff
            mt_rand(0,      65535),         // #0000 - #ffff
            mt_rand(0,      65535),         // #0000 - #ffff
        ];

        // Compiling the UUID
        $uuid = vsprintf($format, $digits);

        // Returning the UUID (version 1) 
        return $uuid;
    }


    /**
     * Generates and returns a timestamp string formatted to SecurePay guidelines
     */
    private static function get_timestamp(): string
    {
        // Get the date information
        $time = new \DateTime("now");

        // Formatting in object for developer friendliness and readability
        $format = [
            "YYYY"  => $time->format("Y"),  // A 4-digit year
            "DD"    => $time->format("d"),  // A 2-digit zero-padded day of month
            "MM"    => $time->format("m"),  // A 2-digit zero-padded month of year
            "HH"    => $time->format("H"),  // A 2-digit zero-padded hour of day in 24 hour clock
            "NN"    => $time->format("i"),  // A 2-digit zero-padded minute of hour
            "SS"    => $time->format("s"),  // A 2-digit zero-padded second of minute
            "KKK"   => $time->format("v"),  // A 2-digit zero-padded millsecond of second
            "000"   => "000",               // Static, securepay doesn't use nanoseconds
            "s000"  => "+600"               // Time zone offset in minutes, static for now
        ];

        // Imploding array and returning timestamp string with correct formatting
        return implode($format);
    }

}

