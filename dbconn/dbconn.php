<?php

function mysqliConnObj($charset = 'latin1'){
    
    $serverName = isset($_SERVER['SERVER_NAME']) ? strtolower($_SERVER['SERVER_NAME']) : '';
    
    if (strpos($serverName, 'businessdomainname') !== false){
        
        // removed for privacy
        $servername = "";
        $username = "";
        $password = "";
        $dbname = "";
        
    }
    else {
        
        // LOCAL
        $servername = "localhost";
        $username = "sa";
        $password = "";
        $dbname = "";

    }
    
    $db = new mysqli($servername, $username, $password, $dbname);
    $db->set_charset($charset);
    
    return $db;
    
}

?>