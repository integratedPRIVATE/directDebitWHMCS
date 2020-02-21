<?php namespace WHMCS\Module\Gateway\IntegratedPay;

use Exception;

class SecurePayMessage
{
    // Describes the structure of the message
    public $messageInfo = [
        "messageID"         => "",
        "messageTimestamp"  => "",
        "timeoutValue"      => "60",
        "apiVersion"        => "spxml-3.0",
    ];
    public $merchantInfo = [
        "merchantID"        => "",
        "password"          => "",
    ];
    public $requestType = "Periodic|addToken|lookupToken|Echo";
    public $payload = [];


    function __construct(string $requestType, string $merchantID, string $password)
    {
        // Checking request type is valid
        if(!(in_array($requestType, explode("|", $this->requestType)))) {
            throw new \Exception("Error, invalid request type '$requestType'.");
        }

        // Setting message properties
        $this->messageInfo["messageID"]         = $this->get_uuid();
        $this->messageInfo["messageTimestamp"]  = $this->get_timestamp();
        $this->merchantInfo["merchantID"]       = $merchantID;
        $this->merchantInfo["password"]         = $password;
        $this->requestType = $requestType;
    }


    public function toXML()
    {
        $list = [
            "MessageInfo"   => $this->messageInfo,
            "MerchantInfo"  => $this->merchantInfo,
            "RequestType"   => $this->requestType
        ];
        if($this->requestType === "Periodic") {
            $list["Periodic"] = [
                "PeriodicList"  => [
                    "@count"        => "1",
                    "PeriodicItem"  => array_merge($this->payload, ["@ID"=>"1"])
                ]
            ];
        }

        // Creating DOM object and setting it's properties for human readability
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8" ?><SecurePayMessage></SecurePayMessage>');

        $stack = [[                             // Declaring stack for breadth first recursion
            "parent"    => $doc->appendChild($doc->firstChild),
            "children"  => $list
        ]];

        while(true) {
            $item = array_shift($stack);
            $pare = $item["parent"];
            $chil = $item["children"];

            // Looping through each child
            foreach($chil as $key => $value) {
                if(substr($key, 0, 1) === "@") {
                    $pare->setAttribute(substr($key, 1), $value);
                    continue;
                }

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

        return $doc->saveXML();
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