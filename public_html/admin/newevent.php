<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
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
$date = getPost('date');
$reason = getPost('reason');
$desc = trim(getPost('desc'));
$msg = null;

$isValid = (isNum($uid) && $userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]);

if ($isValid && getPost('postback') == '1'){
    
    $descLen = strlen($desc);
    
    if (!isValidDate(trim($date))){
        $msg = '<span style="font-style: italic; color: red;">A data não é válida.</span>';
    }
    elseif (!isNum($reason) || !$db->query("SELECT COUNT(*) FROM event_reasons WHERE ID = $reason")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">O motivo não é válido.</span>';
    }
    elseif (!$descLen){
        $msg = '<span style="font-style: italic; color: red;">A descrição não é válida.</span>';
    }
    elseif ($descLen > 5000){
        $msg = '<span style="font-style: italic; color: red;">A descrição não pode conter mais de 5000 caracteres.</span>';
    }
    elseif ($db->query("INSERT INTO events (User, Reason, Description, EventDate) VALUES ($uid, $reason, '" . $db->real_escape_string($desc) . "', " . strtotime(parseDate($date)) . ")")) {
        
        $db->close();
        
        header('Location: events.php?uid=' . $uid);
        die();
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Admin - Adicionar Novo Evento</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/dateFunctions.js"></script>
    <link href="<?php echo ROOT_DIR; ?>calendar/calendar.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>calendar/calendar.js"></script>
       
    <style type="text/css">
        
        table.tbl td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            if (element('txtDesc')) charsCount();
            
            if (element('selUser')) styleSelectBox(element('selUser'));
            if (element('selReason')) styleSelectBox(element('selReason'));
            
        };
        
        window.onclick = function(e) {
            if(document.getElementById('calendar') && !document.getElementById('calendar').contains(e.target) && e.target.id != 'txtDate') {
                hideCalendar();
            }
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27)){
                if (document.getElementById('calendar')) hideCalendar();
            }

        };
        
        function showCal(txtBox){
            
            if (CalendarIsOpen()) return;
            
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
        
        function charsCount(){
            
            element('spCharCount').innerHTML = 5000 - element('txtDesc').value.length;
            
        }
        
        function validateInput(){
            
            var descLen = element('txtDesc').value.trim().length;
            
            if (!isValidDate(element('txtDate').value.trim())){
                alert('A data não é válida.');
                return false;
            }
            else if (element('selReason').selectedIndex === 0){
                alert('Por favor selecione o motivo.');
                return false;
            }
            else if (!descLen){
                alert('A descrição não é válida.');
                return false;
            }
            else if (descLen > 5000){
                alert('A descrição não pode conter mais de 5000 caracteres.');
                return false;
            }
            
            return true;
        }
        
        function redir(sel){
            
            if (sel.selectedIndex > 0){
                
                window.location = 'newevent.php?uid=' + selectedValue(sel);
                
            }
            
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
    addEvent($uid, $userName, $date, $reason, $desc, $msg);
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

function addEvent($uid, $userName, $date, $reason, $desc, $msg){
    
    global $db;
    
?>
        <br/>
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: 700px; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Adicionar Novo Evento</span>
            <hr/>
            
            <form action="newevent.php?uid=<?php echo $uid; ?>" method="post">
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?> &nbsp; <a href="newevent.php"><img src="<?php echo IMAGE_DIR; ?>pencil1.png" title="Modificar o colaborador" /></a></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Data:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtDate" name="date" value="<?php echo htmlentities($date, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">Motivo:</td>
                    <td style="width: 100%;">
                        <select id="selReason" name="reason" style="width: 300px;" onchange="styleSelectBox(this);" onkeyup="styleSelectBox(this);">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    $result = $db->query("SELECT * FROM event_reasons ORDER BY ID = 1 DESC, Description");
    
    while ($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;"' . ($row['ID'] == $reason ? ' selected="selected"' : '') . '>' . htmlentities($row['Description'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }
    
    $result->close();

?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; vertical-align: top;">Descrição:</td>
                    <td style="width: 100%;">
                        <textarea id="txtDesc" name="desc" style="width: 95%; height: 100px; resize: none;" maxlength="5000" onkeyup="charsCount();"><?php echo htmlentities($desc, 0, 'ISO-8859-1'); ?></textarea><br/>
                        <span style="color: red; font-style: italic; font-size: 12px;">* <span id="spCharCount"></span> caracteres disponíveis.</span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;"></td>
                    <td style="width: 100%;">
                        <input type="submit" value="Salvar" style="width: 75px;" onclick="return validateInput();"/>
                        <input type="button" value="Cancelar" style="width: 75px;" onclick="window.location = 'events.php?uid=<?php echo $uid; ?>';"/>
                    </td>
                </tr>
            </table>
            
            <input type="hidden" name="postback" value="1"/>
            </form>
                
        </div>
<?php
    
}

// -------------------------------------------------

function selectUser(){
    
    global $db;
    
?>
        <br/>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Adicionar Novo Evento</span>
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

// -------------------------------------------------

function isValidDate($dateStr){
    
    // check format
    // valid format: d/m/yyyy or dd/mm/yyyy
    if (!preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateStr)) return false;
        
    $pieces = explode('/', $dateStr);
    $day = intval($pieces[0], 10);
    $month = intval($pieces[1], 10);
    $year = intval($pieces[2], 10);

    // Check the ranges of month and year
    if($year < 1970 || $year > 2099 || $month == 0 || $month > 12) return false;

    // Adjust for leap years on $monthLength[1]
    $monthLength = array(31, ($year % 400 == 0 || ($year % 100 != 0 && $year % 4 == 0) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

    // Check the range of the day
    return $day > 0 && $day <= $monthLength[$month - 1];
    
}

// -------------------------------------------------

function parseDate($date){
    
    // converts date dd/mm/yyyy to yyyy-mm-dd
    $parts = explode('/', $date);
    return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    
}

?>