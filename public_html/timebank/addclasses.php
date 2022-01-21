<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/date_functions.php';
require_once '../genreq/genreq.php';
require_once 'require/createCheckFlagArray.php';
require_once 'require/validateData.php';

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
$isValid = false;

// get time bank info
if (isNum($tbid) && $tbinfo = $db->query("SELECT `Year`, StartDate, EndDate FROM tb_banks WHERE ID = $tbid AND Active = 1")->fetch_assoc()){
    
    $tbYear = $tbinfo['Year'];
    
    // convert dates to dd/mm/yyyy format
    $tbStartDate = formatDate($tbinfo['StartDate']);
    $tbEndDate = formatDate($tbinfo['EndDate']);
    
    // get user info and set valid flag
    $isValid = !!(isNum($uid) && $userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]);
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Adicionar Turmas</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="../js/general.js"></script>
    <script type="text/javascript" src="../js/dateFunctions.js"></script>
    <link href="../calendar/calendar.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="../calendar/calendar.js"></script>
    <script type="text/javascript" src="js/inscls_validation.js"></script>
       
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
            if (element('selMod')) initializeSelect(element('selMod'));
            
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
            
            // function from inscls_validation.js
            return inscls_validate(element('txtName').value, element('selSemester').selectedIndex, element('txtDate1').value, element('txtDate2').value, element('txtDate3').value, element('txtDate4').value, element('selMod').selectedValue(), document.getElementsByClassName('chkDays'));
            
        }
        
        function showHelpBox(index){
            element('overlay').style.visibility = 'visible';
            element('helpBox' + index).style.visibility = 'visible';
        }
                
        function hideHelpBox(){
            
            var helpBoxes = document.getElementsByClassName('helpBox');
            
            for (var i = 0; i < helpBoxes.length; i++){
                helpBoxes[i].style.visibility = 'hidden';
            }
            
            element('overlay').style.visibility = 'hidden';
            
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
    addClass($tbid, $tbYear, $tbStartDate, $tbEndDate, $uid, $userName);
}
else {
    selectTB();
}

?>
        
    </div>
    
    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay" onclick="hideHelpBox();"></div>
    <div class="helpBox" id="helpBox1" style="width: 500px; height: 295px;">
        <div class="closeImg" onclick="hideHelpBox();"></div>
        <span style="font-weight: bold;">Ajuda - Crédito por Aula</span>
        <hr/>
        <div style="padding: 5px;">Trata-se dos minutos que serão adicionados por cada dia de aula ao banco de horas caso o tempo em sala da respectiva turma seja inferior às horas pagas.<br/><br/>
        Por exemplo, se a aula tem duração de 1:15 e o valor pago for de 1:30, o crédito por aula será de 15 minutos.<br/><br/>
        Caso o campo seja deixado em branco, o valor considerado será zero.<br/><br/>
        <span style="color: red;">Atenção:</span> É importante notar que este crédito será calculado por aula, ou seja, se a turma tem duas aulas por semana, este valor será o dobro por semana.</div>
    </div>
    <div class="helpBox" id="helpBox2" style="width: 500px; height: 170px;">
        <div class="closeImg" onclick="hideHelpBox();"></div>
        <span style="font-weight: bold;">Ajuda - Duração da Aula</span>
        <hr/>
        <div style="padding: 5px;">O período de duração da aula corresponde ao tempo de cada aula em que o professor permanece em sala.<br/><br/>
            Se uma turma tem duas aulas por semana, cada aula com período correspondente a 1:15, o valor inserido deverá ser 1:15 e não 2:30 correspondeten ao período semanal.</div>
    </div>
    <div class="helpBox" id="helpBox3" style="width: 500px; height: 330px;">
        <div class="closeImg" onclick="hideHelpBox();"></div>
        <span style="font-weight: bold;">Ajuda - Período Pago e Período de Aula</span>
        <hr/>
        <div style="padding: 5px;">O <span style="font-style: italic;">'Período Pago'</span> corresponde ao período em que o professor será pago pela turma, enquanto que o <span style="font-style: italic;">'Período de Aula'</span> corresponde ao período entre o primeiro e o último dia de aula.<br/><br/>
        Por exemplo, se uma determinada turma será paga ao professor entre os meses de fevereiro e junho, o período pago será de 01 de fevereiro a 30 de junho. Caso a mesma turma inicie as aulas no dia 02 de fevereiro e finalize no dia 20 de junho, este será o período de aula.<br/><br/>
        <span style="color: red;">Atenção:</span>
        <ul>
            <li>Caso o período pago se estenda ao mês de janeiro, obrigatótiamente o dia deverá ser 31.</li>
            <li>A data inicial do período pago não pode ser em janeiro.</li>
            <li>O período de aula não poderá se estender ao mês de janeiro.</li>
        </ul>
        </div>
    </div>
    
