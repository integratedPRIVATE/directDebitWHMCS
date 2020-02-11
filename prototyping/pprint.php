<?php namespace pprint;

    /**
     * Takes a SimpleXMLElement object and prints it to the screen in a user friendly format
     * @param SimpleXMLElement $xml The object containing the XML elements to print
     */
    function xml(\SimpleXMLElement $xml)
    {   
        // Create DOM and set it's formatting properties
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        // Parse the xml, then print to screen
        $dom->loadXML($xml->asXML());
        echo $dom->saveXML();
    }

?>