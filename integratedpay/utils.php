<?php namespace utils;

/**
 * Validates an array object by checking if it contains a given list of keys, if strict is true it
 * checks if the object has keys that are not specifically listed in $valid_keys
 * @param array $object The array object being checked
 * @param array $valid_keys A 1D list of keys that $object must contain, must be strings
 * @param bool $strict Optional. Defines if the object should be checked for keys that are not valid
 * 
 * @return array Returns an array object, if unsuccessful object has properties, "key" which 
 * has the key it errored out on, and "errnum" which specifies if it was a valid key error (1)
 * or an invalid key error (2), the object always contains a, "status" which is true or false
 */
function validate_keys(array $object, array $valid_keys, bool $strict=false): array
{
    // Checking for valid keys
    foreach($valid_keys as $key) {              // For each entry in the valid keys list
        $check = key_exists($key, $object);     // Checking if the key exists in the object
        if($check === true) {                   // If the check is true (the key is valid)
            continue;                               // Continue to the next entry
        }
        return [                                // Return the error and status as an object
            "key" => $key, "errnum" => 1, "status" => false
        ];
    }

    if($strict === false) {                     // If strict is set to false
        return ["status" => true];              // Return success
    }

    // Checking invalid keys if strict is true
    foreach($object as $key => $value) {        // For each property in the object
        $check = in_array($key, $valid_keys);   // Checking if the key is valid
        if($check === true) {                   // If the check is true (the key is valid)
            continue;                               // Continue to the next entry
        }
        return [                                // Return the error and status as an object
            "key" => $key, "errnum" => 2, "status" => false
        ];
    }

    // Return status true as all checks where passed
    return ["status" => true];
}
