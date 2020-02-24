<?php namespace integratedpay\debug;


/**
 * Finds and returns a list of available non standard function names that match a given string
 */
function find_functions(string $searchparam): array
{
    $defined_functions = get_defined_functions()["user"];
    $match_functions = [];

    foreach($defined_functions as $function)
    {
        if(strpos(strtolower($function), $searchparam) !== false) {
            array_push($match_functions, $function);
        }
    }

    return $match_functions;
}


/**
 * Finds and returns a list of available class names that match a given string
 */
function find_classes(string $searchparam): array
{
    $defined_classes = get_declared_classes();
    $match_classes = [];

    foreach($defined_classes as $class)
    {
        if(strpos(strtolower($class), $searchparam) !== false) {
            array_push($match_classes, $class);
        }
    }

    return $match_classes;
}



define("LOG_JSON", 0);
define("LOG_PRINT", 1);

class Log
{
    static public $path = "_integratedpay.log";


    /**
     * Appends a string or given argument to the log file
     * @param $arg The variable to be written to the file
     * @param string $type Optional. If supplied will alter how the text is displayed in the file
     * @param string $action Optional. If supplied will be listed next to date in the header
     */
    static public function append($arg, string $type=null, string $action=null)
    {
        $value = $arg;                              // Declaring value variable
        
        // Applying output modifiers
        switch($type) {
            case null:
                break;

            case 0:
                $value = json_encode($arg, JSON_PRETTY_PRINT);
                break;
            
            case 1:
                $value = print_r($arg, true);
                break;
        }

        // Generating Header
        $header = "[" . date("Y/m/d - h:i:s");
        if($action !== null) {
            $header = $header . " - " . $action . "]";
        }
        else {
            $header = $header . "]";
        }

        // Putting together entry and appending to file
        $entry = $header . "\n" . $value . "\n";
        file_put_contents(Log::$path, $entry, FILE_APPEND);
    }


    /**
     * Clears/empties the log file
     */
    static public function clear()
    {
        file_put_contents(Log::$path, "");
    }
}


?>