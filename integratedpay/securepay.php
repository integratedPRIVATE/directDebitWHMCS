<?php namespace securepay;

include "http.php";
include "utils.php";

/**
 * The Request class is used to send and get requests to the SecurePay servers  
 * It handles the creation and management of the XML messages, and the POST and GET requests for you
 * making it as simple as possible to interact with the SecurePay API 
 */
class Request
{
    /** PRIVATE PROPERTIES */
    private $merchantID     = "";                   // The ID or login used to authenticate
    private $password       = "";                   // The password used to authenticate
    private $valid_data     = [                     // Describes a valid xml request, checked against
        "requestType"       => ["Periodic", "addToken", "lookupToken", "Echo"],
        "bank"              => ["bsbNumber", "accountNumber", "accountName"],
        "card"              => ["cardNumber", "cvv", "expiryDate"],
    ];
    private $map            = [                     // A map of element locations
        "messageID"         => "/SecurePayMessage/MessageInfo/messageID",
        "messageTimestamp"  => "/SecurePayMessage/MessageInfo/messageTimestamp",
        "requestType"       => "/SecurePayMessage/RequestType"
    ];


    /** PUBLIC PROPERTIES */
    public $serverstatus    = true;                 // Specifies whether or not the server is active
    public $url_payment     = "https://test.api.securepay.com.au/xmlapi/periodic";
    public $url_storage     = "https://test.api.securepay.com.au/xmlapi/token";

    public $message;                                // The document containing the xml request
    public $response;                               // The document containig the response


    
    /**
     * CONSTRUCTOR
     * @param string $merchantID The ID used to authenticate the request
     * @param string $password The password used to authenticate the request
     */
    function __construct(string $merchantID, string $password)
    {
        // Setting instance properties
        $this->merchantID = $merchantID;
        $this->password = $password;

        $this->message = new \DOMDocument();        // Creating new DOMDocument
        $this->message->preserveWhiteSpace = false; // Remove whitespace during parsing
        $this->message->formatOutput = true;        // Format the output for debugging

        $this->response = new \DOMDocument();       // Creating new DOMDocument
        $this->response->preserveWhiteSpace = true; // Remove whitespace during parsing
        $this->response->formatOutput = true;       // Format the output for debugging

        // Executing startup functions
        $this->gen_message();
        // $this->ping();
    }



    /** PUBLIC FUNCTIONS */

    /**
     * Runs an echo request to check the server status
     */
    public function ping()
    {
        $this->set_requestType("Echo");         // Setting the request type to echo
        $this->post();                          // Posting request

        // Travering response
        $xpath = new \DOMXPath($this->response);
        $status = (int)$xpath->query("/SecurePayMessage/Status/statusCode")[0]->nodeValue;

        // Setting server status
        if($status === 0) { $this->serverstatus = true; }
        else { $this->serverstatus = false; }
    }


    /**
     * Adds a credit card or direct debit payor to SecurePay and stores it  
     * @param string $type The payor type, one of ["card", "bank"]
     * @param string $id The payor ID used to identify and trigger payments 
     * @param array $data Either the credit card or bank information to be stored
     * Returns true if succesful
     */
    public function add_payor(string $type, string $id, array $data): bool
    {
        // Validating inputs
        if(!in_array($type, ["card", "bank"])) {
            throw new \Exception(sprintf("Error, invalid payor type '%s', must be one of [%s].", 
            $type, "'card', 'bank'"));
        }
        foreach($this->valid_data[$type] as $key) {
            if(!key_exists($key, $data)) {
                throw new \Exception(sprintf("Error, invalid %s data, cannot find '%s'", 
                $type, $key));
            }
        }

        // Creating Periodic Node
        $periodic = \utils\parse_xml(   <<<XML
        <Periodic>
            <PeriodicList count="1">
                <actionType>add</actionType>
                <clientID>$id</clientID>
                <periodicType>4</periodicType>
            </PeriodicList>
        </Periodic>
        XML);
        
        // Creating the payor payload
        $payor = null;
        if($type === "card") {                  // If we're adding a card
            // Getting the properties
            $cardNumber = $data["cardNumber"];
            $cvv        = $data["cvv"];
            $expiryDate = $data["expiryDate"];

            // Creating XML payload
            $payor =  \utils\parse_xml(   <<<XML
            <CreditCardInfo ID="1" >
                <cardNumber>$cardNumber</cardNumber>
                <cvv>$cvv</cvv>
                <expiryDate>$expiryDate</expiryDate>
            </CreditCardInfo>
            XML);
        }
        elseif($type == "bank") {               // If we're adding a bank
            // Getting the properties
            $bsbNumber      = $data["bsbNumber"];
            $accountNumber  = $data["accountNumber"];
            $accountName    = $data["accountName"];

            // Creating XML payload
            $payor = \utils\parse_xml(    <<<XML
            <DirectEntryInfo ID="1" >
                <bsbNumber>$bsbNumber</bsbNumber>
                <accountNumber>$accountNumber</accountNumber>
                <accountName>$accountName</accountName>
            </DirectEntryInfo>
            XML);
        }

        // Importin nodes
        $xml = $this->message;
        $node_periodic = $xml->importNode($periodic, true);
        $node_payor = $xml->importNode($payor, true);

        // Appending to message
        $xml->firstChild->appendChild($node_periodic);
        $node_periodic->firstChild->appendChild($node_payor);

        // Posting payload
        $this->set_requestType("Periodic");
        $this->post();
      
        return true;
    }


