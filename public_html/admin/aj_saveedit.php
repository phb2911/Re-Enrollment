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
$index = getPost('ind');
$content = base64_decode(getPost('cont'));

$columns = array(
    'ClassProfile',
    'StudentCare',
    'ParentContact',
    'PortalMonitoring',
    'EmailSubmition',
    'ClassRecordBook',
    'Correction',
    'EventRecording',
    'CatchUpClass',
    'PortalTraining',
    'GradeRegistration',
    'ClassPrepForm',
    'UC',
    'ClassAbsence',
    'MeetingAbsence',
    'Other'
);

if (validateInput($uid, $index, count($columns))){
    
    // check if user profile already exists
    if ($db->query("SELECT COUNT(*) FROM `user_profile` WHERE `User` = $uid")->fetch_row()[0]){
        // update
        $q = "UPDATE `user_profile` SET `" . $columns[$index] . "` = NULLIF('" . $db->real_escape_string($content) . "','') WHERE `User` = $uid;";
    }
    else {
        // insert
        $q = "INSERT INTO `user_profile` (`User`, `" . $columns[$index] . "`) VALUES ($uid, NULLIF('" . $db->real_escape_string($content) . "',''))";
    }
    
    if ($db->query($q)){
        echo json_encode(array()); // submit empty array on success
    }
    else {
        echo json_encode(array("Error" => base64_encode("Erro inserindo dados no banco de dados.")));
    }
    
}
else {
    echo json_encode(array("Error" => base64_encode("Parametros inválidos.")));
}

$db->close();

//------------------ validate input

function validateInput($uid, $index, $max){
    
    global $db;
    
    return (preg_match('/^([0-9])+$/', $uid) && 
            preg_match('/^([0-9])+$/', $index) && $index < $max &&
            $db->query("SELECT COUNT(*) FROM users WHERE ID = $uid")->fetch_row()[0]);
    
}

?>