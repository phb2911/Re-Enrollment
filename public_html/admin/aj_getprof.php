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

if (isNum($uid) && isNum($index) && $index <= 15 && $db->query("SELECT COUNT(*) FROM users WHERE ID = $uid")->fetch_row()[0]){
    
    $profCols = array(
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
    
    // fetch data
    $res = $db->query("SELECT " . $profCols[intval($index, 10)] . " FROM user_profile WHERE User = $uid")->fetch_row()[0];
    
    // base 64 encode, json encode and send it
    echo json_encode(array("Result" => base64_encode($res)));
    
}
else {
    echo json_encode(array("Error" => base64_encode("Parametros inválidos.")));
}

$db->close();

?>