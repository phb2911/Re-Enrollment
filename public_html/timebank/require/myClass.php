<?php

class myClass{
    
    private $name;
    private $daysOfWeek;
    private $duration;
    private $excessMinutes;
    private $paidStart;
    private $paidEnd;
    private $classStart;
    private $classEnd;
    
    function __construct($info){
        
        $this->name = $info['Name'];
        $this->daysOfWeek = $this->createWeekDaysArr(intval($info['Days'], 10));
        $this->duration = intval($info['Duration'], 10);
        $this->excessMinutes = intval($info['ExcessMinutes'], 10);
        $this->paidStart = strtotime($info['StartDate']);
        $this->paidEnd = strtotime($info['EndDate'] . ' 12:00:00'); // add time so the end date is included
        $this->classStart = strtotime($info['StartClass']);
        $this->classEnd = strtotime($info['EndClass']);
        
    }
    
    // use the magic method __get() to allow access
    // to private variables without allowing them to
    // be modified
    function __get($name) {
        return $this->$name;
    }
    
    // this method receives a month and a year
    // and returns true if there's class the hole month
    // or false if there's class in part of it or no class
    // at all. No validation is performed
    public function fullMonthOfClass($month, $year){
        
        // last day of each month
        $monthLength = array(1 => 31, 2 => ($year % 400 == 0 || ($year % 100 != 0 && $year % 4 == 0) ? 29 : 28), 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31);
        
        // find the first and last day of the month
        $flagFirst = 1;
        $flagLast = $monthLength[$month];
        
        // loop until the first and last day of class is found
        do {
            
            $firstDay = strtotime($year . '-' . $month . '-' . $flagFirst);
            $fdw = date('w', $firstDay);
            
            if (!$this->daysOfWeek[$fdw]){
                $flagFirst++;
            }
            
            $lastDay = strtotime($year . '-' . $month . '-' . $flagLast);
            $ldw = date('w', $lastDay);
            
            if (!$this->daysOfWeek[$ldw]){
                $flagLast--;
            }
            
        }
        while(!$this->daysOfWeek[$fdw] || !$this->daysOfWeek[$ldw]);
        
        // first day and last day of class must be within the
        // class days
        return ($firstDay >= $this->classStart && $lastDay <= $this->classEnd);
        
    }
    
    // this method receives an integer that is the
    // unix time representation of a holiday. It returns
    // ture if this class is affected by the holiday.
    public function isAffected($holiday){
        
        // check if holiday is within class dates
        if ($holiday < $this->paidStart || $holiday > $this->paidEnd){
            return false;
        }
        
        // retrieve the day of the week of the holiday
        $dw = intval(date('w', $holiday), 10);
        
        // if there's class the day of
        // the week, return true.
        return $this->daysOfWeek[$dw];
        
    }

    // this procedure generates an array containing 7 values (indexes 0 - 6)
    // each one will be set to true if it correspond to one of the class days
    // or false otherwise.
    static public function createWeekDaysArr($days){
    
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
    
}

?>