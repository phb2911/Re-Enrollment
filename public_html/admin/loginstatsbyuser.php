<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/date_functions.php';
require_once '../genreq/genreq.php';

define('CRLF', "\r\n");

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn(true)){
    $db->close();
    header("Location: login.php?redir=" . urlencode($_SERVER['REQUEST_URI']));
    die();
}

$uid = $_GET['uid'];
$isValid = (isset($uid) && preg_match('/^([0-9])+$/', $uid) && $userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]);
$period = $_GET['p'];

if ($period == 2){
    
    $date1 = trim($_GET['d1']);
    $date2 = trim($_GET['d2']);
    
    $datesAreValid = (isValidDate($date1) && isValidDate($date2));
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Histórico de Acesso</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/dateFunctions.js"></script>
    <link href="<?php echo ROOT_DIR; ?>calendar/calendar.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>/calendar.js"></script>
       
    <style type="text/css">
        
        table.tbl td {
            padding: 5px;
        }
                       
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            if (element('selUser')){
                
                element('selUser').selectedIndex = 0;
                element('selUser').style.fontStyle = 'italic';
                
            }
            
        };
        
        window.onclick = function(e) {
            if(document.getElementById('calendar') && !document.getElementById('calendar').contains(e.target) && e.target.id.substring(0,7) != 'txtDate') {
                hideCalendar();
            }
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27)){
                if (document.getElementById('calendar')) hideCalendar();
            }

        };
        
        var txtBoxIdFlag;

        function showCal(txtBox){
            
            if (txtBox.id != txtBoxIdFlag && CalendarIsOpen()){
                hideCalendar();
            }
            
            txtBoxIdFlag = txtBox.id;

            var abs = getAbsPosition(txtBox);
            var dateStr = txtBox.value.trim();
            var year;
            var month;
            
            if (isValidDate(dateStr)){
                var parts = dateStr.split('/');
                month = parseInt(parts[1], 10);
                year = parseInt(parts[2], 10);
            }
            
            showCalendar(txtBox, abs[0] + 24, abs[1], year, month);
            
        }
        
        function selFilterChanged(index){
            
            var disp;
            
            if (index == 2){
                
                disp = 'table-row';
                
                element('txtDate1').value = '';
                element('txtDate2').value = '';
                
            }
            else disp = 'none';
            
            element('trDate1').style.display = disp;
            element('trDate2').style.display = disp;
            
            element('divErrMsg').style.visibility = 'hidden';
            
        }
        
        function filter(uid){
            
            var sel = element('selFilter').selectedIndex;
            var q = '&p=' + sel;
            
            if (sel == 2 && validateDates()){
                q += '&d1=' + encodeURIComponent(element('txtDate1').value.trim()) + '&d2=' + encodeURIComponent(element('txtDate2').value.trim());
            }
            else if (sel == 2) return;
            
            window.location = 'loginstatsbyuser.php?uid=' + uid + q;
            
        }
        
        function validateDates(){
            
            if (!isValidDate(element('txtDate1').value.trim())){
                alert('A primeira data não é válida.');
                return false;
            }
            
            if (!isValidDate(element('txtDate2').value.trim())){
                alert('A segunda data não é válida.');
                return false;
            }
            
            return true;
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="<?php echo IMAGE_DIR; ?>banner1.jpg"/></a>
        
<?php

renderDropDown($db);

if ($isValid){
?>
        <br/>
        <div class="panel">
            
            <span style="font-weight: bold;">Histórico de Acessos</span>
            <hr/>
            <div style="padding: 10px;">
                Colaborador: &nbsp; <?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?> &nbsp;
                <a href="loginstatsbyuser.php"><img src="<?php echo IMAGE_DIR; ?>pencil1.png" style="vertical-align: middle;" title="Alterar Colaborador"/></a>
            </div>
            
            <table class="tbl" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; padding: 0; vertical-align: top;">
                        <fieldset style="border-radius: 5px; height: 250px;">
                            <legend>Filtrar</legend>
                            
                            <table class="tbl">
                                <tr>
                                    <td style="text-align: right; white-space: nowrap;">Selecione o período:</td>
                                    <td style="width: 100%;">
                                        <select id="selFilter" onchange="selFilterChanged(this.selectedIndex)">
                                            <option>Ultimos sete dias</option>
                                            <option<?php if ($period == 1) echo ' selected="selected"'; ?>>Ultimos trinta dias</option>
                                            <option<?php if ($period == 2) echo ' selected="selected"'; ?>>Entre duas datas</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr id="trDate1" style="display: <?php echo ($period == 2 ? 'table-row' : 'none'); ?>;">
                                    <td style="text-align: right; white-space: nowrap;">Primeira data:</td>
                                    <td style="width: 100%;">
                                        <input type="text" id="txtDate1" value="<?php echo htmlentities($date1, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                                    </td>
                                </tr>
                                <tr id="trDate2" style="display: <?php echo ($period == 2 ? 'table-row' : 'none'); ?>;">
                                    <td style="text-align: right; white-space: nowrap;">Segunda data:</td>
                                    <td style="width: 100%;">
                                        <input type="text" id="txtDate2" value="<?php echo htmlentities($date2, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: right; white-space: nowrap;">&nbsp;</td>
                                    <td style="width: 100%;">
                                        <input type="button" value="Filtrar" onclick="filter(<?php echo $uid; ?>);"/>
                                    </td>
                                </tr>
                            </table>
                            <div id="divErrMsg" style="font-style: italic; color: red; padding: 5px; text-align: center; visibility: <?php echo ($period == 2 && !$datesAreValid ? 'visible' : 'hidden'); ?>;">* Uma das datas não é válida.</div>
                                                       
                        </fieldset>
                    </td>
                    <td style="width: 50%; padding: 0; vertical-align: top;">
                        <fieldset style="border-radius: 5px; height: 250px;">
                            <legend>Acessos</legend>
<?php

    $recs = array();

    if ($period == 2){
        
        if ($datesAreValid){
            
            // create date objects
            $dt = new DateTime(parseDate($date1));
            $dt2 = new DateTime(parseDate($date2));
            
            // swap if first date is earlier than second
            if ($dt < $dt2){
                $tempDt = $dt;
                $dt = $dt2;
                $dt2 = $tempDt;
            }
            
            // set the seconds to the last second
            // of first date
            $dt->setTime(23, 59, 59);
            
            // set seconds to the first second
            // of second date
            $dt2->setTime(0, 0, 0);
            
            // retrieve data
            displayResult($uid, $dt, $dt2, $recs);
        
        }
        
    }
    else {
        
        // create date objects with today's date
        $dt = new DateTime();
        $dt2 = new DateTime();

        // set the time to the last second of today
        $dt->setTime(23, 59, 59);

        // deduct 6 or 30 days and set the time to the
        // first second of the day
        $dt2->sub(new DateInterval('P' . ($period == 1 ? '30' : '6') . 'D'));
        $dt2->setTime(0, 0, 0);
        
        // retrieve data
        displayResult($uid, $dt, $dt2, $recs);
        
    }
    
    echo '<div style="font-weight: bold; font-size: 12px; text-align: center;"><span id="spCount">' . count($recs) . '</span> resultado(s) encontrado(s)</div>' . CRLF . 
            '<div id="divResultBox" style="border: #7a7a7a solid 1px; overflow-y: scroll; height: 210px;">' . CRLF;
    
    $count = 0;
    
    foreach ($recs as $rec) {
        
        $bgcolor = ($bgcolor == '#d3d3d3') ? '#ffffff' : '#d3d3d3';
        $count++;
        
        echo '<div style="background-color: ' . $bgcolor . '; padding: 5px;">' . $count . '. &nbsp; ' . date('d/m/Y H:i:s', $rec) . '</div>' . CRLF;
        
    }
    
    echo '</div>' . CRLF;

?>
                        </fieldset>
                    </td>
                </tr>
            </table>
                                   
        </div>
<?php 
}
else{
?>
        <br/>
        <div class="panel">
            
            <span style="font-weight: bold;">Histórico de Acessos</span>
            <hr/>
            
            <div style="padding: 10px;">
                Selecione Colaborador: &nbsp;
                <select id="selUser" onchange="styleSelectBox(this); if (this.selectedIndex > 0) window.location = 'loginstatsbyuser.php?uid=' + this.options[this.selectedIndex].value;" onkeyup="styleSelectBox(this); if (this.selectedIndex > 0) window.location = 'loginstatsbyuser.php?uid=' + this.options[this.selectedIndex].value;">
                    <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    $result = $db->query("SELECT ID, Name FROM users WHERE Blocked = 0 ORDER BY Name");
    
    while ($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . CRLF;
    }
    
    $result->close();

?>
                </select>
            </div>
        </div>
<?php } ?>
    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

//----------------------------------------

function displayResult($uid, $dt, $dt2, &$arr){
    
    global $db;
    
    $result = $db->query("SELECT `DateTime` FROM access_log WHERE UserID = $uid AND DateTime < " . $dt->getTimestamp() . " AND DateTime > " . $dt2->getTimestamp() . " ORDER BY DateTime DESC");
        
    while ($resDT = $result->fetch_row()[0]){
        $arr[] = $resDT;
    }
        
    $result->close();
    
}

?>