<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/date_functions.php';
require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';
require_once 'require/calculateHoursDue.php'; // this module contains calculateHoursDue() and createWeekDaysArr() methods

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()){
    $db->close();
    header("Location: " . LOGIN_PAGE);
    die();
}

$isAdmin = $loginObj->isAdmin();

$tbid = getGet('tbid');
$uid = getGet('uid');
$msg = null;
$isValidUser = false;

if (isNum($tbid) && $tbinfo = $db->query("SELECT `Year`, StartDate, EndDate FROM tb_banks WHERE ID = $tbid")->fetch_assoc()){
    
    $tbYear = $tbinfo['Year'];
    
    $tbStartDate = $tbinfo['StartDate'];
    $tbEndDate = $tbinfo['EndDate'];
        
}
elseif ($db->query("SELECT COUNT(*) FROM tb_banks WHERE Active = 1")->fetch_row()[0] == 1){
    
    // there's only one active bank
    $tbinfo = $db->query("SELECT * FROM tb_banks WHERE Active = 1")->fetch_assoc();
    
    $tbid = $tbinfo['ID'];
    $tbYear = $tbinfo['Year'];
    
    $tbStartDate = $tbinfo['StartDate'];
    $tbEndDate = $tbinfo['EndDate'];
    
}
else {
    
    $db->close();
    header("Location: bankslist.php");
    die();
    
}

if ($isAdmin && isset($uid)){
    
    if (isNum($uid) && $userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]){
        $isValidUser = true;
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">ID do usuário não é válida.</span>';
    }
    
}
elseif (!$isAdmin){
    $uid = $loginObj->userId;
    $userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0];
    $isValidUser = true;
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="../js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27))
                hideHelpBox();

        };
        
        function hideHelpBox(){
            
            var helpBoxes = document.getElementsByClassName('helpBox');
            
            for (var i = 0; i < helpBoxes.length; i++){
                helpBoxes[i].style.visibility = 'hidden';
            }
            
            element('overlay').style.visibility = 'hidden';
            
        }
        
        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
        }
        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="../images/banner3<?php echo ($isAdmin ? 'admin' : ''); ?>.jpg"/></a>
        
<?php

renderDropDown($db, $isAdmin);

?>
        <div id="msgBox" style="width: 700px; left: 0; right: 0; margin: auto; display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <br/>
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
        </div>
<?php

if ($isValidUser){
    showDetaildReport($uid, $userName, $isAdmin, $tbid, $tbYear, $tbStartDate, $tbEndDate);
}
else {
    showGeneralReport($tbid, $tbYear, $tbStartDate, $tbEndDate);
}

?>

    </div>
    
    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay" onclick="hideHelpBox();"></div>
    <div class="helpBox" id="helpBox" style="width: 500px; height: 270px;">
        <div class="closeImg" onclick="hideHelpBox();"></div>
        <span style="font-weight: bold;">Ajuda - Banco de Horas</span>
        <hr/>
        <div style="padding: 5px;">
            Nesta página é possível verificar os descontos e créditos aplicados
            ao Banco de Horas do colaborador.<br/><br/>
            <span style="font-weight: bold;">Descontos</span> são as horas pagas 
            pelo colaborador através de aulas de reposição, Talk Sessions,
            reuniões pedagógicas, etc.<br/><br/>
            <span style="font-weight: bold;">Créditos</span> são as horas devidas 
            pelo colaborador à instituição. Os créditos são relativos à folgas
            não oficiais, aulas pagas não ministradas, etc.<br/><br/>
            <span style="color: red; font-weight: bold;">Atenção:</span> Caso as
            horas do campo <span style="font-style: italic">'Horas Devidas'</span>
            estejam em vermelho, o colaborador deve horas à instituição, caso estejam
            em azul, a instituição deve horas ao colaborador.
        </div>
    </div>
    
</body>
</html>
<?php

$db->close();

//------------------------------------------------

