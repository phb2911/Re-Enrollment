<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';
require_once 'required/campaigninfo.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()) closeDbAndGoTo($db, LOGIN_PAGE);

$isAdmin = $loginObj->isAdmin();

if (!$isAdmin) closeDbAndGoTo($db, ".");

// fetch all campaigns and store into array
$allCamp = allCampaigns($db);

$cid = getPost('cid');

// check if current campaign id submitted by select element
if (isset($cid) && isNum($cid) && isset($allCamp[intval($cid, 10)])){
    $cInfo = $allCamp[intval($cid, 10)];
    // save current campaign id into cookie
    setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN);
}
elseif (!$cInfo = getCampaignInfo($db, $allCamp)){ // get current campaign info
    // invalid campaign
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

unset($cid);

$sid = getGet('sid');
$msg = null;
$isValid = false;
$deleteFlag = false;


if (isset($sid) && isNum($sid)){
    
    $q = "SELECT students.*, users.ID AS UserID, classes.School, "
        . "campaignName(campaigns.ID) AS Campaign, campaigns.ID AS CampID "
        . "FROM students LEFT JOIN classes ON students.Class = classes.ID "
        . "LEFT JOIN users ON classes.User = users.ID "
        . "LEFT JOIN campaigns ON classes.Campaign = campaigns.ID WHERE students.ID = $sid";
    
    if ($row = $db->query($q)->fetch_assoc()){
        
        $stdName = $row['Name'];
        $email = $row['Email'];
        $clsID = $row['Class'];
        $notes = $row['Notes'];
        $situation = $row['Situation'];
        $isFlagged = !!$row['Flagged'];
        $flagNotes = $row['FlagNotes'];
        $campName = $row['Campaign'];
        $cid = $row['CampID'];
        $uid = $row['UserID'];
        $status = $row['Status'];
        $unit = $row['School'];
        $reason = $row['Reason'];
        $isYrCont = !!$row['YearlyContract'];
        
        $isValid = true;
        
    }
    
}

if ($isValid && getPost('submit') == '1'){
    
    $stdName = trim(getPost('stdname'));
    $email = trim(getPost('email'));
    $unit = getPost('unit');
    $notes = trim(getPost('notes'));
    $newuid = getPost('teacher');
    $isFlagged = (getPost('flag') == '1');
    $flagNotes = ($isFlagged ? trim(getPost('flagnotes')) : '');
    $classType = (getPost('clstype') == 'new' ? 'new' : 'ext');
    $clsID = getPost('class');
    $newClassName = trim(getPost('newclass'));
    $reason = getPost('reason');
    $lid = getPost('level');
    $situation = getPost('situation');
    $status = getPost('status');
    $isYrCont = (getPost('ycont') == '1');
    
    $flagNotesLen = strlen($flagNotes);
        
    // VALIDATE INPUT
    if (!validateTeacher($newuid, $uid)){ // validate teacher's id (if $newuid validates, $uid = $newuid)
        $msg = "O professor selecionado não é válido.";
    }
    elseif (!strlen(trim($stdName))){     // validate student's name
        $msg = "O nome do aluno não é válido.";
    }
    elseif (strlen($stdName) > 55){     // validate student's name length
        $msg = "O nome do aluno só pode conter no máximo 55 caracteres.";
    }
    elseif (strlen($email) && !isValidEmail($email)){     // validate email address
        $msg = 'O formato do E-mail não é válido.';
    }
    elseif ($classType == 'ext' && (!isNum($clsID) || !$db->query("SELECT COUNT(*) FROM classes WHERE ID = $clsID AND Campaign = $cid")->fetch_row()[0])){     // validate existing class
        $msg = 'A turma selecionada não é válida.';
    }
    elseif ($classType == 'new' && (!strlen($newClassName) || strlen($newClassName) > 55)){     // validate new class
        $msg = 'O nome da nova turma não poderá estar em branco nem pode conter mais de 55 caracteres.';
    }
    elseif ($classType == 'new' && (!isNum($lid) || !$db->query("SELECT COUNT(*) FROM levels WHERE ID = $lid")->fetch_row()[0])){     // validate new class' level
        $msg = 'O estágio não é válido.';
    }
    elseif ($classType == 'new' && (!isNum($unit) || !$db->query("SELECT COUNT(*) FROM schools WHERE ID = $unit")->fetch_row()[0])){     // validate new class' unit
        $msg = 'A unidade não é válida.';
    }
    elseif (!isNum($situation) || $situation > 3){     // validate student's situation
        $msg = 'A situação do aluno não é válida.';
    }
    elseif (!isNum($status) || $status > 3){     // validate student's status
        $msg = 'O status do aluno não é válido.';
    }
    elseif ($isYrCont && $status != 3){         // a student that has an yearly contract must be set to enrolled.
        $msg = 'O aluno marcado com a opção \'Contrato Anual\' deve ter o status \'Rematriculado\' selecionado.';
    }
    elseif (strlen($notes) > 2000){     // validate student's notes
        $msg = 'As observações não pode conter mais de 2000 caracteres.';
    }
    elseif ($isFlagged && !$flagNotesLen){     // validate flag reason
        $msg = 'Por favor insira a descrição do motivo.';
    }
    elseif ($isFlagged && $flagNotesLen > 500){     // validate flag reason length
        $msg = 'A descrição do motivo não pode ter mais de 500 caracteres.';
    }
    elseif ($classType == 'new' && !!$db->query("SELECT COUNT(*) FROM classes WHERE Name = '" . $db->real_escape_string($newClassName) . "' AND Campaign = $cid AND School = $unit AND User = $uid")->fetch_row()[0]){     // check if new class is duplicate
        // class exists
        $msg = '<span style="font-style: italic; color: red;">Este colaborador já possue uma turma com o nome \'' . htmlentities($newClassName, 0, 'ISO-8859-1') . '\' na mesma unidade nesta campanha.</span>';
    }
    elseif ($classType == 'new' && !$clsID = createNewClass($newClassName, $lid, $uid, $cid, $unit, $msg)){
        // do nothing. error message passed by reference and updated in function.
    }
    // UPDATE RECORD
    else {
        
        $q = "UPDATE `students` SET `Name` = '" . $db->real_escape_string($stdName) . "', `Email` = " . 
                (strlen($email) ? "'" . $db->real_escape_string($email) . "'" : "null") . ", `Class` = $clsID" .
                ", Status = $status, `Reason` = " . (isNum($reason) ? $reason : 'null') . ", `Situation` = $situation, `Notes` = " . 
                (!strlen($notes) ? "null" : "'" . $db->real_escape_string($notes) . "'") . ", `YearlyContract` = " . ($isYrCont ? '1' : '0') .
                ", `Flagged` = ";

        if ($isFlagged){
            $q .= "1, `FlagNotes` = '" . $db->real_escape_string($flagNotes) . "'";
        }
        else {
            $q .= "0, `FlagNotes` = null";
        }

        $q .= " WHERE `ID` = $sid";
        
        // execute query
        if ($db->query($q)){
            closeDbAndGoTo($db, "student.php?sid=" . $sid);
        }
        else {
            $msg = 'Error: ' . $db->error;
        }
        
    }
        
}
elseif ($isValid && getPost('d') == '1'){
    $db->query("DELETE FROM `students` WHERE `ID` = $sid");
    $deleteFlag = true;
}


?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Editar Aluno</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 10px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selClass')){
                
                setControls();
                setFlagElements();
                styleSelect(element('selStatus'));
                element('selClass').styleOption();
                element('selLevel').styleOption();
                element('selUnit').styleOption();
                
                <?php if ($isValid) echo 'retrieveClasses(' . $cid . ', selectedValue(\'selTeacher\'), ' . $clsID . ');'; ?>
                
            }
            
        };
        
        function styleSelect(sel){
            
            var si = sel.selectedIndex;
            
            if (si === 0) sel.style.backgroundColor = 'yellow';
            else if (si === 1) sel.style.backgroundColor = 'orange';
            else if (si === 2) sel.style.backgroundColor = 'red';
            else sel.style.backgroundColor = 'green';
            
            element('selReason').disabled = (element('selStatus').selectedIndex != 2);
            
        }
        
        function setControls(){
            
            var existingChecked = element('radExistingClass').checked;
            
            element('selClass').disabled = !existingChecked;
            element('txtNewClass').disabled = existingChecked;
            element('selLevel').disabled = existingChecked;
            element('selUnit').disabled = existingChecked;
            
        }
        
        function deleteStudent(){
            
            if (confirm("O estudante será removido permanentemente.\r\nTem certeza que deseja continuar?")){
                element('form2').submit();
            }
            
        }
        
        function setFlagElements(){
            
            element('txtFlagNotes').disabled = element('radFlagUnmark').checked;
            
        }
        
        function validateInput(){
            
            if (!element('txtStdName').value.trim().length){
                alert('Por favor insira o nome do aluno.');
                element('txtStdName').focus();
                return false;
            }
            
            if (element('txtEmail').value.trim().length && !validateEmail(element('txtEmail').value.trim())){
                alert('O formato do E-mail não é válido.');
                element('txtEmail').focus();
                return false;
            }
            
            if (element('radExistingClass').checked && element('selClass').selectedIndex == 0){
                alert('Por favor selecione a turma.');
                return false;
            }
            
            if (element('radNewClass') && element('radNewClass').checked){
                
                if (!element('txtNewClass').value.trim().length){
                    alert('Por favor insira o nome da turma.');
                    element('txtNewClass').focus();
                    return false;
                }
                
                if (element('selLevel').selectedIndex == 0){
                    alert('Por favor selecione o Estágio.');
                    element('selLevel').focus();
                    return false;
                }
                
                if (element('selUnit').selectedIndex == 0){
                    alert('Por favor selecione a unidade.');
                    element('selUnit').focus();
                    return false;
                }
                
            }
            
            if (element('chkYrCont').checked && element('selStatus').selectedValue() != 3){
                alert('O aluno marcado com a opção \'Contrato Anual\' deve ter o status \'Rematriculado\' selecionado.');
                element('selStatus').focus();
                return false;
            }
            
            if (element('txtNotes').value.trim().length > 2000){
                alert('As observações não pode conter mais de 2000 caracteres.');
                return false;
            }
            
            if (element('radFlagMark').checked && !element('txtFlagNotes').value.trim().length){
                alert('Por favor insira o motivo pelo qual o aluno esta sendo marcado.');
                element('txtFlagNotes').focus();
                return false;
            }
            else if (element('radFlagMark').checked && element('txtFlagNotes').value.trim().length > 500){
                alert('O motivo deve conter no maximo 500 caracteres.');
                element('txtFlagNotes').focus();
                return false;
            }
            
            return true;
            
        }
        
        function retrieveClasses(campId, userId, curClsId){
            
            var selCls = element('selClass');
            var loader = element('imgLoader');
            
            selCls.clearChildren(); // from gen.js
            
            loader.style.visibility = 'visible';
            
            var xmlhttp = xmlhttpobj();
            
            xmlhttp.onreadystatechange = function() {
                
                // request ready
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    
                    selCls.innerHTML = xmlhttp.responseText;
                    
                    for (var i = 0; i < selCls.options.length; i++){
                        if (selCls.options[i].value == curClsId) selCls.options[i].selected = true;
                    }
                    
                    styleSelectBox(selCls);
                    
                    loader.style.visibility = 'hidden';
                    
                }
                
            };
            
            // THIS WILL STOP PAGE
            xmlhttp.open("POST","aj_retcls.php?" + Math.random(),false);
            xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
            xmlhttp.send("cid=" + campId + "&uid=" + userId);
            
        }
                       
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="editstudent.php<?php if ($isValid) echo '?sid=' . $sid; ?>">
                Campanha: &nbsp;
                <select name="cid" style="width: 100px; border-radius: 5px;" onchange="element('imgCampLoader').style.visibility = 'visible'; element('frmChangeCamp').submit();">
