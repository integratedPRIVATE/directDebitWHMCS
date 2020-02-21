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

?>