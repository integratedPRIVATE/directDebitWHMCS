<?php namespace WHMCS\Module\Gateway\IntegratedPay;

class Log
{
    static private $path = "_integratedpay.log";

    static public $JSON = 0;
    static public $PRINT = 1;


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