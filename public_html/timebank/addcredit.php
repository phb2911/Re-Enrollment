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
$uid = getGet('uid');
$crId = getPost('rem');
$isValid = false;
$msg = null;
$desc = null;
$date1 = null;
$duration = null;
$cid = null;

// get time bank info
if (isNum($tbid) && $tbinfo = $db->query("SELECT `Year`, StartDate, EndDate FROM tb_banks WHERE ID = $tbid AND Active = 1")->fetch_assoc()){
    
    $tbYear = $tbinfo['Year'];
    
    // convert dates to dd/mm/yyyy format
    $tbStartDate = formatDate($tbinfo['StartDate']);
    $tbEndDate = formatDate($tbinfo['EndDate']);
    
    // get user info and set valid flag
    $isValid = !!(isset($uid) && isNum($uid) && $userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]);
    
}

// start session
session_start();

if ($isValid && isset($_SESSION['success'])){
    $msg = '<span style="font-style: italic; color: blue;">O crédito foi adicionado com sucesso.</span>';
    // remove all session variables
    session_unset();
}

if ($isValid && getPost('save') !== null){
    
    $desc = trim(getPost('desc'));
    $date1 = trim(getPost('date1'));
    $duration = trim(getPost('duration'));
    $cid = getPost('cid');
    
    // validate input
    if (!strlen($desc) || strlen($desc) > 200){
        $msg = '<span style="font-style: italic; color: red;">A descrição não é válido.</span>';
    }
    elseif (!isValidDate($date1)){
        $msg = '<span style="font-style: italic; color: red;">A data não é válida.</span>';
    }
    elseif (!isWithinPeriod($tbStartDate, $tbEndDate, $date1)){
        $msg = '<span style="font-style: italic; color: red;">A data deve estar entre \'' . $tbStartDate . '\' e \'' . $tbEndDate . '\'.</span>';
    }
    elseif (!validateShortTimeStr($duration)){
        $msg = '<span style="font-style: italic; color: red;">A duração não é válida.</span>';
    }
    elseif (isset($cid) && !isNum($cid)){
        $msg = '<span style="font-style: italic; color: red;">ID da turma não é válido.</span>';
    }
    else {
        
        // calculate the duration
        $dur = timeToNum($duration);
        
        // convert to DB date format
        $d1 = parseDate($date1);
        
        // check if the class id is nothing or zero
        if (!$cid) $cid = 'null';
        
        // insert data
        if ($db->query("INSERT INTO tb_credits (User, Bank, Description, `Date`, Duration, Class) VALUES ($uid, $tbid, '" . $db->real_escape_string($desc) . 
                "', '$d1', $dur, $cid)")){
            
            // this procedure prevents data from been added multiple times if user presses reload button
            
            // set session variable
            $_SESSION['success'] = '1';
            
            $db->close();
            header("Location: addcredit.php?tbid=$tbid&uid=$uid");
            die();
            
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    
}
elseif ($isValid && isset($crId)){
    
    // validate input
    if (isNum($crId)){
        
        // remove credit
        if ($db->query("DELETE FROM tb_credits WHERE ID = $crId")){
            // check if credit was found and removed
            if ($db->affected_rows){
                $msg = '<span style="font-style: italic; color: blue;">O crédito foi removido com sucesso.</span>';
            }
            else {
                $msg = '<span style="font-style: italic; color: red;">O crédito a ser removido não foi encontrado no banco de dados.</span>';
            }
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Error: Parametros inválidos. O crédito não foi removido.</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Adicionar Crédito</title>
    
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
            
            if (element('selTB')) initializeSelect(element('selTB'));
            if (element('selTeacher')) initializeSelect(element('selTeacher'));
            if (element('selClass')) initializeSelect(element('selClass'));
            
        };
        
        window.onclick = function(e) {
            if(document.getElementById('calendar') && !document.getElementById('calendar').contains(e.target) && e.target.id.substring(0,7) != 'txtDate') {
                hideCalendar();
            }
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27)){
                if (document.getElementById('calendar')) hideCalendar();
                hideHelpBox();
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
        
        function validateOpts(){
            
            if (element('selTB').selectedIndex == 0){
                alert('Por favor selecione o Banco de Horas.');
                return false;
            }
            
            if (element('selTeacher').selectedIndex == 0){
                alert('Por favor selecione o Professor.');
                return false;
            }
            
            return true;
            
        }
        
        function validateInput(){
            
            if (!element('txtDesc').value.trim().length){
                alert('Por favor digite a descrição.');
                element('txtDesc').focus();
                return false;
            }
            
            if (!isValidDate(element('txtDate1').value.trim())){
                alert('A data não é válida.');
                return false;
            }
            
            if (!validateShortTimeStr(element('txtDuration').value)){
                alert('A duração não é válida.');
                element('txtDuration').focus();
                return false;
            }
            
            return true;
            
        }
        
        function removeCredit(crId, tbId, uId){
            
            if (confirm('O crédito será removido permanentemente. Tem certeza?')){
                
                var form = document.createElement('form');
                var hid = document.createElement('input');
                
                form.action = 'addcredit.php?tbid=' + tbId + '&uid=' + uId;
                form.method = 'post';
                
                document.body.appendChild(form);
                
                hid.type = 'hidden';
                hid.name = 'rem';
                hid.value = crId;
                
                form.appendChild(hid);
                form.submit();
                
            }
                
        }
        
        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox1').style.visibility = 'visible';
        }
        
        function hideHelpBox(){
            element('overlay').style.visibility = 'hidden';
            element('helpBox1').style.visibility = 'hidden';
        }
        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="../images/banner3<?php echo ($isAdmin ? 'admin' : ''); ?>.jpg"/></a>
        
<?php

renderDropDown($db, $isAdmin);

if ($isValid){
?>
        <div id="msgBox" style="width: 700px; left: 0; right: 0; margin: auto; display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <br/>
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
        </div>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Adicionar Crédito ao Banco de Horas</span>
            <img src="../images/question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/>
            <hr/>
            
            <form method="post" action="addcredit.php<?php echo "?tbid=$tbid&uid=$uid"; ?>">
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Banco de Horas:</td>
                    <td style="width: 100%;">
                    <?php
                        echo '<span style="font-weight: bold;">' . $tbYear . '</span> <span style="font-style: italic;">(' . $tbStartDate . ' a ' . $tbEndDate . ')</span>';
                    ?>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?> &nbsp; <img src="../images/pencil1.png" style="cursor: pointer;" title="Selecionar outro professor" onclick="window.location = 'addcredit.php';"/></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Descrição:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtDesc" name="desc" value="<?php echo htmlentities($desc, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Turma:</td>
                    <td style="width: 100%;">
                        <select id="selClass" name="cid" style="width: 300px;">
                            <option value="0" style="font-style: italic;">-Selecione-</option>
<?php

// fetch classes
$result = $db->query("SELECT ID, Name FROM tb_classes WHERE User = $uid AND Bank = $tbid ORDER BY Name");

while ($row = $result->fetch_assoc()){
    
    echo '<option value="' . $row['ID'] . '" style="font-style: normal;"' . ($cid == $row['ID'] ? ' selected="selected"' : '') . '>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    
}

$result->close();

?>
                        </select>
                        <span style="font-style: italic;">(Opcional)</span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Data:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtDate1" name="date1" value="<?php echo htmlentities($date1, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Duração:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtDuration" name="duration" value="<?php echo htmlentities($duration, 3, 'ISO-8859-1'); ?>" style="width: 40px; text-align: right;" placeholder="00:00" maxlength="5" autocomplete="off" onblur="autoFormatTime(this);"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;"></td>
                    <td style="width: 100%;">
                        <button type="submit" name="save" value="1" style="padding: 2px; width: 90px;" onclick="return validateInput();"><img src="../images/disk2.png" style="vertical-align: middle;"/> Salvar</button>
                    </td>
                </tr>
            </table>
            </form>
            
        </div>
<?php

    $result = $db->query("SELECT ID, crDescWithClass(ID) AS Description, `Date`, Duration FROM tb_credits WHERE Bank = $tbid AND User = $uid ORDER BY `Date`");
    
    if ($result->num_rows){
        
        $months = array('Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
        $curMon = 0;
        
?>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Créditos</span>
            <hr/>
<?php

        while ($row = $result->fetch_assoc()){
            
            $desc = $row['Description'];
            $duration = $row['Duration'];
            $numDate = strtotime($row['Date']);
            $yr = date('Y', $numDate);
            $mon = date('n', $numDate);
            $day = date('j', $numDate);
            
            if ($curMon != $mon){
                
                if ($curMon != 0) echo '</table><br/>' . PHP_EOL;
                
                echo '<table class="tbl" style="width: 100%;"><tr style="background-color: #1b9a87;"><td style="font-weight: bold;" colspan="4">' . $months[$mon - 1] . '/' . $yr . '</td></tr>' . PHP_EOL .
                        '<tr style="background-color: #85c4bb;"><td style="width: 10%; text-align: center;">Dia</td><td style="width: 75%;">Descrição</td><td style="width: 15%; text-align: center;">Duração</td><td>&nbsp;</td></tr>' . PHP_EOL;
                
                $curMon = $mon;
                $bgcolor = '';
                
            }
            
            $bgcolor = ($bgcolor == '#c6c6c6') ? '#f1f1f1' : '#c6c6c6';
            
            echo '<tr style="background-color: ' . $bgcolor . ';"><td style="text-align: center;">' . str_pad($day, 2, '0', STR_PAD_LEFT) . '</td><td>' . 
                    htmlentities($desc, 0, 'ISO-8859-1') . '</td><td style="text-align: center;">' . numToTime($duration) . 
                    '</td><td style="white-space: nowrap;"><img src="../images/recycle2.png" title="Remover" style="cursor: pointer;" onclick="removeCredit(' . 
                    $row['ID'] . ', ' . $tbid . ', ' . $uid .');"/></td></tr>' . PHP_EOL;
            
        }
        
        echo '</table>' . PHP_EOL;

?>
        </div>
<?php
        
    }
    
    $result->close();

}
else {
    selectTB();
}

?>

    </div>
    
    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay" onclick="hideHelpBox();"></div>
    <div class="helpBox" id="helpBox1" style="width: 500px; height: 300px;">
        <div class="closeImg" onclick="hideHelpBox();"></div>
        <span style="font-weight: bold;">Ajuda - Adicionar Crédito ao Banco de Horas</span>
        <hr/>
        <div style="padding: 5px;">
            <span style="color: red; font-weight: bold;">Atenção:</span> Crédito refere-se ao que foi pago ao professor e este deverá compensar posteriormente.
            Por exemplo: uma aula cancelada por motivo não justificável.<br/><br/>
            Para adicionar crédito ao banco de horas, siga os seguinte passos: preencha os campos <span style="font-style: italic;">'Descrição'</span>,
            <span style="font-style: italic;">'Data'</span> e <span style="font-style: italic;">'Duração'</span>, observando os formatos
            indicados, e, em seguida, pressione o botão <span style="font-style: italic;">'Salvar'</span>.<br/><br/>
            Clique em <img src="../images/pencil1.png"/> para modificar o professor.<br/><br/>
            Os créditos não podem ser editados. Caso haja alguma erro, remova o mesmo e crie outro com as informações corretas.
        </div>
    </div>
    
</body>
</html>
<?php

$db->close();

//----------------------------------------------------------

function selectTB(){
    
    global $db;
    
    // get active banks
    // $tbs[ID] = Year;
    $tbs = array();
    
    $result = $db->query("SELECT ID, `Year` FROM tb_banks WHERE Active = 1 ORDER BY `Year`");
    
    while ($row = $result->fetch_assoc()){
        $tbs[$row['ID']] = $row['Year'];
    }
    
    $result->close();
    
    if (count($tbs)){
        
?>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Adicionar Crédito ao Banco de Horas</span>
            <hr/>
            
            <form method="get" action="addcredit.php">
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Banco de Horas:</td>
                    <td style="width: 100%;">
                        <select id="selTB" name="tbid">
                            <option value="0" style="font-style: italic;">-Selecione-</option>
                            <?php
                            
                            foreach ($tbs as $tid => $year) {
                                echo '<option value="' . $tid . '" style="font-style: normal;">' . $year . '</option>' . PHP_EOL;
                            }
                            
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%;">
                        <select id="selTeacher" name="uid" style="width: 300px;">
                            <option value="0" style="font-style: italic;">-Selecione-</option>
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
            </form>
            
        </div>
<?php
        
    }
    else {
?>
        <br/>
        <div style="width: 500px; left: 0; right: 0; margin: auto; background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
            <span style="color: red; font-style: italic;">Não foi encontrado nenhum Banco de Horas ativo.</span>
        </div>
<?php
    }
    
}

?>