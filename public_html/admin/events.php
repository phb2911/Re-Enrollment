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

if (!$loginObj->isLoggedIn(true)){
    $db->close();
    header("Location: " . LOGIN_PAGE);
    die();
}

$uid = getGet('uid');

$isValid = (isNum($uid) && $userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]);

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Admin - Eventos</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/dateFunctions.js"></script>
    <link href="<?php echo ROOT_DIR; ?>calendar/calendar.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>calendar/calendar.js"></script>
       
    <style type="text/css">
        
        .tbl td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            if (element('selUser')) styleSelectBox(element('selUser'));
            
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
        
        function redir(sel){
            
            if (sel.selectedIndex > 0){
                
                window.location = 'events.php?uid=' + selectedValue(sel);
                
            }
            
        }
        
        function filter(index, uid){
            
            if (index != 2){
                element('divDates').style.display = 'none';
                window.location = 'events.php?uid=' + uid + '&ri=' + index;
            }
            else {
                element('divDates').style.display = 'block';
            }
            
        }
        
        function filterBTD(uid){
            
            var date1 = element('txtDate1').value.trim();
            var date2 = element('txtDate2').value.trim();
            
            if (!isValidDate(date1)){
                alert('A primeira data não é válida');
            }
            else if (!isValidDate(date2)){
                alert('A segunda data não é válida');
            }
            else {
                window.location = 'events.php?uid=' + uid + '&ri=2&date1=' + encodeURIComponent(date1) + '&date2=' + encodeURIComponent(date2);
            }
            
        }
        
        function printVersion(uid, range, date1, date2){
            
            if (range != 1 && range != 2 && range != 3) range = 0;
            
            window.open('printrept.php?t=1&uid=' + uid + '&r=' + range + '&d1=' + encodeURIComponent(date1) + '&d2=' + encodeURIComponent(date2), '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');
            
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
    displayEvents($uid, $userName);
}
else {
    selectUser();
}

?>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

// -------------------------------------------------

