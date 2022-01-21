<?php

require_once '../genreq/ajax.php';
require_once '../genreq/genreq.php';

header('Content-type: text/html; charset=iso-8859-1');

$db = mysqliConnObj('latin1');

if ($db->connect_errno > 0){
    $json = json_encode(array("Error" => base64_encode("Não foi possível conectar ao banco de dados.")));
    die($json);
}

$sid = getPost('sid');
$status = getPost('status');
$reason = getPost('reason');
$notes = (isset($_POST['notes']) ? trim(base64_decode($_POST['notes'])) : ''); // decode base64

// validete input and check if 
if (validateInput($sid, $status, $reason, $notes)){
    
    // build query string
    $q = "UPDATE students SET Status = $status, Reason = " . ($status == 2 ? $reason : "null") . ", Notes = " . 
            (strlen($notes) > 0 ? "'" . $db->real_escape_string($notes) . "'" : "null") . " WHERE ID = $sid";
    
    // update record
    if ($db->query($q)){
        // success
        echo json_encode(array("StdId" => $sid));
    }
    else {
        echo json_encode(array("Error" => base64_encode("Dados inválidos.")));
    }
    
}
else {
    echo json_encode(array("Error" => base64_encode("Parametro inválido.")));
}

$db->close();

//---------------------------------

function validateInput($sid, $status, $reason, $notes){
    
    global $db;

    // validate values and check if student exists
    return (isNum($sid) && isNum($status) && $status <= 3 &&
            ($status != 2 || isNum($reason)) && strlen($notes) <= 2000) &&
            !!$db->query("SELECT COUNT(*) FROM students WHERE ID = $sid")->fetch_row()[0];
    
}

?>