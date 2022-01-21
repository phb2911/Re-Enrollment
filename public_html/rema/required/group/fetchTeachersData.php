<?php

function fetchTeachersData(&$db, $gid){
    
    global $db;

    $tch = array();
    
    $result = $db->query("CALL spFetchGroupInfo($gid);");
    
    while ($row = $result->fetch_assoc()){
        $tch[] = $row;
    }
    
    $result->close();
    
    // clear db stored results
    clearStoredResults($db);
    
    return $tch;
    
}

?>