<?php

function validateStep3Input(&$errArr, &$actTeachers, &$programs, &$levels, $action, $repbyid, $lvlaction, $repprg, $replvl){
    
    // set 2 level array to hold errors
    // $errArr[errorType][] = $errIndex
    $errArr = array('action' => array(), 'lvlProg' => array(), 'lvlAct' => array());
    
    // validate actions
    if (is_array($action)){
        
        foreach ($action as $errIndex => $value) {
            // check if action is 3 (substituir professor por) and
            // the replacement ID is valid (is numeric and is it's in the active teacher array).
            if ($value == 3 && (!isNum($repbyid[$errIndex]) || !isset($actTeachers[$repbyid[$errIndex]]))){
                // add error type and index
                $errArr['action'][] = $errIndex;
            }
            
        }
        
    }
    
    // validate level actions
    if (is_array($lvlaction)){
        
        foreach ($lvlaction as $errIndex => $value){
            
            // check if action value is 1 and if program is valid
            if ($value == 1 && (!isNum($repprg[$errIndex]) || !isset($programs[$repprg[$errIndex]]))){
                // add error index to array
                $errArr['lvlProg'][] = $errIndex;
            }
            // check if action value is 3 and if level to replace is valid
            elseif ($value == 3 && (!isNum($replvl[$errIndex]) || !isset($levels[$replvl[$errIndex]]))){
                $errArr['lvlAct'][] = $errIndex;
            }
            
        }
        
    }
    
    // if array contains no errors, return true
    return !(count($errArr['action']) + count($errArr['lvlProg']) + count($errArr['lvlProg']));
    
}

?>