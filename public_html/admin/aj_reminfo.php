<?php

require_once '../genreq/ajax.php';
require_once '../genreq/genreq.php';

header('Content-type: text/html; charset=iso-8859-1');

$db = mysqliConnObj('latin1');

if ($db->connect_errno > 0){
    $json = json_encode(array("Error" => base64_encode("Não foi possível conectar ao banco de dados.")));
    die($json);
}

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn(true)){
    $db->close();
    $json = json_encode(array("Error" => base64_encode("É necessário fazer o login novamente.")));
    die($json);
}

$uid = getPost('uid');

if (isNum($uid) && $db->query("SELECT COUNT(*) FROM users WHERE ID = $uid")->fetch_row()[0]){

    if ($db->query("UPDATE user_profile SET `StartYear` = NULL, `StartSemester` = NULL, `Education` = NULL WHERE `User` = $uid")){
        echo json_encode(array()); // success
    }
    else {
        echo json_encode(array("Error" => base64_encode("Erro ao remover data.")));
    }
    
}
else {
    echo json_encode(array("Error" => base64_encode("Parametros inválidos.")));
}

$db->close();

?>