<?php

// create option
foreach ($allCamp as $cmp){
    echo '<option value="' . $cmp['ID'] . '"' . ($cmp['ID'] == $cInfo['ID'] ? ' selected="selected"' : '') . ($allCamp[intval($cmp['ID'], 10)]['Open'] ? ' style="font-weight: bold;"' : '') . '>' . $cmp['Name'] . '</option>' . PHP_EOL;
}


?>
                </select>
                <img id="imgCampLoader" src="<?php echo IMAGE_DIR; ?>rema_loader.gif" style="vertical-align: middle; visibility: hidden;"/>
                </form>
            </div>
        
<?php

renderDropDown($db, $isAdmin);

?>
        </div>
        <br/>
<?php

if ($deleteFlag){
    echo '<div class="panel" style="width: 500px; left: 0; right: 0; margin: auto; font-style: italic; color: red;"><img src="' . IMAGE_DIR . 'recycle2.png"/> O aluno foi removido com sucesso.</div>' . PHP_EOL;
}
elseif ($isValid){ 
?>
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: 600px; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <span style="color: red; font-style: italic;"><?php echo $msg; ?></span>
            </div>
            <br/>
        </div>
        <form id="form1" action="editstudent.php?sid=<?php echo $sid; ?>" method="post">
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Editar Aluno</span>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Professor:</td>
                    <td style="width: 100%;">
                        <select id="selTeacher" name="teacher" style="width: 300px;" onchange="retrieveClasses(<?php echo $cid; ?>, selectedValue(this), <?php echo $clsID; ?>);" onkeyup="retrieveClasses(<?php echo $cid . ', ' . $uid . ', ' . $clsID; ?>);">
<?php

    $result = $db->query("SELECT ID, Name FROM users WHERE Status < 2 AND (ID = $uid OR Blocked = 0) ORDER BY Name");
    
    while ($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '"' . ($row['ID'] == $uid ? ' selected="selected"' : '') . '>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }
    
    $result->close();
    
    unset($result);

?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Nome:</td>
                    <td style="width: 100%;"><input type="text" id="txtStdName" name="stdname" value="<?php echo htmlentities($stdName, 3, 'ISO-8859-1'); ?>" style="width: 95%;" maxlength="55"/></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">E-mail:</td>
                    <td style="width: 100%;"><input type="text" id="txtEmail" name="email" value="<?php echo htmlentities($email, 3, 'ISO-8859-1'); ?>" style="width: 80%;" maxlength="200"/> <span style="font-style: italic;">(Opcional)</span></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Turma:</td>
                    <td style="width: 100%;">
                        
                        <fieldset style="border-radius: 5px; padding: 15px;"><legend><input type="radio" id="radExistingClass" name="clstype" value="ext" onclick="setControls();"<?php if (!isset($classType) || $classType == 'ext') echo ' checked="checked"'; ?>/>
                            <label for="radExistingClass"> Turma existente</label></legend>
                            <select id="selClass" name="class" style="width: 300px;" onchange="this.styleOption();" onkeyup="this.styleOption();"></select>
                            <img id="imgLoader" src="<?php echo IMAGE_DIR; ?>circle_loader.gif" style="visibility: hidden;"/>
                        </fieldset>
                        
                        <fieldset style="border-radius: 5px;"><legend><input type="radio" id="radNewClass" name="clstype" value="new" onclick="setControls();"<?php if (isset($classType) && $classType == 'new') echo ' checked="checked"'; ?>/>
                            <label for="radNewClass"> Criar nova turma</label></legend>
                            
                            <table style="border-collapse: collapse;">
                                <tr>
                                    <td style="text-align: right;">Nome:</td>
                                    <td><input type="text" id="txtNewClass" name="newclass" value="<?php if (isset($newClassName)) echo htmlentities($newClassName, 3, 'ISO-8859-1'); ?>" style="width: 200px;" maxlength="55"/></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;">Estágio:</td>
                                    <td>
                                        <select id="selLevel" name="level" style="width: 200px;" onchange="this.styleOption();" onkeyup="this.styleOption();">
                                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    $result = $db->query("SELECT ID, Name FROM levels WHERE Active != 0 ORDER BY Name");

    while ($row = $result->fetch_assoc()){
        echo '                                            <option value="' . $row['ID'] . '" style="font-style: normal;"' . (isset($lid) && $row['ID'] == $lid ? ' selected="selected"' : '') . '>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }

    $result->close();
    
    unset($result);

?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;">Unidade:</td>
                                    <td>
                                        <select id="selUnit" name="unit" style="width: 200px;" onchange="this.styleOption();" onkeyup="this.styleOption();">
                                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php 
    
    $result = $db->query("SELECT ID, Name FROM schools WHERE Active != 0 ORDER BY Name");

    while ($row = $result->fetch_assoc()){
        echo '                                            <option value="' . $row['ID'] . '" style="font-style: normal;"' . (isset($classType) && $classType == 'new' && $row['ID'] == $unit ? ' selected="selected"' : '') . '>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }

    $result->close();
    
    unset($result);
    
?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Contrato Anual:</td>
                    <td style="width: 100%;"><input type="checkbox" id="chkYrCont" name="ycont" value="1"<?php if ($isYrCont) echo ' checked="checked"'; ?>/></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Situação:</td>
                    <td style="width: 100%;">
                        <select id="selSituation" name="situation" style="width: 150px;">
                            <option value="0"<?php if ($situation == '0') echo ' selected="selected"'; ?>>Ativo</option>
                            <option value="1"<?php if ($situation == '1') echo ' selected="selected"'; ?>>Evadido</option>
                            <option value="2"<?php if ($situation == '2') echo ' selected="selected"'; ?>>Cancelado</option>
                            <option value="3"<?php if ($situation == '3') echo ' selected="selected"'; ?>>Concluinte</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Status:</td>
                    <td style="width: 100%;">
                        <select id="selStatus" name="status" style="border-width: 1px; width: 150px;" onkeyup="styleSelect(this);" onchange="styleSelect(this);">
                            <option value="0" style="background-color: yellow;"<?php if ($status == '0') echo ' selected="selected"'; ?>>Não Contatado</option>
                            <option value="1" style="background-color: orange;"<?php if ($status == '1') echo ' selected="selected"'; ?>>Contatado</option>
                            <option value="2" style="background-color: red;"<?php if ($status == '2') echo ' selected="selected"'; ?>>Não Volta</option>
                            <option value="3" style="background-color: green;"<?php if ($status == '3') echo ' selected="selected"'; ?>>Rematriculado</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Motivo:</td>
                    <td style="width: 100%;">
                        <select id="selReason" name="reason" style="width: 150px;">
<?php

    $result = $db->query("CALL sp_reasons()");
    
    while ($row = $result->fetch_assoc()){
        echo '                                            <option value="' . $row['ID'] . '"' . ($row['ID'] == $reason ? ' selected="selected"' : '') . '>' . htmlentities($row['Description'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }
    
    $result->close();
    
    // clear stored results after stored procedure call
    clearStoredResults($db);

?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Observações:</td>
                    <td style="width: 100%;">
                        <textarea id="txtNotes" name="notes" maxlength="2000" style="width: 95%; height: 100px; resize: none;"><?php echo htmlentities($notes, 0, 'ISO-8859-1'); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Flag:</td>
                    <td style="width: 100%;">
                        <input type="radio" id="radFlagUnmark" name="flag" value="0" onclick="setFlagElements();"<?php if (!$isFlagged) echo ' checked="checked"'; ?>/><label for="radFlagUnmark"> Desmarcado</label><br/>
                        <input type="radio" id="radFlagMark" name="flag" value="1" onclick="setFlagElements();"<?php if ($isFlagged) echo ' checked="checked"'; ?>/><label for="radFlagMark"> Marcado</label><br/>
                        Motivo:<br/>
                        <textarea id="txtFlagNotes" name="flagnotes" maxlength="500" style="resize: none; width: 95%; height: 50px;"><?php echo htmlentities($flagNotes, 0, 'ISO-8859-1'); ?></textarea>
                    </td>
                </tr>
            </table>
        </div>
        <br/><div style="width: 600px; left: 0; right: 0; margin: auto;">    
            <button type="submit" name="submit" value="1" onclick="return validateInput();"><img src="<?php echo IMAGE_DIR; ?>disk2.png" style="vertical-align: bottom;"/> Salvar</button>
            <button type="button" onclick="window.location = 'editstudent.php?sid=<?php echo $sid; ?>';"><img src="<?php echo IMAGE_DIR; ?>refresh.png" style="vertical-align: bottom;"/> Reset</button>
            <button type="button" onclick="deleteStudent();"><img src="<?php echo IMAGE_DIR; ?>recycle2.png" style="vertical-align: bottom;"/> Remover Aluno</button>
        </div>
        </form>
        <form id="form2" action="editstudent.php?sid=<?php echo $sid; ?>" method="post">
            <input type="hidden" name="d" value="1"/>
        </form>
<?php

}
else echo '<span style="font-style: italic; color: red;">Parametros inválidos.</span>' . PHP_EOL;
?>
    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

//----------------------------------------------------

function createNewClass($newClassName, $lid, $uid, $cid, $unit, &$msg){
    
    global $db;
    
    // if class successfully created, class id returned, else
    // nothing returned
    if ($db->query("INSERT INTO classes (Name, Level, User, Campaign, School) VALUES ('" . $db->real_escape_string($newClassName) . "' , $lid, $uid, $cid, $unit)")){
        return $db->insert_id;
    }
    else {
        $msg = 'Error: ' . $db->error;
        return;
    }
    
}

//----------------------------------------------------

function validateTeacher($newuid, &$uid){
    
    global $db;
    
    // user not changed
    if ($newuid == $uid) return true;

    // if new teacher id is valid, attribute new id to current id
    if (isNum(trim($newuid)) && !!$db->query("SELECT COUNT(*) FROM users WHERE ID = $newuid AND Status < 2 AND Blocked = 0")){
        $uid = $newuid;
        return true;
    }
    
    // invalid user
    return false;
    
}

?>