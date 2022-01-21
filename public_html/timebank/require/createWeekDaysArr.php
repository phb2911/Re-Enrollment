<?php

function createWeekDaysArr($days){
    
    // this procedure generates an array containing 7 values (indexes 0 - 6)
    // each one will be set to true if it correspond to one of the class days
    // or false otherwise.
    $weekDays = array();
    
    $daysFlag = 64;
    
    for ($i = 0; $i <= 6; $i++){
        
        if ($days >= $daysFlag){
            $weekDays[$i] = true;
            $days -= $daysFlag;
        }
        else {
            $weekDays[$i] = false;
        }
        
        $daysFlag /= 2;
        
    }
    
    return $weekDays;
    
}

?>