function showDetaildReport($uid, $userName, $isAdmin, $tbid, $tbYear, $tbStartDate, $tbEndDate){
    
    global $db;
    
?>
        <br/>
        <div class="panel" style="width: 900px; left: 0; right: 0; margin: auto;">
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 0; width: 100%;">
                        <span style="font-weight: bold;">Banco de Horas</span>
                        <img src="../images/question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/>
                    </td>
                    <td style="padding: 0 3px 0 0;">
                        <?php
                        
                        if ($isAdmin){
                            echo '<img src="../images/doc.png" style="vertical-align: middle; cursor: pointer;" title="Relatório"'
                            . ' onclick="window.location = \'report.php?tbid=' . $tbid . '&tid=' . $uid . '&t=2\';"/>';
                        }
                        
                        ?>
                    </td>
                </tr>
            </table>
            
            <hr/>
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Banco de Horas:</td>
                    <td style="width: 100%;">
                    <?php
                        echo '<span style="font-weight: bold;">' . $tbYear . '</span> <span style="font-style: italic;">(' . formatLiteralDate($tbStartDate) . ' a ' . formatLiteralDate($tbEndDate) . ')</span>';
                    ?>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?></td>
                </tr>
            </table>
            
<?php

    $totalCredit = 0;
    $totalDesc = 0;

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
    $result = $db->query("SELECT ID, Name, Days, Duration, ExcessMinutes, StartDate, EndDate, StartClass, EndClass, Semester FROM tb_classes WHERE Bank = $tbid AND User = $uid ORDER BY Semester, Name");

    $classes = array();
    
    while ($row = $result->fetch_assoc()){
        $classes[] = $row;
    }
    
    $result->close();
    
    $bgcolor = null;
    
    foreach ($classes as $cls){

        $clsCredit = 0;
        $numOfClasses = 0;

        $excMin = intval($cls['ExcessMinutes'], 10);
        $totalTime = intval($cls['Duration'], 10) + $excMin; // time in class + excess minutes
        
        $semester = $tbYear . '.' . $cls['Semester'];

        // days of the week
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
            // add january credit (2.5 * 4.5 = 11.25 hours = 685 min)
            $clsCredit += 685;
        }
        
        // create period obj and loop through each day
        // note: if paid period includes january, last day of period is december 31st of the previous year
        $period = new DatePeriod($clsPaidStart, new DateInterval('P1D'), ($inclJan ? $endPeriod : $clsPaidEnd));
        
        // array to store credits and descounts
        $crdsArr = array();

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
                            'Description' => 'Dia não trabalhado',
                            'Date' => $date->format('d/m/Y'),
                            'isCredit' => true,
                            'Value' => $totalTime
                            );
                        
                    }
                    // check if date is day off
                    // if so, add duration + excess
                    elseif (isset($daysOff[$numCurDate])){
                        
                        $crdsArr[] = array(
                            'Description' => $daysOff[$numCurDate],
                            'Date' => $date->format('d/m/Y'),
                            'isCredit' => true,
                            'Value' => $totalTime
                            );
                        
                    }
                    else {
                        // regular class day
                        $numOfClasses++;
                        // add excess minutes only
                        $clsCredit += $excMin;
                    }

                }

            }

        }
        
        // calculate excess minutes        
        $excTotal = $excMin * $numOfClasses;
        
        // fetch other credits and discounts related to current class
        $result = $db->query("SELECT Description, `Date` , Duration, 1 AS Credit FROM tb_credits WHERE Bank = $tbid AND User = $uid AND Class = " . $cls['ID'] . " UNION ALL SELECT Description, `Date` , Duration, 0 AS Credit FROM tb_discounts WHERE Bank = $tbid AND User = $uid AND Class = " . $cls['ID'] . " ORDER BY `Date`");
        
        while ($row = $result->fetch_assoc()){
            
            $crdsArr[] = array(
                'Description' => $row['Description'],
                'Date' => formatDate($row['Date']),
                'isCredit' => !!$row['Credit'],
                'Value' => $row['Duration']
                );
            
        }
        
        $result->close();
        
        // class header
        echo '<table style="width: 100%;"><tr style="background-color: #1b9a87; color: white;"><td colspan="4">' . htmlentities($cls['Name'], 0, 'ISO-8859-1') . ' - ' . $semester . ' <span style="font-style: italic;">(' . formatLiteralDate($cls['StartClass']) . ' a ' . formatLiteralDate($cls['EndClass']) . ')</span></td></tr>' . PHP_EOL . 
                '<tr style="background-color: #85c4bb;"><td style="width: 15%; text-align: center;">Data</td><td style="width: 55%;">Descrição</td><td style="width: 15%; text-align: center; white-space: nowrap;">Desconto</td><td style="width: 15%; text-align: center; white-space: nowrap;">Crédito</td></tr>' . PHP_EOL;
        
        // display excess minutes
        $bgcolor = ($bgcolor == '#c1c1c1' ? '#e6e6e6' : '#c1c1c1');
        
        echo '<tr style="background-color: ' . $bgcolor . ';"><td style="text-align: center;">-</td><td>Crédito por aula (' . numToTime($excMin) . ') X numero de aulas (' . $numOfClasses . ')</td><td style="text-align: center; color: blue;">&nbsp;</td><td style="text-align: center; color: red;">' . numToTime($excTotal) . '</td></tr>' . PHP_EOL;
        
        // sort array
        usort($crdsArr, "mySort");
        
        $subDesc = 0;
        
        // display credits/discounts
        foreach ($crdsArr as $cr){
            
            if ($cr['isCredit']){
                $clsCredit += $cr['Value'];
            }
            else {
                $totalDesc += $cr['Value'];
                $subDesc += $cr['Value'];
            }
            
            $bgcolor = ($bgcolor == '#c1c1c1' ? '#e6e6e6' : '#c1c1c1');
            
            echo '<tr style="background-color: ' . $bgcolor . ';"><td style="text-align: center;">' . $cr['Date'] . '</td><td>' . htmlentities($cr['Description'], 0, 'ISO-8859-1') . '</td><td style="text-align: center; color: blue;">' . (!$cr['isCredit'] ? numToTime($cr['Value']) : '&nbsp;') . '</td><td style="text-align: center; color: red;">' . ($cr['isCredit'] ? numToTime($cr['Value']) : '&nbsp;') . '</td></tr>' . PHP_EOL;
            
        }
        
        // display january credit
        if ($inclJan){
            $bgcolor = ($bgcolor == '#c1c1c1' ? '#e6e6e6' : '#c1c1c1');
            echo '<tr style="background-color: ' . $bgcolor . ';"><td style="text-align: center;">-</td><td>Crédito por aulas não ministradas no mês de janeiro (2:30 x 4,5)</td><td style="text-align: center; color: blue;">&nbsp;</td><td style="text-align: center; color: red;">11:25</td></tr>' . PHP_EOL;
        }
        
        echo '<tr><td colspan="2" style="text-Align: right;">Sub-Total:</td><td style="background-color: #85c4bb; text-align: center; color: blue;">' . numToTime($subDesc) . '</td><td style="background-color: #85c4bb; text-align: center; color: red;">' . numToTime($clsCredit) . '</td></tr>' . PHP_EOL;
        echo '</table><br/>' . PHP_EOL;
        
        $bgcolor = null;
        
        $totalCredit += $clsCredit;

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
    
    $q = "SELECT Description, `Date` , Duration, 1 AS Credit FROM tb_credits WHERE Bank = $tbid AND User = $uid $sq UNION ALL SELECT Description, `Date` , Duration, 0 AS Credit FROM tb_discounts WHERE Bank = $tbid AND User = $uid $sq ORDER BY `Date`";
    
    $result = $db->query($q);

    if ($result->num_rows){
        
        echo '<table style="width: 100%;"><tr style="background-color: #1b9a87; color: white;"><td colspan="4">Outros Créditos/Descontos</td></tr>' . PHP_EOL . 
                '<tr style="background-color: #85c4bb;"><td style="width: 15%; text-align: center;">Data</td><td style="width: 55%;">Descrição</td><td style="width: 15%; text-align: center; white-space: nowrap;">Desconto</td><td style="width: 15%; text-align: center; white-space: nowrap;">Crédito</td></tr>' . PHP_EOL;
    
        $subDesc = 0;
        $subCred = 0;
        
        while ($row = $result->fetch_assoc()){

            $bgcolor = ($bgcolor == '#c1c1c1') ? '#e6e6e6' : '#c1c1c1';
            
            $isCredit = !!$row['Credit'];
            
            if ($isCredit){
                $totalCredit += intval($row['Duration'], 10);
                $subCred += intval($row['Duration'], 10);
            }
            else {
                $totalDesc += intval($row['Duration'], 10);
                $subDesc += intval($row['Duration'], 10);
            }
            
            echo '<tr style="background-color: ' . $bgcolor . ';">'
                    . '<td style="text-align: center;">' . formatDate($row['Date']) . '</td>'
                    . '<td>' . htmlentities($row['Description'], 0, 'ISO-8859-1') . '</td>'
                    . '<td style="text-align: center; color: blue;">' . (!$isCredit ? numToTime($row['Duration']) : '&nbsp;') . '</td>'
                    . '<td style="text-align: center; color: red;">' . ($isCredit ? numToTime($row['Duration']) : '&nbsp;') . '</td>'
                    . '</tr>' . PHP_EOL;

        }
        
        echo '<tr><td colspan="2" style="text-Align: right;">Sub-Total:</td><td style="background-color: #85c4bb; text-align: center; color: blue;">' . numToTime($subDesc) . '</td><td style="background-color: #85c4bb; text-align: center; color: red;">' . numToTime($subCred) . '</td></tr>' . PHP_EOL;
        echo '</table><br/>' . PHP_EOL;
    
    }

    $result->close();
    
    $totalDue = $totalCredit - $totalDesc;

