<?php namespace WHMCS\Module\Gateway\IntegratedPay;

// Using library classes
use WHMCS\Module\Gateway\IntegratedPay\HTTPRequest;
use WHMCS\Module\Gateway\IntegratedPay\SecurePayMessage;


class SecurePay
{
    private static $url_test = "https://test.api.securepay.com.au/xmlapi/periodic";
    private static $url_live = "https://api.securepay.com.au/xmlapi/periodic";

    public $merchantID  = "";
    public $password    = "";

    
    function __construct(string $merchantID, string $password)
    {
        // Setting member variables
        $this->merchantID = $merchantID;    
        $this->password = $password;
    }

    
    /**
     * Stores a payor's bank details in Securepay
     * @param string $id The payor ID used for triggering future payments
     * @param string $bsb The Route or BSB number for the bank 
     * @param string $number The account number 
     * @param string $name The name of the bank
     * @param string $credit whether the bank is charging credit or not, type of [yes|no]
     */
    public function store_directdebit(string $id, string $bsb, string $number, string $name, string $credit)
    {
        // Creating core message
        $message = new SecurePayMessage("Periodic", $this->merchantID, $this->password);
        $message->payload = [
            "actionType"        => "add",
            "clientID"          => $id,
            "DirectEntryInfo"   => [
                "bsbNumber"         => $bsb,
                "accountNumber"     => $number,
                "accountName"       => $name,
                "creditFlag"        => $credit
            ],
            "amount"            => 10,
            "periodicType"      => 4,
        ];
        $message_str = $message->toXML();

        // Creating header
        $headers = [
            "host: test.securepay.com.au",
            "content-type: text/xml",
            "content-length: " . strlen($message_str)
        ];

        // Declaring HTTPRequest object
        $request = new HTTPRequest(SecurePay::$url_test, $headers, $message_str);

        $request->POST();
        file_put_contents("_LOG.txt", $message_str . "\n\n");

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($request->response);

        file_put_contents("_LOG.txt", $doc->saveXML(), FILE_APPEND);
    }


    public function trigger_payment(string $id)
    {

    }


    private static function list_to_xml(array $list)
    {
        // Creating DOM object and setting it's properties for human readability
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML('<?xml version="1.0" encoding="UTF-8>');

        $message = $doc->appendChild($doc->createElement("SecurePayMessage"));
        $stack = [["parent" => $message, "children" => $list]];

        while(true) {                           // Breadth first forloop
            $item = array_shift($stack);        // Getting first entry in stack
            $par = $item["parent"];             // Get the parent

            // Looping through each child
            foreach($item["children"] as $key => $value) {
                $node = $par->appendChild($doc->createElement($key));
                
                if(gettype($value) === "array") {
                    array_push($stack, [
                        "parent"    => $node,
                        "children"  => $value
                    ]);
                    continue;
                }

                $node->nodeValue = $value;
            }


            if(count($stack) < 1) {break;}
        }

        return $doc;
    }
}

