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
    eader("Location: " . LOGIN_PAGE);
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
$isValid = false;
$isPostBack = (getPost('save') === '1');
$nextIndexFlag = 3;
$clsArr = array();
$msg = null;
$tbStartDate = null;
$tbEndDate = null;

// get time bank info
if (isNum($tbid) && $tbinfo = $db->query("SELECT `Year`, StartDate, EndDate FROM tb_banks WHERE ID = $tbid AND Active = 1")->fetch_assoc()){
    
    $tbYear = $tbinfo['Year'];
    
    // convert dates to dd/mm/yyyy format
    $tbStartDate = formatDate($tbinfo['StartDate']);
    $tbEndDate = formatDate($tbinfo['EndDate']);
    
    // get user info and set valid flag
     if (isset($uid) && isNum($uid) && $userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]){
        
        // fetch classes from user
        $result = $db->query("SELECT ID, Name FROM tb_classes WHERE User = $uid AND Bank = $tbid ORDER BY Name");

        // store classes in array
        // $clsArr[ID] = Name
        while ($row = $result->fetch_assoc()){
            $clsArr[$row['ID']] = $row['Name'];
        }

        $result->close();
        
        $isValid = true;
        
     }
     
}

// start session
session_start();

if ($isValid && getSession('success_dt') !== null){
    $msg = '<span style="font-style: italic; color: blue;">Desconto(s) adicionado(s) com sucesso.</span>';
    // remove all session variables
    session_unset();
}

$dateArr = getPost('date');
$disId = getPost('rem');
$errFlag = false;
$emptyFieldFlag = true;
    