?>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 65%;">&nbsp;</td>
                    <td style="white-space: nowrap; background-color: #c1c1c1; width: 20%; text-align: right;">Créditos:</td>
                    <td style="white-space: nowrap; background-color: #c1c1c1; width: 15%; text-align: center; color: red;">&nbsp;<?php echo numToTime($totalCredit); ?></td>
                </tr>
                <tr>
                    <td style="width: 65%;">&nbsp;</td>
                    <td style="white-space: nowrap; background-color: #e6e6e6; width: 20%; text-align: right;">Descontos:</td>
                    <td style="white-space: nowrap; background-color: #e6e6e6; width: 15%; text-align: center; color: blue;">&nbsp;<?php echo numToTime($totalDesc); ?></td>
                </tr>
                <tr>
                    <td style="width: 65%;">&nbsp;</td>
                    <td style="white-space: nowrap; background-color: #c1c1c1; width: 20%; text-align: right;">Horas Devidas:</td>
                    <td style="white-space: nowrap; background-color: #c1c1c1; width: 15%; text-align: center;<?php echo ($totalDue < 0 ? ' color: blue;' : ($totalDue > 0 ? ' color: red;' : 'blue')); ?>"><?php echo ($totalDue < 0 ? '-' : '') . numToTime(abs($totalDue)); ?></td>
                </tr>
            </table>

        </div>
