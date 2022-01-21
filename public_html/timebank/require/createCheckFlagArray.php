<?php

function createCheckFlagArray($days){
    
    // create an array containing 7 boolean values
    // that represents the day's checkboxes that must
    // be checked or not
    
    $chkFlag = array();
    
    for($i = 64; $i >= 1; $i /= 2){

        if ($days >= $i){
            $chkFlag[] = true;
            $days -= $i;
        }
        else {
            $chkFlag[] = false;
        }

    }
    
    return $chkFlag;
    
}

?>