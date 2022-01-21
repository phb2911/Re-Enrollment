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

$tbid = getGet('tbid');
$isValid = false;
$msg = null;
$desc = null;
$date1 = null;
$durgen = null;
$teacher = null;
$duration = null;
$errArr = array();

// get time bank info
if (isNum($tbid) && $tbinfo = $db->query("SELECT `Year`, StartDate, EndDate FROM tb_banks WHERE ID = $tbid AND Active = 1")->fetch_assoc()){
    
    $tbYear = $tbinfo['Year'];
    
    $tbStartDate = $tbinfo['StartDate'];
    $tbEndDate = $tbinfo['EndDate'];
    
    // get user info and set valid flag
    $isValid = true;
    
}

// form submitted
if (isset($_POST['save'])){
    
    $desc = trim(getPost('desc'));
    $date1 = trim(getPost('date1'));
    $durgen = trim(getPost('durgen'));
    $teacher = getPost('teacher');
    $descs = getPost('descs');
    $duration = getPost('duration');
    
    //validate input
    if (!isValidDate($date1)){
        $errArr[] = 'A data não é válida.';
    }
    elseif(!isWithinPeriod(formatDate($tbStartDate), formatDate($tbEndDate), $date1)){
        $errArr[] = 'A data está fora do período do banco de horas atual.';
    }
    
    if (!is_array($teacher) || !count($teacher)){
        $errArr[] = 'Nenhum professor foi selecionado.';
    }
    else {
        
        foreach ($teacher as $tid) {
            
            $dur = isset($duration[$tid]) ? $duration[$tid] : null;
            $dscLen = isset($descs[$tid]) ? strlen(trim($descs[$tid])) : 0;
            
            if ($dscLen == 0 || $dscLen > 200 || !validateShortTimeStr($dur)){
                $errArr[] = 'Uma ou mais descrições/durações não são válidas.';
                break;
            }
            
        }
        
    }
    
    // check if errors were found
    if (count($errArr)){
        
        $msg = '<span style="font-style: italid; color: red;">Os seguintes erros foram encontrados:<ul>' . PHP_EOL;
        
        foreach ($errArr as $err){
            $msg .= '<li>' . $err . '</li>' . PHP_EOL;
        }
        
        $msg .= '</ul>' . PHP_EOL;
        
    }
    else {
        
        // build query
        $q = "INSERT INTO tb_discounts (User, Bank, Description, `Date`, Duration) VALUES ";
        
        $dt = parseDate($date1);
        $usrs = array();
        
        foreach ($teacher as $tid) {
            
            if (count($usrs)) $q .= ",";
            
            $d = trim($descs[$tid]);
            
            $q .= "($tid, $tbid, '" . $db->real_escape_string($d) . "', '$dt', " . timeToNum($duration[$tid]) . ")";
            
            $usrs[$tid] = array($d, $duration[$tid]);
            
        }
        
        // insert data
        if ($db->query($q)){
            
            // start session
            session_start();

            // save data into session variable
            $_SESSION['tbid'] = $tbid;
            $_SESSION['description'] = $desc;
            $_SESSION['date'] = $date1;
            $_SESSION['users'] = $usrs;
            
            // redirect to confirmation page
            $db->close();
            header("Location: discconf.php");
            die();
        
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Adicionar Multiplos Descontos</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="../js/general.js"></script>
    <script type="text/javascript" src="../js/dateFunctions.js"></script>
    <link href="../calendar/calendar.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="../calendar/calendar.js"></script>
       
    <style type="text/css">
        
        .tbl td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            if (element('chkAll')){
                verifyChkBoxes();
                validateDscDur();
            }
            
            if (element('selTB')) styleSelectBox(element('selTB'));
            
        };
        
        window.onclick = function(e) {
            if(document.getElementById('calendar') && !document.getElementById('calendar').contains(e.target) && e.target.id.substring(0,7) != 'txtDate') {
                hideCalendar();
            }
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27))
                if (document.getElementById('calendar')) hideCalendar();

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
            
            if (sel.selectedIndex > 0) window.location = 'addmultidisc.php?tbid=' + sel.selectedValue();
            
        }
        
        function checkAll(checked){
            
            var chkBoxes = document.getElementsByClassName('teachers');
            var i;
            
            for (i = 0; i < chkBoxes.length; i++){
                chkBoxes[i].checked = checked;
            }
            
        }
        
        function verifyChkBoxes(){
            
            var chkBoxes = document.getElementsByClassName('teachers');
            var i;
            var flag = true;
            
            for (i = 0; i < chkBoxes.length; i++){
                if (!chkBoxes[i].checked){
                    flag = false;
                    break;
                }
            }
            
            element('chkAll').checked = flag;
            
        }
        
        var curDsc;
        
        function propagateDescription(){
            
            var dsc = element('txtDesc').value.trim();
            
            // check if there was alteration
            if (curDsc != dsc){
                
                var dscs = document.getElementsByClassName('descriptions');
                
                for (var i = 0; i < dscs.length; i++){
                    dscs[i].value = dsc;
                    dscs[i].style.backgroundColor = '';
                }
                
            }
            
            curDsc = dsc;
            
        }
        
        var curDur;
        
        function propagateDuration(){
            
            var dur = element('txtDurGen').value.trim();
            
            if(!dur.length || (/^\d{1,2}\:\d{2}$/.test(dur) && dur.split(':')[1] <= 59)){
            
                if (curDur == dur) return;
            
                var durs = document.getElementsByClassName('durations');
                
                for (var i = 0; i < durs.length; i++){
                    durs[i].value = dur;
                    durs[i].style.backgroundColor = '';
                }
            
            }
            
            curDur = dur;
            
        }
        
        function validateInput(){
            
            var chkBoxes = document.getElementsByClassName('teachers');
            var i;
            var flag = false;
            
            if (!isValidDate(element('txtDate1').value.trim())){
                alert('A data não é válida.');
                element('txtDate1').focus();
                return false;
            }
            
            for (i = 0; i < chkBoxes.length; i++){
                if (chkBoxes[i].checked){ 
                    flag = true;
                    break;
                }
            }
            
            if (!flag){
                alert('Por favor selecione pelo menos um professor.');
                return false;
            }
            
            if (!validateDscDur()){
                alert('Uma ou mais descrições/durações não são válidas.');
                return false;
            }
            
            return true;
            
        }
        
        function validateDscDur(){
            
            var chkBoxes = document.getElementsByClassName('teachers');
            var res = true;
            
            for (var i = 0; i < chkBoxes.length; i++){
                
                if (chkBoxes[i].checked){
                    
                    if (!element('txtDesc' + chkBoxes[i].value).value.trim().length){
                        element('txtDesc' + chkBoxes[i].value).style.backgroundColor = '#ffb5a8';
                        res = false;
                    }
                    
                    if (!validateShortTimeStr(element('txtDuration' + chkBoxes[i].value).value)){
                        element('txtDuration' + chkBoxes[i].value).style.backgroundColor = '#ffb5a8';
                        res = false;
                    }
                    
                }
                
            }
            
            return res;
            
        }
        
        function clearBGColor(index){
            element('txtDesc' + index).style.backgroundColor = '';
            element('txtDuration' + index).style.backgroundColor = '';
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
        <div id="msgBox" style="width: 800px; left: 0; right: 0; margin: auto; display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
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
        <div class="panel" style="width: 800px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Adicionar Descontos ao Banco de Horas</span>
            <hr/>
            
            <form method="post" action="addmultidisc.php?tbid=<?php echo $tbid; ?>">
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Banco de Horas:</td>
                    <td style="width: 100%;" colspan="3">
                    <?php
                        echo '<span style="font-weight: bold;">' . $tbYear . '</span> <span style="font-style: italic;">(' . formatLiteralDate($tbStartDate) . ' a ' . formatLiteralDate($tbEndDate) . ')</span>';
                    ?>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Descrição:</td>
                    <td style="width: 100%;" colspan="3">
                        <input type="text" id="txtDesc" name="desc" value="<?php echo htmlentities($desc, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200" onkeyup="propagateDescription();" onblur="propagateDescription();"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Data:</td>
                    <td style="width: 25%;">
                        <input type="text" id="txtDate1" name="date1" value="<?php echo htmlentities($date1, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">Duração:</td>
                    <td style="width: 75%;">
                        <input type="text" id="txtDurGen" name="durgen" value="<?php echo htmlentities($durgen, 3, 'ISO-8859-1'); ?>" style="width: 40px; text-align: right;" placeholder="00:00" maxlength="5" autocomplete="off" onkeyup="propagateDuration();" onblur="autoFormatTime(this); propagateDuration();"/>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%;" colspan="4">
                        <fieldset style="border-radius: 5px;">
                            <legend>Selecione os Professores:</legend>
                            <?php
                            
                            $result = $db->query("SELECT ID, Name FROM users WHERE Blocked = 0 AND Status < 2 ORDER BY Name");
                            
                            $noTeacherFlag = false;
                            
                            if ($result->num_rows){
                                
                                echo '<table class="tbl" style="width: 100%; border-collapse: collapse;"><tr style="background-color: #f1f1f1;"><td style="width: 100%;"><input type="checkbox" id="chkAll" onclick="checkAll(this.checked);"/><label for="chkAll"> Todos</label></td><td>Descrição:</td><td>Duração:</td></tr>' . PHP_EOL;
                                
                                $bgcolor = null;
                                
                                while ($row = $result->fetch_assoc()){
                                    
                                    $bgcolor = ($bgcolor == '#c6c6c6') ? '#f1f1f1' : '#c6c6c6';
                                    
                                    echo '<!--NEXT ROW-->' . PHP_EOL . '<tr style="background-color: ' . $bgcolor . ';">' . PHP_EOL . 
                                            '<td style="width: 100%;"><input type="checkbox" class="teachers" id="chkTeacher' . $row['ID'] . '" name="teacher[' . $row['ID'] . ']" value="' . $row['ID'] . '" onclick="verifyChkBoxes(); clearBGColor(' . $row['ID'] . ');"' . (isset($teacher[$row['ID']]) ? ' checked="checked"' : '') . '/><label for="chkTeacher' . $row['ID'] . '"> ' . $row['Name'] . '</label></td>' . PHP_EOL .
                                            '<td><input type="text" class="descriptions" id="txtDesc' . $row['ID'] . '" name="descs[' . $row['ID'] . ']" value="' . (isset($teacher[$row['ID']]) ? htmlentities(getTwoDeepPost('descs', $row['ID']), 3, 'ISO-8859-1') : '') . '" style="width: 300px;" maxlength="200" onkeypress="this.style.backgroundColor = \'\';"/></td>' . PHP_EOL .
                                            '<td style="text-align: center;"><input type="text" class="durations" id="txtDuration' . $row['ID'] . '" name="duration[' . $row['ID'] . ']" value="' . (isset($teacher[$row['ID']]) ? $duration[$row['ID']] : '') . '" style="width: 40px; text-align: right;" placeholder="00:00" maxlength="5" autocomplete="off" onkeypress="this.style.backgroundColor = \'\';" onblur="autoFormatTime(this);"/></td>' . PHP_EOL . 
                                            '</tr>' . PHP_EOL;
                                
                                }
                                
                                echo '</table>' . PHP_EOL;
                                
                            }
                            else {
                                echo '<div style="font-style: italic; color: red;">Não há professores ativos.</div>' . PHP_EOL;
                                $noTeacherFlag = true;
                            }
                            
                            $result->close();
                            
                            ?>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%;" colspan="4">
                        <button type="submit" name="save" value="1" style="padding: 2px; width: 90px;" onclick="return validateInput();"<?php if ($noTeacherFlag) echo ' disabled="disabled"'; ?>><img src="../images/disk<?php echo ($noTeacherFlag ? '1' : '2'); ?>.png" style="vertical-align: middle;"/> Salvar</button>
                    </td>
                </tr>
            </table>
            </form>
            
        </div>
<?php
}
else {
?>
        <div class="panel" style="width: 800px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Adicionar Descontos ao Banco de Horas</span>
            <hr/>
            
            <div style="padding: 10px;">Selecione o Banco de Horas:
                <select id="selTB" name="tbid" onchange="styleSelectBox(this); redir(this);" onkeyup="styleSelectBox(this); redir(this);">
                    <option value="0" style="font-style: italic;">-Selecione-</option>
                    <?php

                    $result = $db->query('SELECT ID, `Year` FROM tb_banks WHERE Active = 1 ORDER BY `Year`');
                    
                    while($row = $result->fetch_assoc()){
                        echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . $row['Year'] . '</option>' . PHP_EOL;
                    }
                    
                    $result->close();

                    ?>
                </select>
            </div>
            
        </div>
<?php
}
?>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

//--------------------------------------------------

function getTwoDeepPost($ind1, $ind2){
    return isset($_POST[$ind1]) && isset($_POST[$ind1][$ind2]) ? $_POST[$ind1][$ind2] : null;
}

?>