<?php namespace securepay;

use DOMXPath;

function xml_message(array $credentials) : \DOMDocument
    {
        // Validating input
        utils\validate_keys(["merchantID", "password"], $credentials, "Invalid credentials.");

        // Getting generated values
        $messageID          = message\get_id();
        $messageTimestamp   = message\get_timestamp();
        $merchantID         = $credentials["merchantID"];
        $password           = $credentials["password"];

        // Creating DOM object for XML traversal
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;


        // Loading XML into DOM
        $doc->loadXML(  <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <SecurePayMessage>
        
            <!-- Identifies the message, mandatory -->
            <MessageInfo>
                <messageID>$messageID</messageID>
                <messageTimestamp>$messageTimestamp</messageTimestamp>
                <timeoutValue>60</timeoutValue>
                <apiVersion>spxml-3.0</apiVersion>
            </MessageInfo>
            
            <!-- The credentials for authentication, mandatory -->
            <MerchantInfo>
                <merchantID>$merchantID</merchantID>
                <password>$password</password>
            </MerchantInfo>
        
            <!-- The request type, must be one of ["Periodic", "addToken", "lookupToken", "Echo"] -->
            <RequestType></RequestType>
        
            <!-- Contains information about financial transactions to be processed -->
            <Periodic>
            </Periodic>
        
        </SecurePayMessage>
        XML);

        
        // Removing all comment nodes as they're there purely for developers
        $xpath = new DOMXPath($doc);                // Getting xpath for finding nodes
        $comments = $xpath->query("comment()");     // Querying for all comments

        foreach($comments as $node) {               // For each comment
            $node->parentNode->removeChild($node);  // Remove 
        }
        

        return $doc;
    }

?>



<?php namespace securepay\message;

    /**
     * 
     */
    function add_transaction()
    {

    }


    /**
     * Generates and returns a unique identifier for use in "messageID"
     */
    function get_id()
    {
        return 0;
    }


    /**
     * Generates and returns a timestamp string formatted to SecurePay guidelines
     */
    function get_timestamp()
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

?>


<?php namespace securepay\utils;

    /**
     * Dynamically checks if given object contains keys held in a given list of keys
     */
    function validate_keys(array $keys, array $obj, string $error) : bool
    {
        // Loop through each key in the list
        foreach($keys as $key) {
            if(!key_exists($key, $obj)) {   // Validate the key
                throw new \Exception("Error! "
                . $error . "\nArray must contain key, '" . strval($key) . "'.");
            }
        }

        // Returns true if everything passes
        return true;
    }

?>