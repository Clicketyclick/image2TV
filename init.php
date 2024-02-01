<?php

// CLI or HTTP
// https://stackoverflow.com/a/37600661
if (php_sapi_name() === 'cli') {
    // Remove '-' and '/' from keys
    for ( $x = 0 ; $x < count($argv) ; $x++ )
        $argv[$x]   = ltrim($argv[$x], '-/');
    // Concatenate and parse string into $_REQUEST
    parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);
}

?>