</body>
</html>
<?php

$db->close();

//----------------------------------------------------------

function addClass($tbid, $tbYear, $tbStartDate, $tbEndDate, $uid, $userName){
    
    global $db;
    
    $days = 0;
    $msg = null;
    $newName = null;
    $semester = null;
    $date1 = null;
    $date2 = null;
    $date3 = null;
    $date4 = null;
    $newDuration = null;
    $excMin = null;
    $mode = null;
        
    // button save was clicked
    if (getPost('save') !== null){
        
        // get submitted data and attribute to variables
        $newName = trim(getPost('name'));
        $semester = getPost('semester');
        $date1 = trim(getPost('date1'));
        $date2 = trim(getPost('date2'));
        $date3 = trim(getPost('date3'));
        $date4 = trim(getPost('date4'));
        $mode = getPost('mod');
        
        // add the days submitted
        $daysArr = getPost('days');
        $daysCount = 0;
        
        if (isset($daysArr) && is_array($daysArr)) {
            
            foreach ($daysArr as $key => $val){
                if (isNum($key) && ($key == 1 || $key == 2 || $key == 4 || $key == 8 || $key == 16 || $key == 32 || $key == 64) && $val === '1'){
                    $days += intval($key, 10);
                    $daysCount++;
                }
            }
            
        }
        
        //$newDuration = trim(getPost('duration'));
        //$excMin = trim(getPost('excmin'));
        
        // validate submitted data
        if (validateData($newName, $semester, $tbStartDate, $tbEndDate, $date1, $date2, $date3, $date4, $mode, $daysCount, $msg)) {
            
            // converts time format to number of minuts
            //$duration = timeToNum($newDuration);
            // set excess minuts if necessary
            //if (!strlen($excMin)) $excMin = 0;
            
            // set duration and excess minutes according to the selected mode
            if ($mode == 1){
                $newDuration = '01:15';
                $excMin = 0;
            }
            elseif ($mode == 2){
                $newDuration = '02:15';
                $excMin = 15;
            }
            else {
                $newDuration = '02:30';
                $excMin = 0;
            }
            
            $duration = timeToNum($newDuration); // duration in minuts
            
            // convert to DB date format
            $d1 = parseDate($date1);
            $d2 = parseDate($date2);
            $d3 = parseDate($date3);
            $d4 = parseDate($date4);
            
            // generate querey string
            $q = "INSERT INTO tb_classes (User, Name, Days, Duration, ExcessMinutes, StartDate, EndDate, StartClass, EndClass, Bank, Semester) Values ($uid, '" . 
                    $db->real_escape_string($newName) . "', $days, $duration, $excMin, '$d1', '$d2', '$d3', '$d4', $tbid, $semester)";
            
            // insert data
            if ($db->query($q)){
                $msg = '<span style="font-style: italic; color: blue;">A turma foi criada com sucesso.</span>';
                $newClsId = $db->insert_id;
            }
            else {
                $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
            }
            
        }
        else {
            // format error message
            $msg = '<span style="font-style: italic; color: red;">' . $msg . '</span>';
        }
        
    }
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
            
            <span style="font-weight: bold;">Adicionar Turmas ao Banco de Horas</span>
            <hr/>
            
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
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?></td>
                </tr>
            </table>
            
        </div>
        <br/>
<?php
    // new class created successfully
    if (isset($newClsId)){
?>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
    
            <span style="font-weight: bold;">Nova Turma</span>
            <hr/>
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Nome:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($newName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Semestre:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $tbYear . '.' . $semester; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Período Pago:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($date1, 0, 'ISO-8859-1'); ?> <span style="font-weight: normal;">a</span> <?php echo htmlentities($date2, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Período de Aula:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($date3, 0, 'ISO-8859-1'); ?> <span style="font-weight: normal;">a</span> <?php echo htmlentities($date4, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Dias da Semana:</td>
                    <td style="width: 100%; font-weight: bold;">
                        <?php
                        
                        $dArr = array(1 => 'Sab', 2 => 'Sex', 4 => 'Qui', 8 => 'Qua', 16 => 'Ter', 32 => 'Seg', 64 => 'Dom');
                        $sl = false;
                        $tempDays = $days;
                        
                        for ($i = 64; $i >= 1; $i /= 2){
                            
                            if ($tempDays == 0) break;
                            
                            if ($tempDays >= $i){
                                
                                if ($sl) echo '/';
                                
                                echo $dArr[$i];
                                
                                $tempDays -= $i;
                                $sl = true;
                                
                            }
                            
                        }
                                                
                        ?>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Duração da Aula:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($newDuration, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Crédito por Aula:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($excMin, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="width: 100%; font-weight: bold;" colspan="2">
                        <button type="button" onclick="window.location = 'addclasses.php<?php echo "?tbid=$tbid&uid=$uid"; ?>';"><img src="../images/plus.png"/> Adicionar Outra Turma</button>
                        <button type="button" onclick="window.location = 'editclass.php?cid=<?php echo $newClsId; ?>';"><img src="../images/pencil1.png"/> Editar Turma</button>
                        <button type="button" onclick="window.location = 'addclasses.php';"><img src="../images/person.png"/> Escolher Outro Professor</button>
                    </td>
                </tr>
            </table>
            
        </div>
<?php
    }
    else {
        // display new class form
?>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Turmas Existentes</span>
            <hr/>
            
<?php
        
        // show teacher's existing classes
        $result = $db->query("SELECT ID, Name, Semester FROM tb_classes WHERE Bank = $tbid AND User = $uid ORDER BY Semester, Name");

        if ($result->num_rows){
        
?>
            <table class="tbl" style="width: 100%; border-collapse: collapse; border: black solid 1px;">
                <tr style="background-color: #1b9a87; color: white">
                    <td style="width: 80%;">Turma</td>
                    <td style="width: 20%; text-align: center;">Semestre</td>
                </tr>
<?php

            $bgcolor = null;

            while ($row = $result->fetch_assoc()){

                $bgcolor = ($bgcolor == '#e1e1e1') ? '#ffffff' : '#e1e1e1';
            
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td><?php echo '<a href="classdetails.php?cid=' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a>'; ?></td>
                    <td style="text-align: center;"><?php echo $tbYear . '.' . $row['Semester']; ?></td>
                </tr>
<?php
            }
?>
            </table>
<?php
        
        }
        else {
            echo '<div style="padding: 10px; color: red; font-style: italic; font-size: 14px;">*Não há turmas deste professor no Banco de Horas ' . $tbYear . '.</div>' . PHP_EOL;
        }

        $result->close();
        
        // set checkboxes checked flag
        $chkFlag = createCheckFlagArray($days);

?>
        </div>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
    
            <span style="font-weight: bold;">Nova Turma</span>
            <hr/>
            
            <form method="post" action="addclasses.php<?php echo "?tbid=$tbid&uid=$uid"; ?>">
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Nome:</td>
                    <td style="width: 100%;">
                        <input id="txtName" type="text" name="name" value="<?php echo htmlentities($newName, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="55"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Semestre:</td>
                    <td style="width: 100%;">
                        <?php echo $tbYear; ?>.<select id="selSemester" name="semester">
                            <option value="0"></option>
                            <option value="1"<?php if ($semester == 1) echo ' selected="selected"'; ?>>1</option>
                            <option value="2"<?php if ($semester == 2) echo ' selected="selected"'; ?>>2</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Período Pago:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtDate1" name="date1" value="<?php echo htmlentities($date1, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                        a
                        <input type="text" id="txtDate2" name="date2" value="<?php echo htmlentities($date2, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                        <img src="../images/question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox(3);"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Período de Aula:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtDate3" name="date3" value="<?php echo htmlentities($date3, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                        a
                        <input type="text" id="txtDate4" name="date4" value="<?php echo htmlentities($date4, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Modalidade:</td>
                    <td style="width: 100%;">
                        <select id="selMod" name="mod">
                            <option style="font-style: italic;" value="0">- Selecione -</option>
                            <option style="font-style: normal;" value="1"<?php if ($mode == 1) echo ' selected="selected"'; ?>>Duas aulas de 1:15</option>
                            <option style="font-style: normal;" value="2"<?php if ($mode == 2) echo ' selected="selected"'; ?>>Uma aula de 2:15</option>
                            <option style="font-style: normal;" value="3"<?php if ($mode == 3) echo ' selected="selected"'; ?>>Uma aula de 2:30</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Dias da Semana:</td>
                    <td style="width: 100%;">
                        <input type="checkbox" class="chkDays" id="chkDays64" name="days[64]" value="1"<?php if ($chkFlag[0]) echo ' checked="checked"'; ?>/><label for="chkDays64"> Dom</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays32" name="days[32]" value="1"<?php if ($chkFlag[1]) echo ' checked="checked"'; ?>/><label for="chkDays32"> Seg</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays16" name="days[16]" value="1"<?php if ($chkFlag[2]) echo ' checked="checked"'; ?>/><label for="chkDays16"> Ter</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays8" name="days[8]" value="1"<?php if ($chkFlag[3]) echo ' checked="checked"'; ?>/><label for="chkDays8"> Qua</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays4" name="days[4]" value="1"<?php if ($chkFlag[4]) echo ' checked="checked"'; ?>/><label for="chkDays4"> Qui</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays2" name="days[2]" value="1"<?php if ($chkFlag[5]) echo ' checked="checked"'; ?>/><label for="chkDays2"> Sex</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays1" name="days[1]" value="1"<?php if ($chkFlag[6]) echo ' checked="checked"'; ?>/><label for="chkDays1"> Sab</label>
                    </td>
                </tr>
                <!--
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Duração da Aula:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtDuration" name="duration" value="<?php echo htmlentities($newDuration, 3, 'ISO-8859-1'); ?>" style="width: 40px; text-align: right;" placeholder="00:00" maxlength="5" autocomplete="off" onblur="autoFormatTime(this);"/>
                        <img src="../images/question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox(2);"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Crédito por Aula:</td>
                    <td style="width: 100%;">
                        <input id="txtExcMin" type="text" name="excmin" value="<?php echo htmlentities($excMin, 3, 'ISO-8859-1'); ?>" style="width: 40px; text-align: right;" maxlength="3"/>
                        <img src="../images/question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox(1);"/>
                    </td>
                </tr>
                -->
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
    }
    
}

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
            
            <span style="font-weight: bold;">Adicionar Turmas</span>
            <hr/>
            
            <form method="get" action="addclasses.php">
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
                        <input type="submit" value="Enviar" onclick="return validateOpts();"/>
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