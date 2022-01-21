<?php

$host = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'])['host'] : null;

if (!isset($host)) die('Invalid URL.');

$validHosts = array(
    'localhost',
    'domain'
);

$notFound = true;

foreach ($validHosts as $h){
    // valid host
    if (strpos(strtolower($host), strtolower($h)) !== false){
        $notFound = false;
        break;
    }

}

// a valid host was not found
if ($notFound) die('Invalid Host');

?>