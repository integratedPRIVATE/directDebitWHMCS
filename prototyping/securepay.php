<?php namespace securepay;

    include "lib/helper.php";

    
    // Declaring static variables
    $XML_BASE = <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
    <SecurePayMessage>
    
        <!-- Identifies the message, mandatory -->
        <MessageInfo>
            <messageID></messageID>
            <messageTimestamp></messageTimestamp>
            <timeoutValue>60</timeoutValue>
            <apiVersion>spxml-3.0</apiVersion>
        </MessageInfo>
        
        <!-- The credentials for authentication, mandatory -->
        <MerchantInfo>
            <merchantID></merchantID>
            <password></password>
        </MerchantInfo>
    
        <!-- The request type, must be one of ["Periodic", "addToken", "lookupToken", "Echo"] -->
        <RequestType></RequestType>
    
    </SecurePayMessage>
    XML;



    function xml_message(array $credentials, string $requestType) : \DOMDocument
    {
        // Validating input
        \helper\validate_keys(["merchantID", "password"], $credentials, "Invalid credentials.");
        \helper\validate_string($requestType, ["Periodic", "addToken", "lookupToken", "Echo"], "Invalid request type");

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
            <RequestType>$requestType</RequestType>
        
        </SecurePayMessage>
        XML);

        
        // Removing all comment nodes as they're there purely for developers
        $xpath = new \DOMXPath($doc);               // Getting xpath for finding nodes
        $comments = $xpath->query("comment()");     // Querying for all comments

        foreach($comments as $node) {               // For each comment
            $node->parentNode->removeChild($node);  // Remove 
        }

        // Returning the document 
        return $doc;
    }

?>



<?php namespace securepay\message;


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