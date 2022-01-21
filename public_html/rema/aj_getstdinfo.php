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

if (isNum($sid)){
    
    // query string
    if (getPost('flag') == 1) {
        
        $q = "SELECT students.Name, students.Flagged, students.FlagNotes, students.YearlyContract, classes.Name AS Class, schools.Name AS Unit, users.Name AS Teacher " .
            "FROM students JOIN classes ON students.Class = classes.ID JOIN users ON classes.User = users.ID JOIN schools ON classes.School = schools.ID " .
            "WHERE students.ID = " . $sid;
        
    }
    else {
        
        $q = "SELECT students.Name, students.Status, students.Reason, students.Notes, students.YearlyContract, classes.Name AS Class, schools.Name AS Unit, users.Name AS Teacher " .
            "FROM students JOIN classes ON students.Class = classes.ID JOIN users ON classes.User = users.ID JOIN schools ON classes.School = schools.ID " .
            "WHERE students.ID = " . $sid;
        
    }
    
    // fetch data
    if ($row = $db->query($q)->fetch_assoc()){
        
        $resArr = array();
        
        foreach ($row as $key => $value) {
            // encode values
            $resArr[$key] = base64_encode($value);
        }
        
        // send json encoded array
        echo json_encode($resArr);
        
    }
    else {
        echo json_encode(array("Error" => base64_encode("Aluno não encontrado.")));
    }
    
}
else {
    echo json_encode(array("Error" => base64_encode("Parametro inválido.")));
}

$db->close();

?>