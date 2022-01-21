<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/date_functions.php';
require_once '../genreq/genreq.php';
require_once 'require/calculateHoursDue.php';
require_once 'require/getCreditDiscounts.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);
$type = getGet('t');

if (!$loginObj->isLoggedIn()){
    $db->close();
    die('<span style="font-style: italic; color: red;">Você precisa fazer o login.</style>');
}
elseif (!$loginObj->isAdmin()){
    $db->close();
    die('<span style="font-style: italic; color: red;">Acesso restrito.</style>');
}
elseif (!isNum($type)){
    $db->close();
    die('<span style="font-style: italic; color: red;">Parametros inválidos (0).</style>');
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Imprimir Relatório</title>
    
    <style type="text/css">
        
        div.main {
            width: 800px;
            margin-left: auto; 
            margin-right:auto;
            margin-top: 0px;
            position: relative;
            font-family: calibri;
        }
        
        table.headder td {
            padding: 0;
        }
        
        table.main {
            width: 100%;
            border-collapse: collapse;
        }
        
        table.main td {
            padding: 5px;
            border-top: #404040 solid 1px;
            border-bottom: #404040 solid 1px;
        }
        
    </style>
    
    <style type="text/css" media="print">
        
        .noprint {
            display: none;
        }
               
    </style>
    
    <script type="text/javascript">
        
    </script>
    
</head>
<body>

    <div class="main">
        
        <img src="../images/logo1.jpg" style="vertical-align: middle; width: 250px; height: 70px;"/>
        
<?php

if ($type == 1){
    displayGeneralReport(getGet('tbid'));
}
elseif ($type == 2){
    displayReportByTeacher(getGet('tbid'), getGet('tid'));
}
else {
    echo '<br/><br/><span style="font-style: italic; color: red;">Parametros inválidos (1).</style>';
}

?>
        
    </div>
    
</body>
</html>
<?php

$db->close();

// ----------------------------------------------------

function displayReportByTeacher($tbid, $tid){
    
    global $db;
    
    if ($tbinfo = $db->query("SELECT `Year`, StartDate, EndDate FROM tb_banks WHERE ID = $tbid")->fetch_assoc()){
    
        // convert content into variables and values
        $tbYear = $tbinfo['Year'];
        $tbStartDate = $tbinfo['StartDate'];
        $tbEndDate = $tbinfo['EndDate'];
        
        $tName = $db->query("SELECT Name FROM users WHERE ID = $tid")->fetch_row()[0];
        
    }
    
    if (isset($tName)){
        
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%; padding-bottom: 5px;" colspan="2">
                    <span style="font-weight: bold; font-size: 21px;">Relatório Por Professor</span>
                    <br/>
                    Banco de Horas: <?php echo '<span style="font-weight: bold;">' . $tbYear . '</span> <span style="font-style: italic;">(' . formatLiteralDate($tbStartDate) . ' a ' . formatLiteralDate($tbEndDate) . ')</span>'; ?><br/>
                    Professor: <span style="font-weight: bold;"><?php echo htmlentities($tName, 0, 'ISO-8859-1'); ?></span>
                </td>
                <td class="noprint" style="padding-bottom: 5px; vertical-align: bottom;"><img src="../images/printer.png" style="vertical-align: middle; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>    
<?php

        // fetch credits/discounts
        $crDsCol = getCreditDiscounts($tid, $tbid, $tbYear, $tbStartDate, $tbEndDate);
        
        // separate class credits and the other credits/discounts
        // note: class credits are the ones withou a data
        $classCredits = array();
        $crdsArr = array();
        
        foreach ($crDsCol as $cd){
            
            if (isset($cd['Date'])){
                $crdsArr[] = $cd;
            }
            else {
                $classCredits[] = $cd;
            }
            
        }
        
        // sort credit/discount array
        usort($crdsArr, "mySort");

        $totalCredit = 0;
        $totalDesc = 0;
        $tblSeparatorFlag = false;

        // display class table
        if (count($classCredits)){

            $tblSeparatorFlag = true;
            
?>
        <table class="main">
            <tr style="background-color: #d1d1d1; font-weight: bold; page-break-inside: avoid;">
                <td style="width: 100%; border-left: #404040 solid 1px; border-right: #404040 solid 1px;" colspan="3">Crédito Excedente Por Turma</td>
            </tr>
            <tr style="background-color: #d1d1d1; font-weight: bold; page-break-inside: avoid;">
                <td style="width: 70%; border-left: #404040 solid 1px;">Professor</td>
                <td style="width: 15%; text-align: center; vertical-align: bottom;">Desconto</td>
                <td style="width: 15%; border-right: #404040 solid 1px; text-align: center; vertical-align: bottom;">Crédito</td>
            </tr>
<?php
            foreach ($classCredits as $clCr){

                echo '<tr style="page-break-inside: avoid;"><td style="border-left: #404040 solid 1px;">' . $clCr['Description'] . '</td><td style="text-align: center;">&nbsp;</td><td style="text-align: center; border-right: #404040 solid 1px;">' . numToTime($clCr['Value']) . '</td></tr>' . PHP_EOL;

                $totalCredit += $clCr['Value'];

            }
?>
        </table>
<?php
        }
        
        if (count($crdsArr)){

            $months = array(1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 
                7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro');
            
            $curYear = null;

            // display other credits/discouns by date
            foreach ($crdsArr as $crds){

                $datePieces = explode("-", $crds['Date']);

                // if this credit/discount is from a different month,
                // create new table
                if ($datePieces[0] != $curYear || $datePieces[1] != $curMon){

                    if (isset($curYear)) echo '</table>' . PHP_EOL; // a table was previous created and needs to be closed
                    if ($tblSeparatorFlag) echo '<br/>' . PHP_EOL;

                    $tblSeparatorFlag = true;

                    $curYear = $datePieces[0];
                    $curMon = $datePieces[1];

                    echo '        <table class="main">
                <tr style="background-color: #d1d1d1; font-weight: bold; page-break-inside: avoid;">
                    <td style="width: 100%; border-left: #404040 solid 1px; border-right: #404040 solid 1px;" colspan="4">' . $months[+$datePieces[1]] . '/' . $datePieces[0] . '</td>
                </tr>
                <tr style="background-color: #d1d1d1; font-weight: bold; page-break-inside: avoid;">
                    <td style="width: 10%; text-align: center; border-left: #404040 solid 1px;">Dia</td>
                    <td style="width: 60%;">Descrição</td>
                    <td style="width: 15%; text-align: center; white-space: nowrap;">Desconto</td>
                    <td style="width: 15%; text-align: center; white-space: nowrap; border-right: #404040 solid 1px;">Crédito</td>
                </tr>';

                }

                echo '            <tr>
                    <td style="text-align: center; border-left: #404040 solid 1px;">' . $datePieces[2] . '</td>
                    <td>' . htmlentities($crds['Description'], 0, 'ISO-8859-1') . '</td>
                    <td style="text-align: center;">' . (!$crds['isCredit'] ? numToTime($crds['Value']) : '&nbsp;') . '</td>
                    <td style="text-align: center; border-right: #404040 solid 1px;">' . ($crds['isCredit'] ? numToTime($crds['Value']) : '&nbsp;') . '</td>
                </tr>';

                if ($crds['isCredit']){
                    $totalCredit += $crds['Value'];
                }
                else {
                    $totalDesc += $crds['Value'];
                }

            }

            echo '</table>' . PHP_EOL; // close the last table

        }

        $totalDue = $totalCredit - $totalDesc;
        
?>
            <br/>
            <table class="main" style="width: 250px; page-break-inside: avoid;">
                <tr>
                    <td style="white-space: nowrap; width: 20%; text-align: right; border-left: #404040 solid 1px;">Créditos:</td>
                    <td style="white-space: nowrap; width: 15%; text-align: center; border-right: #404040 solid 1px;">&nbsp;<?php echo numToTime($totalCredit); ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; border-left: #404040 solid 1px;">Descontos:</td>
                    <td style="white-space: nowrap; text-align: center; border-right: #404040 solid 1px;">&nbsp;<?php echo numToTime($totalDesc); ?></td>
                </tr>
                <tr style="background-color: #d1d1d1; font-weight: bold;">
                    <td style="white-space: nowrap; text-align: right; border-left: #404040 solid 1px;">Horas Devidas:</td>
                    <td style="white-space: nowrap; text-align: center; border-right: #404040 solid 1px;"><?php echo ($totalDue < 0 ? '-' : '') . numToTime(abs($totalDue)); ?></td>
                </tr>
            </table>
            <div style="font-style: italic; font-size: 13px;">Relatório gerado em <?php echo date('d/m/Y H:i:s'); ?></div>
        
            <p class="noprint">&nbsp;</p>
<?php
        
    }
    else {
        echo '<br/><br/><span style="font-style: italic; color: red;">Parametros inválidos (3).</style>';
    }
    
}

// ----------------------------------------------------

function displayGeneralReport($tbid){
    
    global $db;
    
    // fetch time bank info
    if ($tbinfo = $db->query("SELECT `Year`, StartDate, EndDate FROM tb_banks WHERE ID = $tbid")->fetch_assoc()){
    
        $tbYear = $tbinfo['Year'];
        $tbStartDate = $tbinfo['StartDate'];
        $tbEndDate = $tbinfo['EndDate'];
        
        // fetch teachers
        $teachers = array();

        $result = $db->query("SELECT ID, Name FROM users WHERE Status <= 1 AND Blocked = 0 ORDER BY Name");

        while ($row = $result->fetch_assoc()){
            $teachers[] = $row;
        }

        $result->close();
        
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%; padding-bottom: 5px;">
                    <span style="font-weight: bold; font-size: 21px;">Relatório Geral</span>
                    <br/>
                    Banco de Horas: <?php echo '<span style="font-weight: bold;">' . $tbYear . '</span> <span style="font-style: italic;">(' . formatLiteralDate($tbStartDate) . ' a ' . formatLiteralDate($tbEndDate) . ')'; ?>
                </td>
                <td class="noprint" style="padding-bottom: 5px;"><img src="../images/printer.png" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>    
<?php
        if (count($teachers)){
?>
        <table class="main">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 80%; border-left: #404040 solid 1px;">Professor</td>
                <td style="width: 20%; border-right: #404040 solid 1px; text-align: center;">Horas Devidas</td>
            </tr>
<?php

            foreach ($teachers as $tInfo) {
                
                $hoursDue = calculateHoursDue($db, $tInfo['ID'], $tbid, $tbStartDate, $tbEndDate);
                
                // check if total is negative
                if ($hoursDue < 0){
                    $hoursDisplay = '<span style="font-weight: bold;">-' . numToTime($hoursDue * (-1)) . '</span>';
                }
                else {
                    $hoursDisplay = numToTime($hoursDue);
                }
                
?>
            <tr style="page-break-inside: avoid;">
                <td style="border-left: #404040 solid 1px;"><?php echo htmlentities($tInfo['Name'], 0, 'ISO-8859-1'); ?></td>
                <td style="text-align: center; border-right: #404040 solid 1px;"><?php echo $hoursDisplay; ?></td>
            </tr>            
<?php } ?>
        </table>
        <div style="font-style: italic; font-size: 13px;">Relatório gerado em <?php echo date('d/m/Y H:i:s'); ?></div>
        
        <p class="noprint">&nbsp;</p>
<?php
        }
        else {
            echo '<br/><span style="font-style: italic; color: red;">Não há professores disponíveis no banco de dados.</style>';
        }
        
    }
    else {
        echo '<br/><br/><span style="font-style: italic; color: red;">Parametros inválidos (2).</style>';
    }
    
}

//----------------------------------------------------

// user-defined comparison function for usort()
function mySort($a, $b){
    
    $ta = strtotime($a['Date'] . ' 00:00:00');
    $tb = strtotime($b['Date'] . ' 00:00:00');
    
    return ($ta > $tb ? 1 : ($ta < $tb ? -1 : 0));
    
}

?>