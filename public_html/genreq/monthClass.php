<?php

// Implements a month object.
// monthClass(month, year)
// month = integer between 1 and 12
// year = integer between 1970 and 2099
class monthClass{
    
    private $lday;
    private $mon;
    private $yr;
    
    function __construct($month, $year){
        
        // convert parameters into integers if necessary
        $this->mon = $this->intConv($month);
        $this->yr = $this->intConv($year);
        
        // check if parameters are within range
        if ($this->mon < 1 || $this->mon > 12) throw new Exception('The month must range from 1 to 12.');
        if ($this->yr < 1970 || $this->yr > 2099) throw new Exception('The year must range from 1970 to 2099.');

        // adjust february for leap year
        $monthLength = array(31, ($this->yr % 400 == 0 || ($this->yr % 100 != 0 && $this->yr % 4 == 0) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        
        $this->lday = $monthLength[$this->mon - 1];
        
    }
    
    public function get_last_day() {
        return $this->lday;
    }
    
    public function get_month(){
        return $this->mon;
    }
    
    public function get_year(){
        return $this->yr;
    }
    
    // compares this month object to another.
    // if this month object is posterior return 1,
    // if prior return -1, if equals return 0.
    public function compare($obj){
        
        if (!$obj instanceof monthClass) throw new Exception('The object is not an instance of monthClass.');
        
        if ($this->yr > $obj->get_year() || ($this->yr == $obj->get_year() && $this->mon > $obj->get_month())){
            return 1;
        }
        elseif ($this->yr < $obj->get_year() || ($this->yr == $obj->get_year() && $this->mon < $obj->get_month())){
            return -1;
        }
        else {
            return 0;
        }
        
    }
    
    // returns the count of a certain day in the
    // month object.
    // $day = integer value between 0 and 6
    // where 0 is sunday and 6 is saturday
    public function count_of_day($day){
        
        // convert input to integer
        $d = $this->intConv($day);
        
        // validate input
        if ($d < 0 || $d > 6) throw new Exception('Parameter must be between 0 and 6.');
        
        $count = 0;
        
        // create unix timestamp of first day of month
        $curDay = mktime(0, 0, 0, $this->mon, 1, $this->yr);
        
        // loop through each day of the month
        // if next month is reached, exit loop
        while (date("n", $curDay) == $this->mon){
            
            // if current day is the one being sought after,
            // increment count
            if (date("w", $curDay) == $d){
                $count++;
            }
            
            // increment current day
            $curDay = strtotime("+1 day", $curDay);
            
        }
        
        return $count;        
        
    }
    
    // sorts array containing month objects
    // 
    // usage: monthClass::arrsort(objArr, [desc = false|true])
    // 
    // arguments: objArr - array containign month objects;
    //                  Note: the array is passed by reference, therefore
    //                  the array passed as argument gets modified.
    //            desc (optional) - sort order: 
    //                  true = descending
    //                  false = ascending (default)
    //                  
    // returns true on success, false otherwise.
    //
    static function arrsort(&$objArr, $desc = false){

        if ($desc){

            return usort($objArr, function($a, $b){
                return $b->compare($a);
            });

        }
        else {

            return usort($objArr, function($a, $b){
                return $a->compare($b);
            });

        }

    }
    
    // This private method makes sure the
    // value passed is a representation of
    // an integer value and return is't equivalent.
    // Below are some examples of values that represents
    // an integer
    // '1.00' => represents
    // '1.11' => doesn't represent (float)
    // '1e4' => represents
    // '1e40' => doesn't represent (long)
    // '-3.00000' => represents
    // '1.0.0' => doesn't represent (string)
    private function intConv($value){
        
        // check if value is an integer - no conversion needed
        if (gettype($value) != 'integer'){
            return $value;
        }
        // check if value represents an integer, then convert it.
        elseif (is_numeric($value) && +$value == (int)(+$value)){
            return intval($value, 10);
        }
        // value does not represent an integer
        else {
            throw new Exception("The value '$value' can't be converted into an integer.");
        }
        
    }
    
}

?>