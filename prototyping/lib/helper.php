<?php namespace helper;

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


    /**
     * Dynamically checks a given string against a list of strings to check if it matches
     */
    function validate_string(string $input, array $validlist, string $error): bool
    {
        // Check if the input exists in the array of valid strings
        if(!in_array($input, $validlist)) {
            throw new \Exception("Error! "
            . $error . "\nString must be one of: [" . implode(", ", $validlist) . "].");
        }

        // Returns true if everything passes
        return true;
    }

?>