if ($isValid && $isPostBack){
    
    $q = "INSERT INTO tb_discounts (User, Bank, Description, `Date`, Duration, Class) VALUES ";
    
    if (is_array($dateArr)) {
    
        // set next index flag
        // retrieve the largest key index and add 1
        $nextIndexFlag = max(array_keys($dateArr)) + 1;

        foreach ($dateArr as $index => $dt) {

            $dt = trim($dt);
            $desc = trim(getPostTwoDeep('desc', $index));
            $dur = trim(getPostTwoDeep('dur', $index));
            $clsId = trim(getPostTwoDeep('cid', $index));

            // check if at least one field is not empty.
            if (strlen($dt) || strlen($desc) || strlen($dur)){

                // validate input
                if (!strlen($desc) || !isValidDate($dt) || !isWithinPeriod($tbStartDate, $tbEndDate, $dt) || !validateShortTimeStr($dur)){
                    $errFlag = true;
                    break;
                }
                else {
                    // build query string

                    if (!$emptyFieldFlag){
                        $q .= ",";
                    }

                    // convert to DB date format
                    $d = parseDate($dt);

                    // calculate the duration
                    $tmp = explode(':', $dur);

                    $dr = (intval($tmp[0], 10) * 60) + intval($tmp[1], 10);

                    // check if class id exists in class array
                    // if not set to null
                    if (!isNum($clsId) || !isset($clsArr[$clsId])){
                        $clsId = 'null';
                    }

                    $q .= "($uid, $tbid, '" . $db->real_escape_string($desc) . "', '$d', $dr, $clsId)";

                }

                // set flag
                $emptyFieldFlag = false;

            }

        }
    
    }
    
    if ($errFlag){
        // invalid field found
        $msg = '<span style="font-style: italic; color: red;">Há campos inválidos. Verifique os dados inseridos e tente novamente.</span>';
    }
    elseif ($emptyFieldFlag){
        // all fields are empty
        $msg = '<span style="font-style: italic; color: red;">Por favor preencha pelo menos um campo.</span>';
    }
    elseif ($db->query($q)) {
        
        // this procedure prevents data from been added multiple times if user presses reload button
            
        // set session variable
        $_SESSION['success_dt'] = '1';

        $db->close();
        header("Location: adddisc.php?tbid=$tbid&uid=$uid");
        die();
        
    }
    else {
        // db error
        $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
    }
    
}
elseif ($isValid && isset($disId)){
    
    // validate input
    if (isNum($disId)){
        
        // remove discount
        if ($db->query("DELETE FROM tb_discounts WHERE ID = $disId")){
            // check if discount was found and removed
            if ($db->affected_rows){
                $msg = '<span style="font-style: italic; color: blue;">O desconto foi removido com sucesso.</span>';
            }
            else {
                $msg = '<span style="font-style: italic; color: red;">O desconto a ser removido não foi encontrado no banco de dados.</span>';
            }
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Error: Parametros inválidos. O desconto não foi removido.</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Adicionar Descontos</title>
    
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
        
        img.closeIcon {
            opacity: 0.5;
        }
        
        img.closeIcon:hover {
            cursor: pointer;
            opacity: 1;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            var sels = document.getElementsByClassName('selCls');
            
            for (var i = 0; i < sels.length; i++){
                initializeSelect(sels[i]);
            }
            
            if (element('selTB')) initializeSelect(element('selTB'));
            if (element('selTeacher')) initializeSelect(element('selTeacher'));
            
            <?php if ($errFlag || $emptyFieldFlag) echo "validateInput(false);"; ?>
        
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
        
        function validateInput(displayMessage){
            
            // get row collection
            var rows = document.getElementsByClassName('dataRow');
            var emptyFlag = true;
            var errorFlag = false;
            var i, index, dateVal, descVal, durVal;
            
            // iterate through rows
            for (i = 0; i < rows.length; i++){
                
                // extract index
                index = rows[i].id.substring(2);
                
                // get data from fields
                dateVal = element('txtDate' + index).value.trim();
                descVal = element('txtDesc' + index).value.trim();
                durVal = element('txtDur' + index).value;
                
                // check if at least one field is not empty
                if (dateVal.length || descVal.length || durVal.length){
                    
                    // set flag
                    emptyFlag = false;
                    
                    // validate data
                    if (!isValidDate(dateVal) || !isRangeWithinRange(<?php echo "'$tbStartDate', '$tbEndDate'"; ?>, dateVal)){
                        errorFlag = true;
                        element('txtDate' + index).style.backgroundColor = '#ffb5a8';
                    }
                    
                    if (!descVal.length){
                        errorFlag = true;
                        element('txtDesc' + index).style.backgroundColor = '#ffb5a8';
                    }
                    
                    if (!validateShortTimeStr(durVal)){
                        errorFlag = true;
                        element('txtDur' + index).style.backgroundColor = '#ffb5a8';
                    }
                                        
                }
                
            }
            
            // display error message
            if (displayMessage && emptyFlag) alert('Por favor preencha pelo menos um campo.');
            if (displayMessage && errorFlag) alert('Valor(es) inválido(s). Por favor verifique os campos e tente novamente.');
            
            // returns true if no error found, false otherwise.
            return (!emptyFlag && !errorFlag);
            
        }
        
        function removeDiscount(disId, tbId, uId){
            
            if (confirm('O desconto será removido permanentemente. Tem certeza?')){
                
                var form = document.createElement('form');
                var hid = document.createElement('input');
                
                form.action = 'adddisc.php?tbid=' + tbId + '&uid=' + uId;
                form.method = 'post';
                
                document.body.appendChild(form);
                
                hid.type = 'hidden';
                hid.name = 'rem';
                hid.value = disId;
                
                form.appendChild(hid);
                form.submit();
                
            }
                
        }
        
        function removeRow(index){
            
            // check number of rows before delete
            if (document.getElementsByClassName('dataRow').length == 1){
                alert('Este campo não pode ser removido.');
            }
            else {
                element('tr' + index).kill();
            }
            
        }
        
        // global var used in addRow()
        // to keep track of next field's index
        var nextIndex = <?php echo $nextIndexFlag; ?>;
        
        function addRow(){
            
            // create table row
            var tr = document.createElement('tr');
            tr.id = 'tr' + nextIndex;
            tr.className = 'dataRow';
            
            // create table cells
            var td1 = document.createElement('td');
            var td2 = document.createElement('td');
            var td3 = document.createElement('td');
            var td4 = document.createElement('td');
            var td5 = document.createElement('td');
            
            td5.style.paddingLeft = '0';
            
            // append cells to row
            tr.appendChild(td1);
            tr.appendChild(td2);
            tr.appendChild(td3);
            tr.appendChild(td4);
            tr.appendChild(td5);
            
            // create date textbox
            var input1 = document.createElement('input');
            input1.type = 'text';
            input1.id = 'txtDate' + nextIndex;
            input1.name = 'date[' + nextIndex + ']';
            input1.style.width = '100px';
            input1.placeholder = 'dd/mm/aaaa';
            input1.maxlength = '10';
            input1.autocomplete = 'off';
            input1.onclick = function(){showCal(this);};
            input1.onfocus = function(){showCal(this); this.style.backgroundColor= '';};
            
            // append textbox to first cell
            td1.appendChild(input1);
            
            // create description textbox
            var input2 = document.createElement('input');
            input2.type = 'text';
            input2.id = 'txtDesc' + nextIndex;
            input2.name = 'desc[' + nextIndex + ']';
            input2.style.width = '100%';
            input2.maxlength = '200';
            input2.onfocus = function(){this.style.backgroundColor= '';};
            
            // append textbox to second cell
            td2.appendChild(input2);
            
            // create class select
            var sel = document.createElement('select');
            sel.className = 'selCls';
            sel.id = 'selClass' + nextIndex;
            sel.name = 'cid[' + nextIndex + ']';
            sel.style.width = '250px';
            
            // add options
            var opt = document.createElement("option");
            opt.text = '- Selecione -';
            opt.value = '0';
            
            sel.add(opt);
            
<?php

foreach ($clsArr as $cid => $clsName){
?>
            opt = document.createElement("option");
            opt.text = '<?php echo str_replace("'", "\'", $clsName); ?>';
            opt.value = '<?php echo $cid; ?>';
            
            sel.add(opt);
            
<?php
}

?>
            
            initializeSelect(sel);
            
            // append textbox to third cell
            td3.appendChild(sel);
            
            // create duration textbox
            var input3 = document.createElement('input');
            input3.type = 'text';
            input3.id = 'txtDur' + nextIndex;
            input3.name = 'dur[' + nextIndex + ']';
            input3.style.width = '100%';
            input3.style.textAlign = 'right';
            input3.placeholder = '00:00';
            input3.maxlength = '5';
            input3.autocomplete = 'off';
            input3.onfocus = function(){this.style.backgroundColor= '';};
            input3.onblur = function(){autoFormatTime(this)};
            
            // append textbox to fourth cell
            td4.appendChild(input3);
            
            // cloning nextIndex is necessary
            // or else the function removeRow()
            // will always access the value of
            // nextIndex.
            var curInd = nextIndex + 0;
            
            // create delete row icon
            var icon = document.createElement('img');
            icon.className = 'closeIcon';
            icon.src = '../images/close.png';
            icon.style.verticalAlign = 'middle';
            icon.title = 'Fechar';
            icon.onclick = function(){removeRow(curInd);};
            
            // append icon to fifth cell
            td5.appendChild(icon);
            
            // append row to table
            element('mainTable').appendChild(tr);
            
            // increment next index
            nextIndex++;
            
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
        <div id="msgBox" style="width: 900px; left: 0; right: 0; margin: auto; display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <br/>
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
        </div>
        <br/>
        <div class="panel" style="width: 900px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Adicionar Descontos ao Banco de Horas</span>
            <img src="../images/question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/>
            <hr/>
            
            <form method="post" action="adddisc.php<?php echo "?tbid=$tbid&uid=$uid"; ?>">
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
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?> &nbsp; <img src="../images/pencil1.png" style="cursor: pointer;" title="Selecionar outro professor" onclick="window.location = 'adddisc.php';"/></td>
                </tr>
            </table>
            <fieldset style="border-radius: 5px;">
                <table class="tbl" id="mainTable" style="width: 100%;">
                    <tr>
                        <td style="white-space: nowrap; padding-bottom: 0;">Data</td>
                        <td style="white-space: nowrap; padding-bottom: 0; width: 100%;">Descrição</td>
                        <td style="white-space: nowrap; padding-bottom: 0;">Turma <span style="font-style: italic;">(Opcional)</span></td>
                        <td style="white-space: nowrap; width: 100%; padding-bottom: 0;">Duração</td>
                        <td><img src="../images/trans.png" style="width: 16px; height: 16px;"></td>
                    </tr>
<?php

// print rows
if ($isPostBack){
    if (is_array($dateArr)) {
        foreach ($dateArr as $index => $dt) {

            $data = array(
                'date' => $dt,
                'desc' => getPostTwoDeep('desc', $index),
                'cid' => getPostTwoDeep('cid', $index),
                'dur' => getPostTwoDeep('dur', $index)
                    );

            printTableRow($index, $clsArr, $data);

        }
    }
}
else {
    for ($i = 0; $i < 3; $i++){
        printTableRow($i, $clsArr);
    }
}

?>
                </table>
                <div style="padding: 5px;">
                    <button type="button" style="padding: 2px;" onclick="addRow();"><img src="../images/plus.png"/> Adicionar Campo</button>
                </div>
            </fieldset>
            <div style="padding-top: 5px;">
                <button type="submit" name="save" value="1" style="padding: 2px; width: 90px;" onclick="return validateInput(true);"><img src="../images/disk2.png" style="vertical-align: middle;"/> Salvar</button>
            </div>
            </form>
            
        </div>
<?php

    $result = $db->query("SELECT ID, disDescWithClass(ID) AS Description, `Date`, Duration FROM tb_discounts WHERE Bank = $tbid AND User = $uid ORDER BY `Date`");
    
    if ($result->num_rows){
        
        $months = array('Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
        $curMon = 0;
        
?>
        <br/>
        <div class="panel" style="width: 900px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Descontos</span>
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
                    htmlentities($desc, 0, 'ISO-8859-1') . '</td><td style="text-align: center;">' . numToTime($duration) . '</td>' .
                    '<td style="white-space: nowrap;"><img src="../images/recycle2.png" title="Remover" style="cursor: pointer;" onclick="removeDiscount(' . 
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
    <div class="helpBox" id="helpBox1" style="width: 600px; height: 355px;">
        <div class="closeImg" onclick="hideHelpBox();"></div>
        <span style="font-weight: bold;">Ajuda - Adicionar Crédito ao Banco de Horas</span>
        <hr/>
        <div style="padding: 5px;">
            <span style="color: red; font-weight: bold;">Atenção:</span> Desconto refere-se ao que foi abatido do banco de horas pelo professor.
            Por exemplo: aulas de reposição ou reforço, reuniões pedagógicas, talk sessions, etc.<br/><br/>
            Para adicionar descontos ao banco de horas, siga os seguinte passos:
            <ul>
                <li>Preencha os campos <span style="font-style: italic;">'Data'</span>,
                    <span style="font-style: italic;">'Descrição'</span> e <span style="font-style: italic;">'Duração'</span>, observando os formatos
                    indicados.</li>
                <li>Selecione a turma relacionada ao crédito (opcional).</li>
                <li>pressione o botão <span style="font-style: italic;">'Salvar'</span>.</li>
            </ul>
            <span style="color: red; font-weight: bold;">Importante:</span> Certifique-se que a data inserida esteja dentro do período do banco de
            horas atual.<br/><br/>
            Clique em <img src="../images/pencil1.png"/> para modificar o professor.<br/><br/>
            Os descontos não podem ser editados. Caso haja alguma erro, remova o mesmo e crie outro com as informações corretas.
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
            
            <span style="font-weight: bold;">Adicionar Descontos ao Banco de Horas</span>
            <hr/>
            
            <form method="get" action="adddisc.php">
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

//----------------------------------------------------------

function printTableRow($index, $clsArr, $data = null){
?>
                    <tr class="dataRow" id="tr<?php echo $index; ?>">
                        <td>
                            <input type="text" id="txtDate<?php echo $index; ?>" name="date[<?php echo $index; ?>]" value="<?php echo htmlentities($data['date'], 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this); this.style.backgroundColor = '';"/>
                        </td>
                        <td>
                            <input type="text" id="txtDesc<?php echo $index; ?>" name="desc[<?php echo $index; ?>]" value="<?php echo htmlentities($data['desc'], 3, 'ISO-8859-1'); ?>" style="width: 100%;" maxlength="200" onfocus="this.style.backgroundColor = '';"/>
                        </td>
                        <td>
                            <select class="selCls" id="selClass<?php echo $index; ?>" name="cid[<?php echo $index; ?>]" style="width: 250px;">
                                <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    foreach ($clsArr as $cid => $clsName){
        echo '<option value="' . $cid . '" style="font-style: normal;"' . ($cid == $data['cid'] ? ' selected="selected"' : '') . '>' . htmlentities($clsName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }

?>
                            </select>
                        </td>
                        <td>
                            <input type="text" id="txtDur<?php echo $index; ?>" name="dur[<?php echo $index; ?>]" value="<?php echo htmlentities($data['dur'], 3, 'ISO-8859-1'); ?>" style="width: 100%; text-align: right;" placeholder="00:00" maxlength="5" autocomplete="off" onfocus="this.style.backgroundColor = '';" onblur="autoFormatTime(this);"/>
                        </td>
                        <td style="padding-left: 0;"><img class="closeIcon" src="../images/close.png" style="vertical-align: middle;" title="Apagar" onclick="removeRow(<?php echo $index; ?>);"/></td>
                    </tr>
<?php
}

//----------------------------------------

function getPostTwoDeep($index, $index2){
    return (isset($_POST[$index]) && isset($_POST[$index][$index2]) ? $_POST[$index][$index2] : null);
}

?>