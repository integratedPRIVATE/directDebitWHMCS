<?php namespace http;

/**
 * Takes in http paramaters and used curl to send out a payload, sets the response and returns true if succesful
 * @param string $type The type of HTTP request being sent, one of [POST|GET|DELETE]
 * @param string $url The url the payload is sent to
 * @param array $headers A 1D list of headers that are sent as part of the payload, must be formatted as ["key:value"]
 * @param string $body The body or data of the payload, must be a string, either JSON or XML format
 * @param mixed $response A reference to the response variable, the function will write the response to this variable
 * 
 * @return bool Returns true if the request is succesful, if not the response will be set as an error and it returns false
 */
function curl_payload(string $type, string $url, array $headers, string $body, &$response): bool
{
    // SETUP //
    $curl = curl_init($url);                    // Initialising a curl instance with reference to the url

    curl_setopt_array($curl, [                  // Setting the curl command properties
        CURLOPT_RETURNTRANSFER  => true,        // Must return a response
        CURLOPT_CUSTOMREQUEST   => $type,       // The request type, i.e POST

        CURLOPT_HTTPHEADER      => $headers,    // The header entries to be read by the recieving server
        CURLOPT_POSTFIELDS      => $body        // Sets the data sent with the payload, doesn't affect non POST types
    ]);


    // EXECUTION //
    $resp = curl_exec($curl);                   // Executing the curl command and recieving the response


    // CHECKING RESPONSE //
    if($resp === false) {                       // If the command did not execute correctly
        $response = curl_error($curl);          // Setting the response to the error
        curl_close($curl);                      // Closing down the curl instance
        return false;                           // Returning false
    }

    else {                                      // If the command executed succesfully
        $response = $resp;                      // Setting the response variable
        curl_close($curl);                      // Closing down the curl instance
        return true;                            // Returning true
    }
}