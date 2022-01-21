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
$year = getPost('yr');
$sem = getPost('sem');
$eduEnc = getPost('edu');
$eduDec = base64_decode($eduEnc);

if (validateInput($uid, $year, $sem)){
    
    // save data
    if ($db->query("CALL spProfAddStEdu($uid,$year,$sem,'" . $db->real_escape_string($eduDec) . "')")){
        $res = array(
            "Year" => $year,
            "Semester" => $sem,
            "Education" => $eduEnc
        );
    }
    else {
        $res = array("Error" => base64_encode("Erro inserindo dados no banco de dados."));
    }
    
    // clear stored results after a stored procedure call
    clearStoredResults($db);
    
    // json encode and send it
    echo json_encode($res);
    
}
else {
    echo json_encode(array("Error" => base64_encode("Parametros inválidos.")));
}

$db->close();

//------------------ validate input

function validateInput($uid, $year, $sem){
    
    global $db;
    
    return (preg_match('/^([0-9])+$/', $uid) && 
            preg_match('/^([0-9])+$/', $year) && $year >= 2000 && $year <= 2050 &&
            preg_match('/^([0-9])+$/', $sem) && ($sem == 1 || $sem == 2) && 
            $db->query("SELECT COUNT(*) FROM users WHERE ID = $uid")->fetch_row()[0]);
    
}

?>