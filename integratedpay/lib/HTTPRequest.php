<?php namespace WHMCS\Module\Gateway\IntegratedPay;


class HTTPRequest
{
    public  $url;                               // The URL the request is being sent to
    public  $headers;                           // The list of header entries 
    public  $message;                           // The message data that's being sent
    public  $response;                          // The response from the server
    public  $status;                            // The status of the curl request


    /**
     * @param string $url The URL the request is being sent to
     * @param array $headers Optional. The list of header entries that define the request
     * @param string $message Optional. The message data that's being sent with the request
     */
    function __construct(string $url, array $headers=[], string $message="")
    {
        // Setting instance properties
        $this->url      = $url;
        $this->headers  = $headers;
        $this->message  = $message;
    }


    /**
     * Adds a http header to the headers member  
     * Formats the given property and value arguments to the correct HTTP format
     * @param string $property The header key
     * @param string $value The value of the header entry  
     *   
     * @return bool Returns true if succesful
     */
    public function add_header(string $property, string $value): bool
    {
        $entry = $property . ": " . $value;     // Formatting entry
        array_push($this->headers, $entry);     // Adding to headers member

        return true;
    }


    /**
     * Returns the response as an DOMDocument function
     */
    public function response_as_xml(): \DOMDOcument
    {
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($this->response);

        return $doc;
    }


    public function POST() { $this->curl("POST"); }

    public function GET() { $this->curl("GET"); }




    /**
     * Sends out a curl request based on instance member variables  
     * Sets the response and status members based on request
     * @param string $type The type of response to be sent, must be one of [POST|GET|DELETE]
     */
    private function curl(string $type)
    {
        // GETTING PARAMS
        $url = $this->url;
        $headers = $this->headers;
        $message = $this->message;

        // SETUP
        $curl = curl_init($url);                // Initialising a curl instance with url

        curl_setopt_array($curl, [              // Setting the curl command properties
            CURLOPT_RETURNTRANSFER  => true,    // Must return a response
            CURLOPT_CUSTOMREQUEST   => $type,   // The request type, i.e POST

            CURLOPT_HTTPHEADER      => $headers, 
            CURLOPT_POSTFIELDS      => $message 
        ]);


        // EXECUTION
        $resp = curl_exec($curl);                   // Executing curl and getting the response


        // CHECKING RESPONSE
        if($resp === false) {                       // If the command did not execute correctly
            $this->response = curl_error($curl);    // Setting the response to the error
            $this->status = false;                  // Setting the status to false
            curl_close($curl);                      // Closing down the curl instance
        }

        else {                                      // If the command executed succesfully
            $this->response = $resp;                // Setting the response variable
            $this->status = true;                   // Setting the status to true / successful
            curl_close($curl);                      // Closing down the curl instance
        }
    }
}