<?php
    
}

//------------------------------------------------

function showGeneralReport($tbid, $tbYear, $tbStartDate, $tbEndDate){
 
    global $db;
    
    // fetch teachers
    $teachers = array();
    
    $result = $db->query("SELECT ID, Name FROM users WHERE Status <= 1 AND Blocked = 0 ORDER BY Name");
    
    while ($row = $result->fetch_assoc()){
        $teachers[] = $row;
    }
    
    $result->close();
    
?>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <table style="width: 100%; border-collapse: collapse;">
                <td style="padding: 0; width: 100%;">
                    <span style="font-weight: bold;">Banco de Horas - <?php echo $tbYear . '</span> <span style="font-style: italic;">(' . formatDate($tbStartDate) . ' a ' . formatDate($tbEndDate) . ')'; ?></span>
                </td>
                <td style="padding: 0 3px 0 0;">
                    <?php
                        
                         if (count($teachers)){
                             echo '<img src="../images/doc.png" style="vertical-align: middle; cursor: pointer;" title="Relatório"'
                            . ' onclick="window.location = \'report.php?t=1&tbid=' . $tbid . '\';"/>';
                         }
                        
                        ?>
                </td>
            </table>
            
            <hr/>
<?php

    if (count($teachers)){
        
        echo '<div style="font-style: italic; color: red; font-size: 13px;">* Clique no nome do professor para visualizar detalhes.</div>' . PHP_EOL .
                '<table style="width: 100%; border: black solid 1px; border-collapse: collapse;">' . PHP_EOL . '<tr style="background-color: #1b9a87; color: white">' . 
                '<td style="width: 80%;">Professor</td>' . PHP_EOL . '<td style="width: 20%; text-align: center;">Horas Devidas</td>' . PHP_EOL;
        
        $bgcolor = null;
        
        foreach ($teachers as $tInfo) {

            $bgcolor = ($bgcolor == '#e1e1e1' ? '#ffffff' : '#e1e1e1');

            $hoursDue = calculateHoursDue($db, $tInfo['ID'], $tbid, $tbStartDate, $tbEndDate);

            // check if total is negative
            if ($hoursDue < 0){
                $hoursDisplay = '<span style="color: blue; font-weight: bold;">(' . numToTime(abs($hoursDue)) . ')</span>';
            }
            elseif ($hoursDue > 0) {
                $hoursDisplay = '<span style="color: red;">' . numToTime($hoursDue) . '</span>';
            }
            else {
                $hoursDisplay = numToTime($hoursDue);
            }

            echo '<tr style="background-color: ' . $bgcolor . ';"><td><a href=".?tbid=' . $tbid . '&uid=' . $tInfo['ID'] . '">' . htmlentities($tInfo['Name'], 0, 'ISO-8859-1') . '</a></td>' . 
                    '<td style="text-align: center;">' . $hoursDisplay . '</td></tr>' . PHP_EOL;

        }
        
        echo '</table><br/>' . PHP_EOL;
        
    }
    else {
        echo '<div style="padding: 10px; color: red; font-style: italic;">Não há professores disponíveis no banco de dados.</div>';
    }

?>
        </div>
<?php
    
}

//----------------------------------------------------

// user-defined comparison function for usort()
function mySort($a, $b){
    
    // function compareDates() found in
    // data_functions.php
    return compareDates($a['Date'], $b['Date']);
    
}

?>