    /**
     * 
     */
    public function trigger_payment(string $type, array $data)
    {

    }



    /** PRIVATE FUNCTIONS */

    /**
     * Finalises the request and sends off the payload 
     */
    private function post()
    {
        // Checking server status before continuing
        if(!$this->serverstatus) {
            throw new \Exception("Error, server status false, connection could not be established");
        }

        // Setting ID and timestamp, must be unique for each post
        $xpath = new \DOMXPath($this->message); // Creating XPath for traversal
        $xpath->query($this->map["messageID"])[0]->nodeValue = $this->gen_uuid();
        $xpath->query($this->map["messageTimestamp"])[0]->nodeValue = $this->gen_timestamp();

        // Setting up request
        $body = $this->message->saveXML();      // Getting the XML message as a string
        $headers = [                            // Declaring header values
            "host: test.securepay.com.au",
            "content-type: text/xml",
            "content-length: " . strlen($body)
        ];

        // Sending off
        $response = "";                         // Declaring response to be written to
        $status = \http\curl_payload(           // Sending off curl payload
            "POST",                             // The type of request
            $this->url_payment,                 // The url to send it to
            $headers,                           // Our header entries
            $body,                              // The data of our message
            $response                           // The string we want our response to be written to
        );

        // Validating
        if($status === false) {                 // If the status is false we throw an error
            throw new \Exception("Error, Request not sent, curl error:\n" . $response);
        }

        var_dump($response);

        // Setting response XML and validating
        $this->response->loadXML($response);
        // $this->validate_response();
    }


    /**
     * Validates the response to check everything matches our request
     */
    private function validate_response()
    {
        $check = [                              // Creating a map of properties to check
            $this->map["messageID"],
            $this->map["requestType"],
            "/SecurePayMessage/MerchantInfo/merchantID"
        ];

        // Getting xpaths for traversal
        $req_path = new \DOMXPath($this->message);
        $res_path = new \DOMXPath($this->response);

        // Checking for equivelent properties for security
        foreach($check as $path) {
            $req_prop = $req_path->query($path)[0]->nodeValue;
            $res_prop = $res_path->query($path)[0]->nodeValue;

            if($req_prop === $res_prop) {       // If the values match
                continue;                       // Then continue to the next one
            }

            throw new \Exception(sprintf(   <<<EOT
            Error, invalid response from server.
            The Response property does not match the Request property
            
            Path:       '%s'
            Request:    '%s'
            Response:   '%s'"
            EOT, $path, $req_prop, $res_prop
            ));
        }
    }


    /**
     * Sets the request type element on our xml message  
     * @param string $type The request type to be set, must be a valid type  
     * Returns true if succesful
     */
    private function set_requestType(string $type): bool
    {
        // Validating input
        if(!in_array($type, $this->valid_data["requestType"])) {
            throw new \Exception(sprintf(
                "Error, invalid request type '%s', must be one of: [%s].", 
                $type, implode($this->valid_data["requestType"])));
        }

        // Setting type
        $xpath = new \DOMXPath($this->message);     // Creating xpath for traversal
        $xpath->query($this->map["requestType"])[0]->nodeValue = $type;

        return true;
    }


    /**
     * Generates the base XML message for sending to SecurePay and applies it to our DOM  
     * Returns true if succesful 
     */
    private function gen_message(): bool
    {
        /**
         * Generating our base XML string  
         *   
         * <MessageInfo> Is used by securepay to identify the message as unique  
         * <MerchantInfo> Holds the credentials, securepay will use this to authenticate  
         * <RequestType> Is set before a request is sent, must be one of ["Periodic", "addToken", "lookupToken", "Echo"]
         */
        $this->message->loadXML(    <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <SecurePayMessage>
        
            <MessageInfo>
                <messageID></messageID>
                <messageTimestamp></messageTimestamp>
                <timeoutValue>60</timeoutValue>
                <apiVersion>spxml-3.0</apiVersion>
            </MessageInfo>
            
            <MerchantInfo>
                <merchantID>$this->merchantID</merchantID>
                <password>$this->password</password>
            </MerchantInfo>
        
            <RequestType></RequestType>
        
        </SecurePayMessage>
        XML);

        return true;
    }


    /**
     * Generates and returns a universal unique identifier (UUID) (version 1) for use in "messageID"  
     *   
     * *Complies to the RFC 4122 standard*  
     */
    private function gen_uuid(): string
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
    private function gen_timestamp(): string
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