function displayEvents($uid, $userName){
    
    global $db;
    
    $ri = getGet('ri');
    $date1 = trim(getGet('date1'));
    $date2 = trim(getGet('date2'));
    $msg = null;
    
    if (!isNum($ri) || $ri > 3) $ri = 0;
    
    if ($ri == 2){
        if (!isValidDate($date1)){ 
            $msg = '<span style="font-style: italic; color: red;">A primeira data não é válida.</span>';
            $validDates = false;
        }
        elseif (!isValidDate($date2)){ 
            $msg = '<span style="font-style: italic; color: red;">A segunda data não é válida.</span>';
            $validDates = false;
        }
        else {
            $validDates = true;
        }
    }
    
?>
        <br/>
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
        <div class="panel">
            
            <span style="font-weight: bold;">Eventos</span>
            <hr/>
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; vertical-align: top;">Visualizar:</td>
                    <td style="width: 100%;">
                        <select onchange="filter(this.selectedIndex, <?php echo $uid; ?>);">
                            <option<?php if ($ri == 0) echo ' selected="selected"'; ?>>Ultimos 30 dias</option>
                            <option<?php if ($ri == 1) echo ' selected="selected"'; ?>>Ultimos 6 meses</option>
                            <option<?php if ($ri == 2) echo ' selected="selected"'; ?>>Entre duas datas</option>
                            <option<?php if ($ri == 3) echo ' selected="selected"'; ?>>Todos</option>
                        </select>
                        <div id="divDates" style="padding-top: 5px; line-height: 200%; display: <?php echo ($ri == 2 ? 'block' : 'none'); ?>;">
                            <input type="text" id="txtDate1" value="<?php echo htmlentities($date1, 3, 'ISO-8859-1'); ?>" style="width: 120px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/><br/>
                            <input type="text" id="txtDate2" value="<?php echo htmlentities($date2, 3, 'ISO-8859-1'); ?>" style="width: 120px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                            <input type="button" value="OK" onclick="filterBTD(<?php echo $uid; ?>);"/>
                        </div>
                    </td>
                </tr>
            </table>
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="white-space: nowrap; width: 100%;" colspan="2">
                        <button onclick="window.location = 'newevent.php?uid=<?php echo $uid; ?>';"><img src="<?php echo IMAGE_DIR; ?>plus.png"/> Adicionar Novo Evento</button>
                        <button onclick="window.location = 'events.php'"><img src="<?php echo IMAGE_DIR; ?>pencil1.png"> Mudar Colaborador</button>
                    </td>
                    <td style="vertical-align: bottom;"><img src="<?php echo IMAGE_DIR; ?>print.png" title="Versão para impressão" style="cursor: pointer; vertical-align: bottom;" onclick="printVersion(<?php echo $uid . ',' . $ri . ',\'' . $date1 . '\',\'' . $date2 . '\''; ?>)"/></td>
                </tr>
            </table>
            
        </div>
<?php

    if ($ri == 0){
        // last 30 days
        $q = 'AND EventDate >= ' . (time() - (60*60*24*30));
    }
    elseif ($ri == 1){
        // last six months
        $q = 'AND EventDate >= ' . (time() - (60*60*24*183));
    }
    elseif ($ri == 2 && $validDates){
        // between two dates
        // if dates are not valid, display last 30 days
        
        if (compareDates($date1, $date2) == 1){
            // swap dates
            $tmpDate = $date1;
            $date1 = $date2;
            $date2 = $tmpDate;
        }
        
        $q = 'AND EventDate >= ' . strtotime(parseDate($date1) . ' 00:00:00') . ' AND EventDate <= ' . strtotime(parseDate($date2) . ' 23:59:59');
        
    }
    elseif ($ri == 3){
        // display all
        $q = '';
    }

    $result = $db->query("SELECT `events`.ID, `events`.Description, `events`.EventDate, event_reasons.Description AS Reason FROM `events` JOIN event_reasons ON `events`.Reason = event_reasons.ID WHERE User = $uid $q ORDER BY EventDate DESC");
    
    if ($result->num_rows){
        
?>
        <br/>
        <table class="tbl" style="width: 100%; border: #fd7706 solid 1px; box-shadow: 3px 3px 3px #808080;">
            <tr style="background-color: #fd7706; color: white;">
                <td style="width: 15%;">Data</td>
                <td style="width: 30%;">Motivo</td>
                <td style="width: 55%;">Descrição</td>
                <td></td>
            </tr>
<?php

        $bgcolor = null;

        while ($row = $result->fetch_assoc()){
            
            $bgcolor = ($bgcolor == '#c6c6c6') ? '#ffffff' : '#c6c6c6';
            
            echo '<tr style="background-color: ' . $bgcolor . ';"><td>' . date('d/m/Y', $row['EventDate']) . '</td><td>' . htmlentities($row['Reason'], 0, 'ISO-8859-1') . '</td><td>' . nl2br(htmlentities($row['Description'], 0, 'ISO-8859-1')) . '</td><td><a href="editevent.php?eid=' . $row['ID'] . '"><img src="' . IMAGE_DIR . 'pencil1.png" title="Editar evento"/></a></td></tr>' . PHP_EOL;
            
        }

?>
        </table>
<?php
        
    }
    else {
        echo '<div style="font-style: italic; color: red; padding: 10px;">Este colaborador não possui eventos no período selecionado.</div>';
    }
    
    $result->close();

}

// -------------------------------------------------

function selectUser(){
    
    global $db;
    
?>
        <br/>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Eventos</span>
            <hr/>
            
            <div style="padding: 10px;">
                Selecione o colaborador: &nbsp;
                <select id="selUser" style="width: 300px;" onchange="styleSelectBox(this); redir(this);" onkeyup="styleSelectBox(this); redir(this);">
                    <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    $result = $db->query("SELECT ID, Name FROM users WHERE Blocked = 0 ORDER BY Name");
    
    while($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }
    
    $result->close();

?>
                </select>
            </div>
            
        </div>
<?php
    
}

?>