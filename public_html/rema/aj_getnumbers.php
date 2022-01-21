<?php

require_once '../genreq/ajax.php';
require_once '../genreq/genreq.php';

header('Content-type: text/html; charset=iso-8859-1');

$db = mysqliConnObj('latin1');

if ($db->connect_errno > 0){
    $json = json_encode(array("Error" => base64_encode("Não foi possível conectar ao banco de dados.")));
    die($json);
}

$uid = getPost('uid');
$cid = getPost('cid');
$flag = (getPost('f') == 1 ? 1 : 0);

// validate input
if (isNum($uid) && isNum($cid)){

    // retrieve data
    $row = $db->query("CALL spFetchNumbers($cid,$uid,$flag)")->fetch_assoc();

    $resArr = array(
        "Active" => +$row['Active'],
        "ActiveEnrolled" => +$row['ActEnr'],
        "DropOut" => +$row['DropOut'],
        "DropOutEnrolled" => +$row['DropOutEnr'],
        "NotContacted" => +$row['NotCont'],
        "Contacted" => +$row['Contacted'],
        "NotComingBack" => +$row['NotCmBk'],
        "Enrolled" => +$row['Enrolled'],
        "YearlyContract" => +$row['YearlyContract']
    );
    
    echo json_encode($resArr);
    
    // clear stored results after stored procedure call
    clearStoredResults($db);
    
}
else {
    echo json_encode(array("Error" => base64_encode("Os dados submetidos são inválidos.")));
}

$db->close();

?>