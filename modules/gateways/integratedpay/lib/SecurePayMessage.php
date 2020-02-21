<?php namespace WHMCS\Module\Gateway\IntegratedPay;

use Exception;

class SecurePayMessage
{
    // Describes the structure of the message
    private static $MessageInfo = [
        "messageID"         => "",
        "messageTimestamp"  => "",
        "timeoutValue"      => "60",
        "apiVersion"        => "spxml-3.0",
    ];
    private static $MerchantInfo = [
        "merchantID"        => "",
        "password"          => "",
    ];
    private static $RequestType = "Periodic|addToken|lookupToken|Echo";
    private static $Periodic = [
        "PeriodicList"      => [
            "PeriodicItem"      => [
                "actionType"        => "add|delete|trigger",
                "perioidicType"     => "1|2|3|4",
                "clientID"          => "",
            ]
        ]
    ];
    private static $CreditCardInfo = [
        "cardNumber"        => "",
        "cvv"               => "",
        "expiryDate"        => "",
        "recurringFlag"     => "yes|no"
    ];
    private static $DirectEntryInfo = [
        "bsbNumber"         => "",
        "accountNumber"     => "",
        "accountName"       => "",
        "creditFlag"        => "yes|no"
    ];


    // Member variables, set properties on them to change how the message behaves
    public $messageInfo     = [];
    public $merchantInfo    = [];
    public $requestType     = "";
    public $item            = [];


    function __construct(string $requestType, string $merchantID, string $password)
    {
        // Checking request type is valid
        if(!(in_array($requestType, explode("|", SecurePayMessage::$RequestType)))) {
            throw new \Exception("Error, invalid request type '$requestType'.");
        }

        // Constructing messageinfo
        $messageInfo = SecurePayMessage::$MessageInfo;
        $messageInfo["messageID"]           = $this->get_uuid();
        $messageInfo["messageTimestamp"]    = $this->get_timestamp();
        $this->messageInfo                  = $messageInfo;

        // Constructing merchantinfo    
        $merchantInfo = SecurePayMessage::$MerchantInfo;
        $merchantInfo["merchantID"]         = $merchantID;
        $merchantInfo["password"]           = $password;
        $this->merchantinfo                 = $merchantInfo;

        // Setting request type
        $this->requestType = $requestType;

        // Creating list based on request type
        if($requestType === "Periodic") {
            $this->periodic = SecurePayMessage::$Periodic;
        }
    }

    
    public function to_xml()
    {
        // Creating DOM object and setting it's properties for human readability
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8>');

        // Creating XML elements
        $root = $doc->createElement("SecurePayMessage");
        $messageinfo = $doc->createElement("MessageInfo");
        $merchantinfo = $doc->createElement("MerchantInfo");
        $requesttype = $doc->createElement("RequestType", $this->requestType);

        SecurePayMessage::list_to_xml($doc, $messageinfo, $this->messageInfo);
        $root->appendChild($messageinfo);
        $doc->appendChild($root);
        file_put_contents("LOG.txt", $doc->saveXML());
    }

    

    private static function list_to_xml(\DOMDocument $doc, \DOMElement $parent, array $list)
    {
        foreach($list as $key => $value) {      // For each entry in list
            $item = $doc->createElement($key);  // Creating element

            if(gettype($value) === "array") {   // If the item has children
                SecurePayMessage::list_to_xml($doc, $item, $value);
            }
            else {                              // If it doesn't
                $item->nodeValue = $value;
            }

            $parent->appendChild($item);        // Append it to the parent
        }
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