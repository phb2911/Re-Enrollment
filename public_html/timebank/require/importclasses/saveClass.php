<?php

function saveClass(&$db, $tbId, $tbStartDate, $tbEndDate, &$msg){
    
    // initialize variables
    $selOpt = getPost('selopt');
    $tchId = getPost('teacher');
    $tchFromFile = getPost('tchfromfile');
    $newTch = trim(getPost('newtch'));
    $newName = trim(getPost('class_name'));
    $semester = getPost('sem');
    $date1 = getPost('date1');
    $date2 = getPost('date2');
    $date3 = getPost('date3');
    $date4 = getPost('date4');
    $mode = getPost('mode');
    $daysArr = getPost('days');
    $days = 0;
    $daysCount = 0;
    
    // validate input
    if (!isNum($selOpt) || $selOpt == 0 || $selOpt > 5){
        $msg = 'Por favor selecione a optção para professor da turma.';
        return false;
    }
    
    if ($selOpt == 1 && (!isNum($tchId) || $tchId == 0 || !$db->query("SELECT COUNT(*) FROM `users` WHERE `ID` = $tchId AND `Blocked` = 0 AND Status < 2")->fetch_row()[0])){
        $msg = 'O professor selecionado não é válido.';
        return false;
    }
    
    if ($selOpt == 2 && strlen($newTch) < 4){
        $msg = 'O nome do professor deve conter pelo menos 4 caracteres.';
        return false;
    }
    
    if ($selOpt > 2){
        if (!strlen($tchFromFile)){
            $msg = 'O nome do professor foi enviado incorretamente.';
            return false;
        }
        elseif (!$tchId = $db->query("SELECT `ID` FROM `users` WHERE `Name` = '" . $db->real_escape_string($tchFromFile) . "'")->fetch_row()[0]) {
            $msg = 'O nome do professor não foi encontrado no banco de dados.';
            return false;
        }
    }
    
    if (isset($daysArr) && is_array($daysArr)) {
            
        foreach ($daysArr as $key => $val){
            if (isNum($key) && ($key == 1 || $key == 2 || $key == 4 || $key == 8 || $key == 16 || $key == 32 || $key == 64) && $val === '1'){
                $days += intval($key, 10);
                $daysCount++;
            }
        }

    }
    
    // validate data
    if (!validateData($newName, $semester, $tbStartDate, $tbEndDate, $date1, $date2, $date3, $date4, $mode, $daysCount, $msg)){
        return false;
    }
    
    // create new teacher
    if ($selOpt == 2){
        
        // create new teacher
        // create login id
        $loginId = generateLoginID($newTch);

        // create random salt
        $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));

        // pasword is the same as login id
        // double hash password
        $pwdHash = hash('sha512', hash('sha512', $loginId) . $salt);

        // create new teacher
        if ($db->query("INSERT INTO `users` (`Name`, `LoginID`, `Password`, `Salt`, `Status`) VALUES ('" . $db->real_escape_string($newTch) . "', '" . $db->real_escape_string($loginId) . "', '$pwdHash', '$salt', 0)")){
            $tchId = $db->insert_id; // retrieve teacher ID
        }
        else {
            $msg = 'Erro ao criar professor.';
            return false;
        }
        
    }
    elseif ($selOpt > 2 && !$db->query("UPDATE `users` SET `Blocked` = 0, `Status` = IF(`Status` = 0, 0, 1) WHERE `ID` = $tchId")){ // unblock and set teacher status
        $msg = 'Não foi possível reativar o professor ou atribuir o status de professor ao colaborador.';
        return false;
    }
    
    // set duration and excess minutes according to the selected mode
    if ($mode == 1){
        $newDuration = '01:15';
        $excMin = 0;
    }
    elseif ($mode == 2){
        $newDuration = '02:15';
        $excMin = 15;
    }
    else {
        $newDuration = '02:30';
        $excMin = 0;
    }

    $duration = timeToNum($newDuration); // duration in minuts

    // convert to DB date format
    $d1 = parseDate($date1);
    $d2 = parseDate($date2);
    $d3 = parseDate($date3);
    $d4 = parseDate($date4);

    // generate querey string
    $q = "INSERT INTO tb_classes (User, Name, Days, Duration, ExcessMinutes, StartDate, EndDate, StartClass, EndClass, Bank, Semester) Values ($tchId, '" . 
            $db->real_escape_string($newName) . "', $days, $duration, $excMin, '$d1', '$d2', '$d3', '$d4', $tbId, $semester)";

    // insert data
    if (!$db->query($q)){
        $msg = 'Error: ' . $db->error;
        return false;
    }
    
    return true;
    
}

//--------------------------------------------------------------

function generateLoginID($userName){
    
    $res = replaceSpecialChars(substr(strtolower(preg_replace("/\s+/", "", $userName)), 0, 5));
    
    while (strlen($res) < 8){
        $res .= mt_rand(0, 9);
    }
    
    return $res;
    
}

?>