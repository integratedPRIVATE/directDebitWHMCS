<?php namespace securepay;

    include "lib/helper.php";


    /**
     * Takes in credentials and the request type and generates the base xml document for a securepay request
     */
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

        
        // Adding a Token, Periodic or Echo element based on request type
        $message = $xpath->query("/SecurePayMessage")[0];
        if($requestType == "Periodic") {
            // Creating elements
            $periodic       = $doc->createElement("Periodic");
            $periodicList   = $doc->createElement("PeriodicList");

            // Setting up tree
            $periodic->appendChild($periodicList);
            $message->appendChild($periodic);
        }
        elseif($requestType == "addToken" || $requestType == "lookupToken") {
            // Creating elements
            $token          = $doc->createElement("Token");
            $tokenList      = $doc->createElement("TokenList");

            // Setting up tree
            $token->appendChild($tokenList);
            $message->appendChild($token);
        }

        // Returning the document 
        return $doc;
    }

?>



<?php namespace securepay\message;

    function add_tokenitem(\DOMDocument $doc, array $token_struct)
    {
        /*
        $valid_struct = ["cardNumber", "expiryDate", "tokenType", "amount", "transactionReference"];

        // Validating input
        \helper\validate_keys($valid_struct, $token_struct, "Invalid token struct");
        */

        // Getting the token list element
        $xpath = new \DOMXPath($doc);                   // Getting xpath for finding nodes
        $tokenlist = $xpath->query("/SecurePayMessage/Token/TokenList")[0];   

        // Getting token list properties
        $count = count($xpath->query("TokenItem", $tokenlist));

        // Creating the token XML element and adding it to the list
        $token = new \DOMElement("TokenItem");
        $tokenlist->appendChild($token);

        // Adding children elements based off struct
        foreach($token_struct as $key => $value) {
            $token->appendChild($doc->createElement($key, $value));
        }

        // Setting the list and token properties
        $tokenlist->setAttribute("count", $count+1);
        $token->setAttribute("ID", $count+1);
    }


    function add_item(\DOMDocument $doc, array $item_struct, string $item_type)
    {
        // Declaring item and list names
        $itemname = $item_type . "Item";
        $listname = $item_type . "List";

        // Getting the list element
        $xpath = new \DOMXPath($doc);
        $list = $xpath->query(implode("/", ["/SecurePayMessage", $item_type, $listname]))[0];

        // Getting item count
        $count = count($xpath->query($itemname, $list));

        // Creating the XML item and adding it to the list
        $item = new \DOMElement($itemname);
        $list->appendChild($item);

        // Populating item children breadth first to allow multi dimensional array data
        $stack = [["parent" => $item, "children" => $item_struct]];
        while(true) {
            $node = array_shift($stack);            // Getting the first item in stack
            $parent = $node["parent"];              // Getting the parent
            $children = $node["children"];          // Getting the list of children

            // Looping through each child
            foreach($children as $key => $value) {
                $child = $doc->createElement($key); // Creating the element
                $parent->appendChild($child);       // Adding to the parent
                
                // If the item is an array then we add it to the stack and continue
                if(gettype($value) === "array") {
                    array_push($stack, [
                        "parent" => $child,
                        "children" => $value
                    ]);
                    continue;
                }

                // If not we set it's value
                $child->nodeValue = $value;
            }
        
            if(count($stack) < 1){break;}   // Exiting loop if the stack is empty
        }


        // Setting the list and item properties
        $list->setAttribute("count", $count+1);
        $item->setAttribute("ID", $count+1);
    }


    /**
     * Generates and returns a universal unique identifier (UUID) (version 1) for use in "messageID"  
     *   
     * *Complies to the RFC 4122 standard*
     */
    function get_id()
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