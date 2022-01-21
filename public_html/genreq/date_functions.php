<?php

function isValidDate($dateStr){
    
    // check format
    // valid format: d/m/yyyy or dd/mm/yyyy
    if (!preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateStr)) return false;
        
    $pieces = explode('/', $dateStr);
    $day = intval($pieces[0], 10);
    $month = intval($pieces[1], 10);
    $year = intval($pieces[2], 10);

    // Check the ranges of month and year
    if($year < 1970 || $year > 2099 || $month == 0 || $month > 12) return false;

    // Adjust for leap years on $monthLength[1]
    $monthLength = array(31, ($year % 400 == 0 || ($year % 100 != 0 && $year % 4 == 0) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

    // Check the range of the day
    return $day > 0 && $day <= $monthLength[$month - 1];
    
}

//----------------------------------------

// compares dates.
// date format: d/m/yyyy or dd/mm/yyyy
// if date1 > date2 returns 1
// if date1 = date2 returns 0
// if date1 < date2 return -1
// date validation will not be made, isValidDate() must be used
// before values are submitted.
function compareDates($date1, $date2){
    
    $d1 = strtotime(parseDate($date1) . ' 00:00:00');
    $d2 = strtotime(parseDate($date2) . ' 00:00:00');
    
    return ($d1 === $d2 ? 0 : ($d1 > $d2 ? 1 : -1));
    
}

//----------------------------------------

// converts date dd/mm/yyyy to yyyy-mm-dd
function parseDate($date){
    
    // check parameter format
    if (!preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date)) return;
    
    $parts = explode('/', $date);
    return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    
}

//----------------------------------------

// convert date from yyyy-mm-dd format to dd/mm/yyyy
function formatDate($date){
    
    // check parameter format
    if (!preg_match('/^\d{4}\-\d{1,2}\-\d{1,2}$/', $date)) return;
    
    $parts = explode('-', $date);
    
    // add left zero to day and month if necessary
    return str_pad($parts[2], 2, '0', STR_PAD_LEFT) . '/' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '/' . $parts[0];
    
}

//----------------------------------------

// convert date from yyyy-mm-dd or dd/mm/yyyy to dd/MON/yyyy
// e.g.: 01/Nov/2018
function formatLiteralDate($date){
    
    if (preg_match('/^\d{4}\-\d{1,2}\-\d{1,2}$/', $date)){
        $parts = explode('-', $date);
        $d = $parts[2];
        $m = intval($parts[1], 10);
        $y = $parts[0];
    }
    elseif (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date)){
        $parts = explode('/', $date);
        $d = $parts[0];
        $m = intval($parts[1], 10);
        $y = $parts[2];
    }
    else return;
    
    $months = array(1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez');
    
    return str_pad($d, 2, '0', STR_PAD_LEFT) . '/' . $months[$m] . '/' . $y;
    
}

//----------------------------------------

// this function will check if a period between two dates (child1 and child2)
// is withing another period (parent1 and parent2).
// if the child period is within the parent period, true will be returned, false otherwise.
// E.g.: if the parent period is from jan/2017 to dec/2017 and the cild period is from
// aug/2017 to oct/2017, true will be returned. However, if the child period is aug/2017 and
// jan/2018, false will be returned.
// 
// the second child date is optional.
// 
// dates format: d/m/yyyy or dd/mm/yyyy
//
function isWithinPeriod($parent1, $parent2, $child1, $child2 = null){
    
    // check if parent1 is posterior to parent2, swap them
    if (compareDates($parent1, $parent2) == 1){
        $tmp = $parent1;
        $parent1 = $parent2;
        $parent2 = $tmp;
    }
    
    // check if child2 exists, if so, check if child1 is posterior
    // to child2, if so, swap.
    if (!isset($child2)){
        $child2 = $child1;
    }
    elseif (compareDates($child1, $child2) == 1){
        $tmp = $child1;
        $child1 = $child2;
        $child2 = $tmp;
    }
    
    // convert dates to numeric values
    $p1 = strtotime(parseDate($parent1) . ' 00:00:00');
    $p2 = strtotime(parseDate($parent2) . ' 23:59:59');
    
    $c1 = strtotime(parseDate($child1) . ' 00:00:00');
    $c2 = strtotime(parseDate($child2) . ' 23:59:59');
    
    return ($p1 <= $c1 && $p2 >= $c2);
    
}

//----------------------------------------

// this function will return the number of days
// between two dates.
// 
// php 5.3+
// 
// date validation will not be made, isValidDate() must be used
// before values are submitted.
//
// dates format: dd/mm/yyyy
//
function daysBetweenDates($date1, $date2){
    
    // if $date1 posterior to $date2, swap
    if (compareDates($date1, $date2) == 1){
        $tmp = $date1;
        $date1 = $date2;
        $date2 = $tmp;
    }
    
    // create DateTime object using yyyy-mm-dd format
    $d1 = new DateTime(parseDate($date1));
    $d2 = new DateTime(parseDate($date2));

    // return the difference in days
    // the plus sigh will convert the value returned to numeric
    return +$d2->diff($d1)->format("%a");
    
}

//---------------------------------------------------

// converts an integer value to a time formatted string (00:00)
// the integer represents the number of minuts
// E.g.: 150 will be converted to 02:30
// the parameter $mins cannot be negative
function numToTime($mins){
    return str_pad(floor($mins / 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad(($mins % 60), 2, '0', STR_PAD_LEFT);
}

//---------------------------------------------------

// converts a time fromatted string into its number of minutes
// E.g.: 01:53 will be converted to 113
// the attribute passed will not have the format validated

function timeToNum($str){
    $tmp = explode(':', $str);
    return (intval($tmp[0], 10) * 60) + intval($tmp[1], 10);
}

//---------------------------------------------------

// This function validates a time string in the format H:MM or HH:MM.
// It returns false for MM > 59 or time = 00:00

function validateShortTimeStr($str){
    
    $str = trim($str);
    
    return (preg_match('/^\d{1,2}\:\d{2}$/', $str) && !preg_match('/^0{1,2}\:0{2}$/', $str) && explode(':', $str)[1] < 60);
    
}

?>