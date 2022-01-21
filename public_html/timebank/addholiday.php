<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/date_functions.php';
require_once '../genreq/genreq.php';

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

$months = array('Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');

$mon = getGet('mon');
$yr = getGet('yr');

// validate month and year
$isValid = (isNum($mon) && isNum($yr) && $mon >= 0 && $mon <= 11 && $yr >= 2000 && $yr <= 2099);

$msg = null;

if ($isValid && isset($_POST['save'])){
    
    $desc = trim(getPost('desc'));
    $date = getPost('date');
    $official = getPost('type');
    
    // validate input
    if (!strlen($desc)){
        $msg = '<span style="font-style: italic; color: red;">A descrição não é válida.</span>';
    }
    elseif (!isValidDate($date)){
        $msg = '<span style="font-style: italic; color: red;">A data do feriado não é válida.</span>';
    }
    elseif ($official != 0 && $official != 1){
        $msg = '<span style="font-style: italic; color: red;">Por favor selecione o tipo do feriado.</span>';
    }
    else {
        
        if ($db->query("CALL `sp_add_holiday` ('" . $db->real_escape_string($desc) . "', '" . parseDate($date) . "', $official);")){
            $msg = '<span style="font-style: italic; color: blue;">Feriado criado/modificado com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    
}
elseif ($isValid && isset($_POST['delete'])){
    
    $date = getPost('date');
    
    if (!isValidDate($date)){
        $msg = '<span style="font-style: italic; color: red;">A data do feriado não é válida.</span>';
    }
    else {
        $db->query("DELETE FROM tb_holidays WHERE `Date` = '" . parseDate($date) . "'");
        $msg = '<span style="font-style: italic; color: blue;">Feriado removido com sucesso.</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Adicionar Feriados</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="../js/general.js"></script>
           
    <style type="text/css">
        
        .tbl td {
            padding: 5px;
        }
        
        .tdCal:hover {
            background: #8bded2 !important;
        }
        
        .tdCalOff:hover {
            background: #1b9a87 !important;
        }
        
        .tdCalUnoff:hover {
            background: #22ebb4 !important;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            if (element('selMon')) initializeSelect(element('selMon'));
            
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27)){
                hideBox();
            }

        };
        
        function redir(){
            
            if (element('selMon').selectedIndex == 0){
                alert('Por favor selecione o mês.');
            }
            else {
                window.location = 'addholiday.php?yr=' + element('selYr').selectedValue() + '&mon=' + element('selMon').selectedValue();
            }
            
        }
        
        function nextMon(yr, mon){
            
            mon++;
            
            if (mon == 12) {
                mon = 0;
                yr++;
            }
            
            if (yr <= 2099) window.location = 'addholiday.php?yr=' + yr + '&mon=' + mon;
            
        }
        
        function prevMon(yr, mon){
            
            mon--;
            
            if (mon < 0){
                mon = 11;
                yr--;
            }
            
            if (yr >= 2000) window.location = 'addholiday.php?yr=' + yr + '&mon=' + mon;
            
        }
        
        function changeDate(){
            
            window.location = 'addholiday.php?yr=' + element('selYear').selectedValue() + '&mon=' + element('selMonth').selectedValue();
            
        }
        
        function hideBox(){
            element('overlay').style.visibility = 'hidden';
            element('editBox').style.visibility = 'hidden';
        }
        
        function showBox(yr, mon, day){
            
            element('divContent').innerHTML = '<div style="text-align: center;"><br/><br/><br/><img src="../images/circle_loader.gif"/></div>';
            
            element('overlay').style.visibility = 'visible';
            element('editBox').style.visibility = 'visible';
            
            var xmlhttp = xmlhttpobj();
            
            xmlhttp.onreadystatechange = function() {
                
                // request ready
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    
                    element('divContent').innerHTML = xmlhttp.responseText;
                    
                }
                
            };
            
            // THIS WILL STOP PAGE
            xmlhttp.open("POST","aj_holiday.php?" + Math.random(),false);
            xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
            xmlhttp.send("year=" + yr + "&month=" + mon + "&day=" + day);
            
        }
        
        function validateInput(){
            
            if (!element('txtDesc').value.trim().length){
                alert('A descrição não é válida.');
                element('txtDesc').focus();
                return false;
            }
            
            return true;
            
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
        <div id="msgBox" style="width: 500px; left: 0; right: 0; margin: auto; display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <br/>
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
        </div>
        <br/>
<?php

if ($isValid){
?>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Adicionar/Modificar Feriados</span>
            <hr/>

            <table style="width: 100%; border: black solid 1px;">
                <tr style="background-color: #1b9a87;">
                    <td colspan="7">
                        <table style="border-collapse: collapse; width: 100%;">
                            <tr>
                                <td style="font-weight: bold; cursor: pointer; padding-left: 5px;" title="Mês anterior" onclick="prevMon(<?php echo $yr . ',' . $mon; ?>);">&lt;</td>
                                <td style="width: 100%; text-align: center;">
                                    <select id="selMonth" onchange="changeDate();" onkeyup="changeDate();">
                                    <?php

                                    for ($i = 0; $i <= 11; $i++){
                                        echo '<option value="' . $i . '"' . ($mon == $i ? ' selected="selected"' : '') . '>' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . ' - ' . $months[$i] . '</option>' . PHP_EOL;
                                    }

                                    ?>
                                    </select> / 
                                    <select id="selYear" onchange="changeDate();" onkeyup="changeDate();">
                                    <?php

                                    for ($i = 2000; $i <= 2099; $i++){
                                        echo '<option value="' . $i . '"' . ($yr == $i ? ' selected="selected"' : '') . '>' . $i . '</option>' . PHP_EOL; 
                                    }

                                    ?>
                                    </select>
                                </td>
                                <td style="font-weight: bold; cursor: pointer; padding-right: 5px;" title="Próximo mês" onclick="nextMon(<?php echo $yr . ',' . $mon; ?>);">&gt;</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="tbl" style="background-color: #85c4bb;">
                    <td style="text-align: center; width: 14%; color: red;">Dom</td>
                    <td style="text-align: center; width: 14%;">Seg</td>
                    <td style="text-align: center; width: 14%;">Ter</td>
                    <td style="text-align: center; width: 14%;">Qua</td>
                    <td style="text-align: center; width: 14%;">Qui</td>
                    <td style="text-align: center; width: 14%;">Sex</td>
                    <td style="text-align: center; width: 14%;">Sab</td>
                </tr>
                <?php
                
                // Adjust for leap years on $monthLength[1]
                $monthLength = array(31, ($yr % 400 == 0 || ($yr % 100 != 0 && $yr % 4 == 0) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
                
                // fetch holdays this month
                $holidays = array();
                
                $result = $db->query("SELECT * FROM tb_holidays WHERE `Date` >= '$yr-" . ($mon + 1) . "-01' AND `Date` <= '$yr-" . ($mon + 1) . "-" . $monthLength[$mon] . "' ORDER BY `Date`");
                
                while ($row = $result->fetch_assoc()){
                    $day = +date('j', strtotime($row['Date']));
                    $holidays[$day] = $row;
                }
                
                $result->close();
                
                $firstWeekDay = +date('w', strtotime($yr . '-' . ($mon + 1) . '-01'));
                
                $curDay = 0;
                $weekDay = 0;
                
                while ($curDay < $monthLength[$mon] || $weekDay != 0){
                    
                    // start new row
                    if ($weekDay == 0){
                        if ($curDay > 0) echo '</tr>';
                        echo '<tr class="tbl" style="text-align: right;">' . PHP_EOL;
                    }
                    
                    if ($curDay > 0 || $weekDay == $firstWeekDay){
                        
                        $curDay++;
                        
                        if ($curDay <= $monthLength[$mon]){
                            
                            $bgcolor = '#85c4bb';
                            $tdClass = 'tdCal';
                            $title = '';
                            
                            if (isset($holidays[$curDay])){
                                
                                // change background and td class color if holiday exists
                                if ($holidays[$curDay]['Official']){
                                    $bgcolor = '#2e6d64';
                                    $tdClass = 'tdCalOff';
                                }
                                else {
                                    $bgcolor = '#0ad19b';
                                    $tdClass = 'tdCalUnoff';
                                }
                                
                                // set title
                                $title = ' title="' . $holidays[$curDay]['Description'] . '"';
                                
                            }
                            
                            echo '<td class="' . $tdClass . '" style="background-color: ' . $bgcolor . '; cursor: pointer;' . ($weekDay == 0 ? ' color: red;' : '') . '"' . $title . ' onclick="showBox(' . $yr . ', ' . ($mon + 1) . ', ' . $curDay . ');">' . $curDay . '</td>' . PHP_EOL;
                            
                        }
                        else {
                            echo '<td style="background-color: #85c4bb;">&nbsp;</td>' . PHP_EOL;
                        }
                        
                    }
                    else {
                        echo '<td style="background-color: #85c4bb;">&nbsp;</td>' . PHP_EOL;
                    }
                    
                    $weekDay++;
                    
                    if ($weekDay > 6) $weekDay = 0;
                    
                }
                
                echo '</tr>' . PHP_EOL;
                
                ?>
            </table>
            <span style="font-style: italic; color: red; font-size: 13px;">*Click na data para adicionar ou modificar feriado.</span>
            <br/><br/>
            <div style="border: black solid 1px; background-color: #2e6d64; width: 16px; height: 16px; display: inline-block;"></div> Feriado<br/>
            <div style="border: black solid 1px; background-color: #0ad19b; width: 16px; height: 16px; display: inline-block;"></div> Folga
        </div>
<?php

    if (count($holidays)){
?>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Feriados de <?php echo $months[$mon] . '/' . $yr; ?></span>
            <hr/>
            
            <?php
            
            foreach ($holidays as $day => $holInfo) {
                echo '<div style="padding-left: 10px;">' . ($day < 10 ? '0' . $day : $day) . ' - ' . htmlentities($holInfo['Description'], 0, 'ISO-8859-1') . (!$holInfo['Official'] ? ' (folga)' : '') . '</div>' . PHP_EOL;
            }
            
            ?>
            
        </div>
<?php
    }

}
else {
?>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Adicionar/Modificar Feriados</span>
            <hr/>
            
            <div style="padding: 10px;">
                Selecione o mês e ano: &nbsp;
                <select id="selMon">
                    <option style="font-style: italic;">-Selecione-</option>
                <?php
                
                for ($i = 0; $i <= 11; $i++){
                    echo '<option value="' . $i . '" style="font-style: normal;">' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . ' - ' . $months[$i] . '</option>' . PHP_EOL;
                }
                
                ?>
                </select> / 
                <select id="selYr">
                <?php
                
                $curYr = +date('Y', time());
                
                for ($i = 2000; $i <= 2099; $i++){
                    echo '<option value="' . $i . '"' . ($curYr == $i ? ' selected="selected"' : '') . '>' . $i . '</option>' . PHP_EOL; 
                }
                
                ?>
                </select>
                <input type="button" value="OK" onclick="redir();"/>
            </div>
            
        </div>
<?php
}
    
?>
    </div>
    
    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay"></div>
    <div class="helpBox" id="editBox" style="width: 450px; height: 170px;">
        <div class="closeImg" onclick="hideBox();"></div>
        <span style="font-weight: bold;">Adicionar/Modificar Feriado</span>
        <hr/>
        <form method="post" action="addholiday.php<?php echo '?yr=' . $yr . '&mon=' . $mon; ?>">
        <div id="divContent"></div>
        </form>
    </div>
    
</body>
</html>
<?php

$db->close();

?>