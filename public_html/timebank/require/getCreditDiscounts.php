<?php

require_once __DIR__ . '/createWeekDaysArr.php';

function getCreditDiscounts($tid, $tbid, $tbYear, $tbStartDate, $tbEndDate){
    
    global $db;
    
    // fetch all holidays and days off withing the TB period
    // the index of the arrey will be the date converted to unix time integer value
    $holidays = array();
    $daysOff = array();

    $result = $db->query("SELECT * FROM tb_holidays WHERE `Date` >= '$tbStartDate' AND `Date` <= '$tbEndDate'");

    while($row = $result->fetch_assoc()){
        if (!!$row['Official']){
            $holidays[strtotime($row['Date'] . ' 00:00:00')] = $row['Description'];
        }
        else {
            $daysOff[strtotime($row['Date'] . ' 00:00:00')] = $row['Description'];
        }
    }

    $result->close();
    
    // fetch classes and store in array
    $result = $db->query("SELECT ID, Name, Days, Duration, ExcessMinutes, StartDate, EndDate, StartClass, EndClass, Semester FROM tb_classes WHERE Bank = $tbid AND User = $tid ORDER BY Semester, Name");

    $classes = array();
    
    while ($row = $result->fetch_assoc()){
        $classes[] = $row;
    }
    
    $result->close();
    
    // array to store credits and descounts
    $crdsArr = array();
    
    // calculate the credit for each class
    foreach ($classes as $cls){

        $classFullName = $cls['Name'] . ' (' . $tbYear . '.' . $cls['Semester'] . ')';
        
        $numOfClasses = 0;

        $excMin = intval($cls['ExcessMinutes'], 10);
        $totalTime = intval($cls['Duration'], 10) + $excMin; // time in class + excess minutes
        
        $weekDays = createWeekDaysArr(intval($cls['Days'], 10));

        // create DateTime objs
        $clsPaidStart = new DateTime($cls['StartDate']);
        $clsPaidEnd = new DateTime($cls['EndDate'] . ' 12:00:00'); // add time so the end date is included
        $clsStart = new DateTime($cls['StartClass']);
        $clsEnd = new DateTime($cls['EndClass']);
        
        // check if the end of the paid date includes the month of january
        $inclJan = false;
        $endPeriod = null;
        
        if ($clsPaidEnd->format('n') == 1){
            $inclJan = true;
            // december 31st of previous year (year - 1)
            $endPeriod = new DateTime((intval($clsPaidEnd->format('Y'), 10) - 1) . '-12-31 12:00:00');
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

                    // check if date is paid but not a class day
                    // if so, add duration + excess
                    if ($date < $clsStart || $date > $clsEnd){
                        
                        $crdsArr[] = array(
                            'Description' => $classFullName . ' - Dia não trabalhado',
                            'Date' => $date->format('Y-m-d'),
                            'isCredit' => true,
                            'Value' => $totalTime
                            );
                        
                    }
                    // check if date is day off
                    // if so, add duration + excess
                    elseif (isset($daysOff[$numCurDate])){
                        
                        $crdsArr[] = array(
                            'Description' => $classFullName . ' - ' . $daysOff[$numCurDate],
                            'Date' => $date->format('Y-m-d'),
                            'isCredit' => true,
                            'Value' => $totalTime
                            );
                        
                    }
                    else {
                        // regular class day
                        $numOfClasses++;
                    }

                }

            }

        }
        
        // calculate excess minutes and store into array 
        $crdsArr[] = array(
            'Description' => $classFullName . ' - Crédito por aula (' . numToTime($excMin) . ') X numero de aulas (' . $numOfClasses . ')',
            'Date' => null,
            'isCredit' => true,
            'Value' => ($excMin * $numOfClasses)
            );
        
        // add january credit
        if ($inclJan){
            $crdsArr[] = array(
                'Description' => $classFullName . ' - Crédito por aulas não ministradas no mês de janeiro',
                'Date' => null,
                'isCredit' => true,
                'Value' => 685
                );
        }
        
        // fetch other credits and discounts related to current class
        $result = $db->query("SELECT Description, `Date` , Duration, 1 AS Credit FROM tb_credits WHERE Bank = $tbid AND User = $tid AND Class = " . $cls['ID'] . " UNION ALL SELECT Description, `Date` , Duration, 0 AS Credit FROM tb_discounts WHERE Bank = $tbid AND User = $tid AND Class = " . $cls['ID'] . " ORDER BY `Date`");
        
        while ($row = $result->fetch_assoc()){
            
            $crdsArr[] = array(
                'Description' => $classFullName . ' - ' . $row['Description'],
                'Date' => $row['Date'],
                'isCredit' => !!$row['Credit'],
                'Value' => +$row['Duration']
                );
            
        }
        
        $result->close();

    }
    
    // fetch other credits and discounts
    
    // exclude credits/discounts previously fetched
    // Note: if no data from previous class extracted, no need for sub-query
    // or otherwise only rows with null classes would be fetched (not what
    // is expected).
    $sq = null;
    
    if (count($classes)){
    
        $sq = 'AND (Class IS NULL';
        $flag = false;

        foreach ($classes as $cls){

            if ($flag) $sq .= " AND ";
            else $sq .= " OR (";

            $sq .= "Class != " . $cls['ID'];

            $flag = true;

        }

        $sq .= "))";
    
    }
    
    $q = "SELECT Description, `Date` , Duration, 1 AS Credit FROM tb_credits WHERE Bank = $tbid AND User = $tid $sq UNION ALL SELECT Description, `Date` , Duration, 0 AS Credit FROM tb_discounts WHERE Bank = $tbid AND User = $tid $sq ORDER BY `Date`";
    
    $result = $db->query($q);

    while ($row = $result->fetch_assoc()){
        
        $crdsArr[] = array(
            'Description' => $row['Description'],
            'Date' => $row['Date'],
            'isCredit' => !!$row['Credit'],
            'Value' => +$row['Duration']
            );
    
    }
    
    $result->close();
    
    return $crdsArr;
    
}

?>