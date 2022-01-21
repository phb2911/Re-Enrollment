<?php

require_once __DIR__ . '/createWeekDaysArr.php';

function calculateHoursDue(&$db, $uid, $tbid, $tb_start = null, $tb_end = null){
    
    $totalCredit = 0;
    $totalDesc = 0;
    
    // retrieve time bank start and end date if not supplied
    if (!isset($tb_start) || !isset($tb_end)){
        $tbInfo = $db->query("SELECT StartDate, EndDate FROM tb_banks WHERE ID = $tbid")->fetch_assoc();
        $tb_start = $tbInfo['StartDate'];
        $tb_end = $tbInfo['EndDate'];
    }
    
    // fetch all holidays and days off withing the TB period
    // the index of the arrey will be the date converted to unix time integer value
    $holidays = array();
    $daysOff = array();

    $result = $db->query("SELECT `Date`, Official FROM tb_holidays WHERE `Date` >= '$tb_start' AND `Date` <= '$tb_end'");

    while($row = $result->fetch_assoc()){
        if (!!$row['Official']){
            $holidays[strtotime($row['Date'] . ' 00:00:00')] = true;
        }
        else {
            $daysOff[strtotime($row['Date'] . ' 00:00:00')] = true;
        }
    }

    $result->close();
    
    // fetch credits and debts
    $result = $db->query("SELECT Duration, 1 AS Credit FROM tb_credits WHERE Bank = $tbid AND User = $uid UNION ALL SELECT Duration, 0 AS Credit FROM tb_discounts WHERE Bank = $tbid AND User = $uid");

    while ($row = $result->fetch_assoc()){

        if (!!$row['Credit']){
            $totalCredit += intval($row['Duration'], 10);
        }
        else {
            $totalDesc += intval($row['Duration'], 10);
        }

    }

    $result->close();
    
    // fetch classes
    $result = $db->query("SELECT Days, Duration, ExcessMinutes, StartDate, EndDate, StartClass, EndClass FROM tb_classes WHERE Bank = $tbid AND User = $uid");

    while ($row = $result->fetch_assoc()){

        $clsCredit = 0;
        
        $excMin = intval($row['ExcessMinutes'], 10);
        $totalTime = intval($row['Duration'], 10) + $excMin; // time in class + excess minutes

        // days of the week
        $weekDays = createWeekDaysArr(intval($row['Days'], 10));

        // create DateTime objs
        $clsPaidStart = new DateTime($row['StartDate']);
        $clsPaidEnd = new DateTime($row['EndDate'] . ' 12:00:00'); // add time so the end date is included
        $clsStart = new DateTime($row['StartClass']);
        $clsEnd = new DateTime($row['EndClass']);
        
        // check if the end of the paid date includes the month of january
        $inclJan = false;
        $endPeriod = null;
        
        if ($clsPaidEnd->format('n') == 1){
            $inclJan = true;
            // december 31st of previous year (year - 1)
            $endPeriod = new DateTime((intval($clsPaidEnd->format('Y'), 10) - 1) . '-12-31 12:00:00');
            // add january credit (2.5 * 4.5 = 11.25 hours = 685 min)
            $clsCredit += 685;
        }

        // create period obj and loop through each day
        $period = new DatePeriod($clsPaidStart, new DateInterval('P1D'), ($inclJan ? $endPeriod : $clsPaidEnd));

        foreach($period as $date){

            // convert current date to unix time integer value
            $numCurDate = strtotime($date->format('Y-m-d 00:00:00'));

            // check if day is a holiday, if so, skipp everything
            if (!isset($holidays[$numCurDate])){

                // check if there's class in the current week day
                if ($weekDays[intval($date->format('w'), 10)]){

                    // check if date is paid but not a class day or if it is a day off
                    // if so, add duration + excess
                    if ($date < $clsStart || $date > $clsEnd || isset($daysOff[$numCurDate])){
                        $clsCredit += $totalTime;
                    }
                    else {
                        // regular class day
                        // add excess minutes only
                        $clsCredit += $excMin;
                    }

                }

            }

        }

        // includ class debt in total debt
        $totalCredit += $clsCredit;

    }

    $result->close();
    
    // total due
    return $totalCredit - $totalDesc;
    
}

?>