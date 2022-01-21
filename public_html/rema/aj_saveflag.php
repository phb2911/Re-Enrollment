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
$notes = (isset($_POST['notes']) ? trim(base64_decode($_POST['notes'])) : ''); // decode base64
$unflag = getPost('unflag');
$notesLen = strlen($notes);

if (isNum($sid) && ($unflag == 1 || ($notesLen > 0 && $notesLen)) <= 500 && !!$db->query("SELECT COUNT(*) FROM students WHERE ID = $sid")->fetch_row()[0]){
    
    // build query
    if ($unflag == 1){
        $q = "UPDATE students SET Flagged = 0, FlagNotes = null WHERE ID = $sid";
    }
    else {
        $q = "UPDATE students SET Flagged = 1, FlagNotes = '" . $db->real_escape_string($notes) . "' WHERE ID = $sid";
    }
    
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

?>