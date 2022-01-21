<?php

function saveData(&$db, &$actTeachers, &$programs, &$levels, $cid, &$counters, &$msg){
    
    // set variables
    $fileName = getPost('f');
    $uid = getPost('uid');
    $cats = getPost('cats');
    $isDropOuts = (getPost('dos') === '1' ? '1' : '0');
    $actions = getPost('action');
    $teachers = getPost('teacher');
    $repById = getPost('repbyid');
    $teacherIDs = getPost('teacherId');
    $lvlaction = getPost('lvlaction');
    $lvlnames = getPost('levelName');
    $repprg = getPost('repprg');
    $replvl = getPost('replvl');
    $levelId = getPost('levelId');
    
    // counters
    $countStd = 0;        // students added
    $countTch = 0;        // new teachers created
    $countCls = 0;        // classes created
    $countSkpLine = 0;    // lines skipped
    $countLvl = 0;        // new levels created
    $countIgnStd = 0;     // students not created due to new theacher or new level ignored
    $countFlag = 0;
    
    // classify cols
    foreach ($cats as $col => $cat) {
        if ($cat == 1) $stdColumn = $col;
        elseif ($cat == 2) $teacherColumn = $col;
        elseif ($cat == 3) $clsColumn = $col;
        elseif ($cat == 4) $lvlColumn = $col;
    }
    
    $ignoreTchList = array();  // teachers to be ignored
    
    // process teacher actions
    if (is_array($actions)){
        
        foreach ($actions as $index => $act){

            $newTeacherName = trim($teachers[$index]);
            
            // validate teacher's name
            if (!strlen($newTeacherName)){
                $msg = 'Nome de professor inválido.';
                return false;
            }
            
            if ($act == 1){ // create teacher
                
                // create login id
                $loginId = generateLoginID($newTeacherName);
                
                // create random salt
                $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
        
                // pasword is the same as login id
                // double hash password
                $pwdHash = hash('sha512', hash('sha512', $loginId) . $salt);
                
                // create new teacher
                if ($db->query("INSERT INTO users (Name, LoginID, Password, Salt, Status) VALUES ('" . $db->real_escape_string($newTeacherName) . "', '" . $db->real_escape_string($loginId) . "', '$pwdHash', '$salt', 0)")){
                    $actTeachers[$db->insert_id] = $newTeacherName;
                    $countTch++;
                }
                else {
                    $msg = 'Erro ao criar professor.';
                    return false;
                }
                
            }
            elseif ($act == 2){ // ignore records containing teacher
                $ignoreTchList[] = $newTeacherName;
            }
            elseif ($act == 3){ // replace teacher
                
                // the theacher to be replaced is not an active teacher
                if (!isset($actTeachers[$repById[$index]])){
                    $msg = 'Parametros inválidos.';
                    return false;
                }
                
                $actTeachers[$repById[$index]] = $newTeacherName;
                
                
                
            }
            elseif ($act == 4 || $act == 5){
                
                // unblock teacher and if action = 5 set user as teacher and admin
                $q = "UPDATE users SET Blocked = 0" . ($act == 5 ? ', Status = 1' : '') . " WHERE ID = " . $teacherIDs[$index];
                
                if (is_array($teacherIDs) && isNum($teacherIDs[$index])){ // validate teacher id
                    
                    // update record
                    if (!$db->query($q)){
                        $msg = 'Internal error: ' . $db->error;
                        return false;
                    }
                    
                }
                else {
                    $msg = 'Parametros inválidos.';
                    return false;
                }
                
                // add teacher to the active theacher's list
                $actTeachers[$teacherIDs[$index]] = $newTeacherName;
                
            }
            
        }
        
    }
    
    $ignoreLvlList = array();  // levels to be ignored
    
    // process level actions
    if (is_array($lvlaction)){
        
        foreach ($lvlaction as $index => $act){
            
            $levelName = trim($lvlnames[$index]);
            
            // validate level name
            if (!strlen($levelName)){
                $msg = 'Nome do estágio inválido.';
                return false;
            }
            
            if ($act == 1){ // create new level
                
                $repProgID = $repprg[$index];
                
                // validate program
                if (!isNum($repProgID) || !isset($programs[$repProgID])){
                    $msg = 'Programa inválido.';
                    return false;
                }
                elseif ($db->query("INSERT INTO levels (Name, Program) VALUES ('" . $db->real_escape_string($levelName) . "', $repProgID)")){ // create level
                    $levels[$db->insert_id] = array('ID' => $db->insert_id, 'Name' => $levelName, 'Program' => $repProgID, 'Active' => 1);
                    $countLvl++;
                }
                else {
                    $msg = 'Erro ao criar programa.';
                    return false;
                }
                
            }
            elseif ($act == 2){ // ignore level
                $ignoreLvlList[] = $levelName;
            }
            elseif ($act == 3){ // replace level for another
                
                $lvlId = $replvl[$index];
                
                // validate level
                if (!isNum($lvlId) || !isset($levels[$lvlId])){
                    $msg = 'Estágio inválido.';
                    return false;
                }
                
                $levels[$lvlId]['Name'] = $levelName;
                                
            }
            elseif ($act == 4){ // reactivate existing level
                
                $lvlId = $levelId[$index];
                
                // validate level
                if (!isNum($lvlId) || !isset($levels[$lvlId])){
                    $msg = 'Estágio inválido.';
                    return false;
                }
                elseif (!$db->query("UPDATE levels SET Active = 1 WHERE ID = $lvlId")){ // activate level
                    $msg = 'Internal error: ' . $db->error;
                    return false;
                }
                
                $levels[$lvlId]['Active'] = 1;
                
                
            }
            
        }
        
    }
        
    // inserir data
    $classCache = array(); // for better peformance, store classes previously used
    $tableOpen = false;
    $trOpen = false;
    $tdOpen = false;
    $firstRow = true;
    $curCol = 0;
    $curRow = 0;
    $val = '';
    $stdVal = '';
    $clsVal = '';
    $teaVal = '';
    $lvlVal = '';
    $q = '';

    $xml = new XMLReader();

    $xml->open(UPLOAD_DIR . $fileName);

    while($xml->read()) {

        if (strtolower($xml->name) == 'table' && $tableOpen){
            // end of table
            break;
        }
        elseif (strtolower($xml->name) == 'table'){
            // beginning of table
            $tableOpen = true;
        }
        elseif (strtolower($xml->name) == 'tr' && $trOpen){

            // check if not first row, if values are not empty and if teacher is not in ignore list
            if (!$firstRow){

                if (!strlen($stdVal) || !strlen($teaVal) || !strlen($clsVal) || !strlen($lvlVal)){
                    // one of the values is empty
                    $countSkpLine++;
                }
                elseif (!in_array($teaVal, $ignoreTchList) && !in_array($lvlVal, $ignoreLvlList)){ // check if record in in one of the ignore lists

                    // get teacher id from active teachers array
                    $tid = getTeacherId($teaVal, $actTeachers);

                    // get level id from levels array
                    $lid = getLevelId($lvlVal, $levels);

                    // retrieve class ID from cached result
                    // perform case insensitive search (all $classCache values
                    //  are lower case)
                    $clsId = getClassId($classCache, strtolower($clsVal), $tid);

                    // class not previously cached
                    if ($clsId === false){

                        // try to find class in db
                        if (!$clsId = $db->query("SELECT ID FROM classes WHERE Name = '" . $db->real_escape_string($clsVal) . "' AND User = $tid AND Campaign = $cid AND School = $uid AND Level = $lid")->fetch_row()[0]){
                            // class not found, create it
                            // NOTHING HAPPENS IF CLASS NOT CREATED SUCCESSFULLY
                            $db->query("INSERT INTO classes (Name, Level, User, Campaign, School) VALUES ('" . $db->real_escape_string($clsVal) . "', $lid, $tid, $cid, $uid)");
                            $clsId = $db->insert_id;

                            $countCls++;
                            
                        }

                        // cache result
                        // store lower case values for future
                        // case insensitive search.
                        // store teacher id as well
                        $classCache[$clsId] = array(strtolower($clsVal), $tid);

                    }


                    // insert students 100 at a time
                    if ($countFlag == 0){
                        // initialize new query
                        $q = "INSERT INTO students (Name, Class, Situation) VALUES ('" . $db->real_escape_string($stdVal) . "', $clsId, $isDropOuts)";
                    }
                    else {
                        // add next student to query
                        $q .= ",('" . $db->real_escape_string($stdVal) . "', $clsId, $isDropOuts)";
                    }

                    $countStd++;
                    $countFlag++;

                    // 100 students mark reached
                    if ($countFlag == 100){
                        // execute query
                        $db->query($q);
                        // reset flag
                        $countFlag = 0;
                    }
                    
                }
                else {
                    // student not added because option to ignore teacher or level
                    // was selected
                    $countIgnStd++;
                }

            }

            // end of row
            $trOpen = false;
            $firstRow = false;
            // reset column flag
            $curCol = 0;

        }
        elseif (strtolower($xml->name) == 'tr'){
            // beginning of row
            $trOpen = true;
            if (!$firstRow) $curRow++;
        }
        elseif (strtolower($xml->name) == 'td' && $tdOpen){
            // end of cell
            $tdOpen = false;
            // remove excess white spaces
            $val = trim(preg_replace("/\s+/", " ", $val));

            if (!$firstRow){

                if ($curCol == $stdColumn) $stdVal = $val;
                elseif ($curCol == $clsColumn) $clsVal = $val;
                elseif ($curCol == $teacherColumn) $teaVal = $val;
                elseif ($curCol == $lvlColumn) $lvlVal = $val;

            }

        }
        elseif (strtolower($xml->name) == 'td'){
            // beginning of cell
            $tdOpen = true;
            // reset value
            $val = '';
            // set column position
            $curCol++;
        }
        elseif ($tdOpen){
            // file originally encoded in utf8.
            // convert to latin1
            $val .= utf8_decode($xml->value);
        }

    }

    // insert last batch of students
    if ($countFlag){
        $db->query($q);
    }

    $xml->close();

    // add counter values to array
    $counters = array();

    $counters['countStd'] = $countStd;
    $counters['countTch'] = $countTch;
    $counters['countCls'] = $countCls;
    $counters['countSkpLine'] = $countSkpLine;
    $counters['countIgnTch'] = count($ignoreTchList);
    $counters['countLvl'] = $countLvl;
    $counters['countIgnLvl'] = count($ignoreLvlList);
    $counters['countIgnStd'] = $countIgnStd;

    // delete file
    unlink(UPLOAD_DIR . $fileName);

    return true;

}

//--------------------------------------

function getClassId(&$clsArr, $clsName, $tid){
    
    // $clsArr[clsId] = array($clsName, $tid)
    // no need to validate $clsArr since its creating
    // and implementation occurs withing code
    
    foreach ($clsArr as $clsId => $cls) {
        if ($cls[0] == $clsName && $cls[1] == $tid){
            // class found
            return $clsId;
        }
    }
    
    // class not found
    return false;
    
}

//--------------------------------------

function getLevelId($lvlName, &$levels){
    
    // case insensitive search
    foreach ($levels as $index => $li){
        if (strtolower($li['Name']) == strtolower($lvlName)){
            return $index;
        }
    }
    
    // level not found
    return false;
    
}

//--------------------------------------

function getTeacherId($tName, &$teachers){
    
    foreach ($teachers as $key => $value) {
        
        // case insensitive search
        if (strtolower($value) == strtolower($tName)){
            return $key;
        }
        
    }
    
    // teacher not found
    return false;
    
}
    
?>