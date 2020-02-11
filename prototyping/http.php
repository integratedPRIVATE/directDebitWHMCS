<?php namespace http\xml;

    function post(string $url, array $request)
    {
        return __request__("POST", $url, $request);
    }

    
    function get(string $url, array $request)
    {
        return __request__("GET", $url, $request);
    }


    function __request__(string $type, string $url, array $request)
    {
        // Checking request type, declared as array to allow for adding extra types
        $valid_types = ["POST", "GET"];
        if(!in_array($type, $valid_types)) {                // If type not valid throw exception
            throw new \Exception("Request type '" . $type . "' invalid, must be one of '"
             . implode(", ", $valid_types));
        }

        // Getting variables from request
        $headers_obj = key_exists("headers", $request) ? $request["headers"] : [];
        $body = key_exists("body", $request) ? $request["body"] : "";

        // Checking header entries for valid XML request and setting if needed
        if(!key_exists("content-type", $headers_obj)) {
            $headers_obj["content-type"] = "text/xml";
        }
        if(!key_exists("content-length", $headers_obj)) {
            $headers_obj["content-length"] = strlen($body);
        }

        // Encoding header to be http friendly
        $headers = [];
        foreach($headers_obj as $key => $value) {                    // For each item in header
            $entry = strval($key) . ": " . strval($value);           // Derive entry string
            array_push($headers, $entry);                            // Append to the headers array
        }

        return __curl__($type, $url, $headers, $body);
    }


    function __curl__(string $type, string $url, array $headers, string $body)
    {
        // Initialising curl
        $curl = curl_init($url);

        // Setting curl properties
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER      => true,
            CURLOPT_CUSTOMREQUEST       => $type,
            
            CURLOPT_HTTPHEADER          => $headers,
            CURLOPT_POSTFIELDS          => $body
        ]);

        // Executing curl command, getting response
        $response = curl_exec($curl);

        // If the command ran into an error, we use that instead
        if($response === false) {
            $response = "CURL ERROR: " . curl_error($curl);
        }
        else {
            $response = simplexml_load_string($response);
        }
        
        // Closing the curl instance and returning the response
        curl_close($curl);
        return $response;
    }
?>