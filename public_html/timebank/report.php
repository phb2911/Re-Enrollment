<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/date_functions.php';
require_once '../genreq/genreq.php';
require_once 'require/calculateHoursDue.php';
require_once 'require/getCreditDiscounts.php';

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

if (!$isAdmin){
    $db->close();
    header("Location: .");
    die();
}

$type = getGet('t');

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Relatório</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="../js/general.js"></script>
       
    <style type="text/css">
        
        .tbl td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            if (element('selTB')) styleSelectBox(element('selTB'));
            if (element('selTB2')) initializeSelect(element('selTB2'));
            if (element('selTeacher')) initializeSelect(element('selTeacher'));
            
        };
        
        function redir(val){
            
            if (val > 0) window.location = 'report.php?t=1&tbid=' + val;
            
        }
                
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="../images/banner3<?php echo ($isAdmin ? 'admin' : ''); ?>.jpg"/></a>
        
<?php

renderDropDown($db, $isAdmin);

$tbid = getGet('tbid');

if ($type == 1){
    
    
    
    if (isNum($tbid)){
        displayGeneralReport($tbid);
    }
    else {
        selectTB();
    }
    
}
elseif ($type == 2){
    
    $tid = getGet('tid');
    
    if (isNum($tbid) && isNum($tid)){
        displayReportByTeacher($tbid, $tid);
    }
    else {
        selectTeacher();
    }
    
}
else {
    echo '<div style="font-style: italic; color: red; padding: 10px;">Parametros inválidos.</div>';
}

?>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

//------------------------------------------

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
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Relatório Geral</span>
            <hr/>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 100%; padding-bottom: 5px;">
                        Banco de Horas: <?php echo '<span style="font-weight: bold;">' . $tbYear . '</span> <span style="font-style: italic;">(' . formatLiteralDate($tbStartDate) . ' a ' . formatLiteralDate($tbEndDate) . ')</span>'; ?>
                    </td>
                    <td>
                        <?php
                        
                         if (count($teachers)){
                             echo '<img src="../images/print.png" style="cursor: pointer; vertical-align: bottom;" title="Versão para impressão"' .
                                     ' onclick="window.open(\'printrpt.php?t=1&tbid=' . $tbid . '\', \'_blank\', \'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600\');"/>';
                         }
                        
                        ?>
                    </td>
                </tr>
            </table>
<?php

        if (count($teachers)){
            
            echo '<table class="tbl" style="width: 100%; border: black solid 1px; border-collapse: collapse;">' . PHP_EOL . '<tr style="background-color: #1b9a87; color: white">' . 
                '<td style="width: 80%;">Professor</td>' . PHP_EOL . '<td style="width: 20%; text-align: center;">Horas Devidas</td>' . PHP_EOL;
        
            $bgcolor = null;
            
            foreach ($teachers as $tInfo) {
                
                $bgcolor = ($bgcolor == '#e1e1e1') ? '#ffffff' : '#e1e1e1';
                
                $hoursDue = calculateHoursDue($db, $tInfo['ID'], $tbid, $tbStartDate, $tbEndDate);
                
                // check if total is negative
                if ($hoursDue < 0){
                    $hoursDisplay = '<span style="font-weight: bold;">-' . numToTime($hoursDue * (-1)) . '</span>';
                }
                else {
                    $hoursDisplay = numToTime($hoursDue);
                }
                
                echo '<tr style="background-color: ' . $bgcolor . ';"><td>' . htmlentities($tInfo['Name'], 0, 'ISO-8859-1') . '</td>' . 
                        '<td style="text-align: center;">' . $hoursDisplay . '</td></tr>' . PHP_EOL;
                
            }
        
            echo '</table>' . PHP_EOL . '<div style="text-align: right;"><img src="../images/print.png" style="cursor: pointer; vertical-align: middle;" title="Versão para impressão" onclick="window.open(\'printrpt.php?t=1&tbid=' . $tbid . '\', \'_blank\', \'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600\');"/></div>' . PHP_EOL;
            
        }
        else {
            echo '<div style="padding: 10px; color: red; font-style: italic;">Não há professores disponíveis no banco de dados.</div>';
        }

?>
        </div>
<?php

    }
    else {
        echo '<div style="font-style: italic; color: red; padding: 10px;">Parametros inválidos.</div>';
    }
    
}

//------------------------------------------

function selectTB(){
    
    global $db;
    
?>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Relatório Geral</span>
            <hr/>
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Selecione o Banco de Horas:</td>
                    <td style="width: 100%;">
                        <select id="selTB" onchange="styleSelectBox(this); redir(this.selectedValue());" onkeyup="styleSelectBox(this); redir(this.selectedValue());">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
                            <?php
                            
                            $result = $db->query("SELECT ID, `Year` FROM tb_banks ORDER BY `Year` DESC");
                            
                            while ($row = $result->fetch_assoc()){
                                echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . $row['Year'] . '</option>' . PHP_EOL;
                            }
                            
                            $result->close();
                            
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            
        </div>
<?php
    
}

//------------------------------------------

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
        <div class="panel" style="width: 900px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Relatório Por Professor</span>
            <hr/>
            
            <table class="tbl" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Banco de Horas:</td>
                    <td style="width: 100%;" colspan="2">
                        <?php echo '<span style="font-weight: bold;">' . $tbYear . '</span> <span style="font-style: italic;">(' . formatLiteralDate($tbStartDate) . ' a ' . formatLiteralDate($tbEndDate) . ')</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%; font-weight: bold;">
                        <?php echo $tName; ?>
                    </td>
                    <td>
                        <img src="../images/print.png" style="cursor: pointer; vertical-align: middle;" title="Versão para impressão" onclick="window.open('printrpt.php?t=2&tbid=<?php echo $tbid . '&tid=' . $tid; ?>', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');"/>
                    </td>
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
            <table class="tbl" style="width: 100%;">
                <tr style="background-color: #1b9a87; color: white;">
                    <td colspan="3">Crédito Excedente Por Turma</td>
                </tr>
                <tr style="background-color: #85c4bb;">
                    <td style="width: 70%;">Descrição</td>
                    <td style="width: 15%; text-align: center; white-space: nowrap;">Desconto</td>
                    <td style="width: 15%; text-align: center; white-space: nowrap;">Crédito</td>
                </tr>
<?php

            $bgcolor = null;

            foreach ($classCredits as $clCr){

                $bgcolor = ($bgcolor == '#c1c1c1') ? '#e6e6e6' : '#c1c1c1';

                echo '<tr style="background-color: ' . $bgcolor . ';"><td>' . $clCr['Description'] . '</td><td style="text-align: center; color: blue;">&nbsp;</td><td style="text-align: center; color: red;">' . numToTime($clCr['Value']) . '</td></tr>' . PHP_EOL;

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

                    $bgcolor = null;

                    echo '        <table class="tbl" style="width: 100%;">
                <tr style="background-color: #1b9a87; color: white;">
                    <td colspan="4">' . $months[+$datePieces[1]] . '/' . $datePieces[0] . '</td>
                </tr>
                <tr style="background-color: #85c4bb;">
                    <td style="width: 10%; text-align: center;">Dia</td>
                    <td style="width: 60%;">Descrição</td>
                    <td style="width: 15%; text-align: center; white-space: nowrap;">Desconto</td>
                    <td style="width: 15%; text-align: center; white-space: nowrap;">Crédito</td>
                </tr>';

                }

                $bgcolor = ($bgcolor == '#c1c1c1') ? '#e6e6e6' : '#c1c1c1';

                echo '            <tr style="background-color: ' . $bgcolor . ';">
                    <td style="text-align: center;">' . $datePieces[2] . '</td>
                    <td>' . htmlentities($crds['Description'], 0, 'ISO-8859-1') . '</td>
                    <td style="text-align: center; color: blue;">' . (!$crds['isCredit'] ? numToTime($crds['Value']) : '&nbsp;') . '</td>
                    <td style="text-align: center; color: red;">' . ($crds['isCredit'] ? numToTime($crds['Value']) : '&nbsp;') . '</td>
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
            <table class="tbl" style="width: 100%; border-collapse: collapse;">
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

            <div style="padding: 3px; text-align: right;">
                <img src="../images/print.png" style="cursor: pointer; vertical-align: middle;" title="Versão para impressão" onclick="window.open('printrpt.php?t=2&tbid=<?php echo $tbid . '&tid=' . $tid; ?>', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');"/>
            </div>
        </div>
<?php

    }
    else {
        echo '<div style="font-style: italic; color: red; padding: 10px;">Parametros inválidos.</div>';
    }
    
}

//------------------------------------------
    
function selectTeacher(){

    global $db;
    
?>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Relatório Por Professor</span>
            <hr/>
            
            <form method="get" action="report.php">
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Banco de Horas:</td>
                    <td style="width: 100%;">
                        <select id="selTB2" name="tbid">
                            <option value="0" style="font-style: italic;">-Selecione-</option>
                            <?php
                            
                            $result = $db->query("SELECT ID, `Year` FROM tb_banks ORDER BY `Year` DESC");
                            
                            while ($row = $result->fetch_assoc()){
                                echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . $row['Year'] . '</option>' . PHP_EOL;
                            }
                            
                            $result->close();
                            
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%;">
                        <select id="selTeacher" name="tid" style="width: 300px;">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
                            <?php
                            // teachers only
                            $result = $db->query("SELECT ID, Name FROM users WHERE Blocked = 0 AND Status < 2 ORDER BY Name");
                            
                            while ($row = $result->fetch_assoc()){
                                echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
                            }
                            
                            $result->close();
                            
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <input type="submit" value="OK" style="width: 60px;" onclick="return validateOpts();"/>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="t" value="2"/>
            </form>
            
        </div>
<?php
    
}

//----------------------------------------------------

// user-defined comparison function for usort()
function mySort($a, $b){
    
    $ta = strtotime($a['Date'] . ' 00:00:00');
    $tb = strtotime($b['Date'] . ' 00:00:00');
    
    return ($ta > $tb ? 1 : ($ta < $tb ? -1 : 0));
    
}

?>
