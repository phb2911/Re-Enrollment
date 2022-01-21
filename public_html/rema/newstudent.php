<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/genreq.php';
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

$campId = getPost('cid');

// check if current campaign id submitted by select element
if (isNum($campId) && isset($allCamp[intval($campId, 10)])){
    $cInfo = $allCamp[intval($campId, 10)];
    // save current campaign id into cookie
    setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN);
}
elseif (!$cInfo = getCampaignInfo($db, $allCamp)){ // get current campaign info
    // invalid campaign
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

// declare variables
$uid = getGet('uid');
$mode = getPost('mode');
$cid = null;
$clsName = null;
$level = null;
$unit = null;
$stds = null;
$situation = null;
$isValid = false;
$msg = null;
$openCampID = null;

// check if campaign is open
if ($cInfo['Open']){
    $openCampID = $cInfo['ID'];
    $isValid = (isNum($uid) && $userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]);
}

if ($isValid && isset($mode)){
    
    
    $stds = getPost('stds');
    $situation = getPost('situation');
    
    if ($mode === 'n'){
        
        $clsName = sTrim(getPost('cname'));
        $level = getPost('lvl');
        $unit = getPost('unit');
        
        if (!strlen($clsName)){
            $msg = 'O nome da nova turma é inválido.';
        }
        elseif (!isset($level) || !isNum($level) || !$db->query("SELECT 1 FROM levels WHERE Active = 1 AND ID = $level")->fetch_row()[0]){
            $msg = 'O estágio não é válido.';
        }
        elseif (!isset($unit) || !isNum($unit) || !$db->query("SELECT 1 FROM schools WHERE Active = 1 AND ID = $unit")->fetch_row()[0]){
            $msg = 'A unidade não é válida.';
        }
        elseif (!trimArray($stds)){ // trimArray will remove invalid elements of the array passed
            $msg = 'Estudante(s) inválido(s).';
        }
        elseif (!isset($situation) || !isNum($situation) || $situation > 3){
            $msg = 'Situação inválida.';
        }
        elseif (!!$db->query("SELECT COUNT(*) FROM classes WHERE Name = '" . $db->real_escape_string($clsName) . "' AND Campaign = $openCampID AND School = $unit AND User = $uid")->fetch_row()[0]){
            // check if campaign is repeated
            $msg = '<span style="font-style: italic; color: red;">Este colaborador já possue uma turma com o nome \'' . htmlentities($clsName, 0, 'ISO-8859-1') . '\' na mesma unidade nesta campanha.</span>';
        }
        elseif ($db->query("INSERT INTO classes (Name, User, Level, Campaign, School) VALUES ('" . $db->real_escape_string($clsName) . "', $uid, $level, $openCampID, $unit)")){

            $newCid = $db->insert_id;

            foreach ($stds as $std) {
                $db->query("INSERT INTO students (Name, Class, Situation) VALUES ('" . $db->real_escape_string($std) . "', $newCid, $situation)");
            }
            
            closeDbAndGoTo($db, "class.php?clsid=" . $newCid);
            
        }
        else {
            $msg = 'Error: ' . $db->error;
        }
        
    }
    elseif ($mode === 'e'){
        
        $cid = getPost('cid');
        
        if (!isNum($cid) || !$db->query("SELECT 1 FROM classes WHERE ID = $cid")->fetch_row()[0]){
            $msg = 'O ID da turma não é válido ou a turma não existe no banco de dados.';
        }
        elseif (!trimArray($stds)){ // trimArray will remove invalid elements of the array passed
            $msg = 'Estudante(s) inválido(s).';
        }
        elseif (!isset($situation) || !isNum($situation) || $situation > 3){
            $msg = 'Situação inválida.';
        }
        else {
            
            foreach ($stds as $std) {
                $db->query("INSERT INTO students (Name, Class, Situation) VALUES ('" . $db->real_escape_string($std) . "', $cid, $situation)");
            }
            
            closeDbAndGoTo($db, "class.php?clsid=" . $cid);
            
        }
        
    }
    else {
        $msg = 'Selecione criar uma nova turma ou adicionar uma existente.';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Adicionar Alunos</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        .linecount {
            text-align: right;
            border: 1px solid #000;
            border-right: none;
            background: #aaa;
            color: #000;
            resize: none;
            width: 25px;
            height: 300px;
            overflow: hidden;
            top: 0;
        }
        
        .mainarea {
            resize: none; 
            width: 500px; 
            height: 300px; 
            border: 1px solid #000;
            padding-left: 5px;
            white-space: pre;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selTeacher')) styleSelectBox(element('selTeacher'));
            if (element('selClass')) styleSelectBox(element('selClass'));
            if (element('selLevel')) styleSelectBox(element('selLevel'));
                        
            if (element('radNewClass')){
                
                element('txtNewClass').disabled = !element('radNewClass').checked;
                element('selLevel').disabled = !element('radNewClass').checked;
                element('selClass').disabled = element('radNewClass').checked;
                
                // if user has no program or class
                if (element('selLevel').options.length === 1) element('selLevel').options[0].text = '- Programa Não Encontrado -';
                if (element('selClass').options.length === 1) element('selClass').options[0].text = '- Turma Não Encontrada -';
                
            }
            
            if (element('selUnit')){
                styleSelectBox(element('selUnit'));
                element('selUnit').disabled = !element('radNewClass').checked;
                // if user has no unit
                if (element('selUnit').options.length === 1) element('selUnit').options[0].text = '- Unidade Não Encontrada -';
            }
            
            if (element('txtNumbers')) modifyCount();
            if (element('txtStudents')) element('txtStudents').wrap = 'off';
            
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27))
                showHelpBox(false);

        };
        
        function modifyCount(){
            
            var txtNum = element('txtNumbers');
            var txtStd = element('txtStudents');
            
            var lineNum = txtStd.value.split(/\r*\n/).length;
            
            txtNum.value = '';
            
            for (var i = 1; i <= lineNum; i++){
                txtNum.value += i + "\n";
            }
            
            txtNum.scrollTop = txtStd.scrollTop;
          
        }
        
        function redir(uid){
            
            if (uid > 0) window.location = 'newstudent.php?uid=' + uid;
            
        }
        
        function enableControls(flag){
            
            element('txtNewClass').disabled = !flag;
            element('selLevel').disabled = !flag;
            element('selClass').disabled = flag;
            
            if (element('selUnit')) element('selUnit').disabled = !flag;
            
        }
        
        function saveStudents(){
            
            // validate
            if (!element('radNewClass').checked && !element('radExiClass').checked){
                alert('Escolha se uma nova turma será criada ou uma existente será adicionada.');
                return;
            }
            
            if (element('radNewClass').checked){
                
                if (element('txtNewClass').value.trim().length === 0){
                    alert('Por favor digite o nome da nova turma.');
                    element('txtNewClass').focus();
                    return;
                }
                
                if (element('selLevel').selectedIndex == 0){
                    alert('Por favor selecione o estágio.');
                    return;
                }
                
                if (element('selUnit') && element('selUnit').selectedIndex == 0){
                    alert('Por favor selecione a unidade.');
                    return;
                }                
            
            }
            
            if (element('radExiClass').checked && element('selClass').selectedIndex === 0){
                alert('Por favor selecione a turma.');
                return;
            }
            
            if (element('selSituation').selectedIndex == 0){
                alert('Por favor selecione a situação.');
                return;
            }
            
            if (element('txtStudents').value.trim().length === 0){
                alert('Por favor adicione pelo menos um aluno.');
                element('txtStudents').focus();
                return;
            }
            
            var form = element('form1');
            
            // clear all child nodes - general.js
            form.clearChildren();
            
            var hidClsMode = document.createElement('input');
            hidClsMode.type = 'hidden';
            hidClsMode.name = 'mode';

            if (element('radNewClass').checked){
                
                var hidClsName = document.createElement('input');
                hidClsName.type = 'hidden';
                hidClsName.name = 'cname';
                hidClsName.value = element('txtNewClass').value.trim();
                
                form.appendChild(hidClsName);
                
                var hidLevelId = document.createElement('input');
                hidLevelId.type = 'hidden';
                hidLevelId.name = 'lvl';
                hidLevelId.value = selectedValue('selLevel');
                
                form.appendChild(hidLevelId);
                
                var hidUnitId = document.createElement('input');
                hidUnitId.type = 'hidden';
                hidUnitId.name = 'unit';
                hidUnitId.value = (element('selUnit') ? selectedValue('selUnit') : element('hidUnit').value);
                
                form.appendChild(hidUnitId);
                
                hidClsMode.value = 'n'; // new class
                
            }
            else {
                
                var hidClsID = document.createElement('input');
                hidClsID.type = 'hidden';
                hidClsID.name = 'cid';
                hidClsID.value = selectedValue('selClass');
                
                form.appendChild(hidClsID);
                
                hidClsMode.value = 'e'; // existing class
                
            }
            
            form.appendChild(hidClsMode);
            
            var hidSituation = document.createElement('input');
            hidSituation.type = 'hidden';
            hidSituation.name = 'situation';
            hidSituation.value = selectedValue('selSituation');
            
            form.appendChild(hidSituation);
            
            var lines = element('txtStudents').value.split(/\r|\n/);
            var hid;
            
            for (var i = 0; i < lines.length; i++){
                
                if (lines[i].trim().length){
                    
                    hid = document.createElement('input');
                    hid.type = 'hidden';
                    hid.name = 'stds[]';
                    hid.value = lines[i].trim();
                    
                    form.appendChild(hid);
                    
                }
                
            }
            
            form.submit();
            
        }
        
        function showHelpBox(show){
            
            if (show){
                element('overlay').style.visibility = 'visible';
                element('helpBox').style.visibility = 'visible';
                element('overlay').style.opacity = '0.6';
                element('helpBox').style.opacity = '1';
            }
            else {
                element('helpBox').style.opacity = '0';
                element('overlay').style.opacity = '0';
                element('helpBox').style.visibility = 'hidden';
                element('overlay').style.visibility = 'hidden';
            }
            
        }
                
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="newstudent.php">
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
        <div id="msgBox" style="width: 700px; left: 0; right: 0; margin: auto; display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <br/>
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <span style="color: red; font-style: italic;"><?php echo $msg; ?></span>
            </div>
        </div>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            <span style="font-weight: bold;">Adicionar Alunos<?php
            
            if ($isValid){
                echo ' <img src="' . IMAGE_DIR . 'question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox(true)"/>';
            }
            
            ?></span>
            <hr/>
<?php

if (!$openCampID){
    echo '<div style="font-style: italic; color: red; padding: 10px;">A campanha ' . $cInfo['Name'] . ' está encerrada e, portanto, não pode receber novos alunos.</div>';
}
elseif ($isValid){
    insertStudents($uid, $userName, $openCampID, $cInfo['Name'], $mode, $cid, $clsName, $stds, $level, $unit, $situation);
}
else {
    selectTeacher();
}

?>
        </div>
        
        <p>&nbsp;</p>
        
    </div>
    
    <div class="overlay" id="overlay" onclick="showHelpBox(false);"></div>
    <div class="helpBox" id="helpBox" style="width: 480px; height: 270px;">
        <div class="closeImg" onclick="showHelpBox(false)"></div>
        <span style="font-weight: bold;">Ajuda - Adicionar Alunos</span>
        <hr>
        <ol style="line-height: 150%;">
            <li>
                Escolha uma das duas opções:
                <ul>
                    <li><span style="text-decoration: underline;">Criar nova turma:</span> Insira o nome da turma e selecione o estágio e a unidade.</li>
                    <li><span style="text-decoration: underline;">Adicionar à turma existente:</span> Selecione a turma desejada.</li>
                </ul>
            </li>
            <li>Selecione a situação pedagógica dos alunos.</li>
            <li>Insira o nome dos alunos na caixa de text.<br/> <span style="color: red;">Importante:</span> Cada nome de aluno deve ser inserido em uma linha diferente.</li>
            <li>Click em "Salvar".</li>
        </ol>
    </div>
    
</body>
</html>
<?php

$db->close();

// ------------------------------

function trimArray(&$arr){
    
    if (is_array($arr)){
        
        $tempArr = array();
        
        foreach ($arr as $value) {
            
            $value = sTrim($value); // removes white and multiple spaces - from genreq.php
            
            if (strlen($value)) $tempArr[] = $value;
            
        }
        
        $arr = $tempArr;
        
        return count($arr);
        
    }
    
    return 0;
    
}

// ------------------------------

function selectTeacher(){
    
    global $db;
    
?>
        <div style="padding: 10px;">
            <table>
                <tr>
                    <td style="white-space: nowrap;">Selecione o Professor:</td>
                    <td style="width: 100%;">
                        <select id="selTeacher" style="width: 250px; font-style: italic;" onchange="styleSelectBox(this); redir(this.options[this.selectedIndex].value);" onkeyup="styleSelectBox(this); redir(this.options[this.selectedIndex].value);">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

$result = $db->query("SELECT ID, Name FROM users WHERE Blocked = 0 AND Status < 2 ORDER BY Name");

while ($row = $result->fetch_assoc()){
    echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
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

// ------------------------------

function insertStudents($uid, $userName, $openCampID, $campName, $mode, $cid, $clsName, $stds, $level, $unit, $situation){
    
    global $db;
    
?>

        <table>
            <tr>
                <td style="white-space: nowrap; text-align: right;">Professor:</td>
                <td style="width: 100%; font-style: italic; font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?></td>
            </tr>
            <tr>
                <td style="white-space: nowrap; text-align: right;">Campanha:</td>
                <td style="width: 100%; font-style: italic; font-weight: bold;"><?php echo htmlentities($campName, 0, 'ISO-8859-1'); ?></td>
            </tr>
            <tr>
                <td style="white-space: nowrap; text-align: right; vertical-align: top;">Turma:</td>
                <td style="width: 100%; padding-bottom: 15px;">
                    <fieldset style="border-radius: 5px; width: 500px;">
                        <legend><input type="radio" id="radNewClass" name="class" value="new" onclick="enableControls(true);"<?php if (!isset($mode) || $mode !== 'e') echo ' checked="checked"'; ?>/><label for="radNewClass"> Criar nova turma</label></legend>
                        <table style="width: 100%;">
                            <tr>
                                <td style="text-align: right; white-space: nowrap;">Nome:</td>
                                <td style="width: 100%;">
                                    <input type="text" id="txtNewClass" style="width: 300px;" maxlength="55" value="<?php if ($mode === 'n') echo htmlentities($clsName, 3, 'ISO-8859-1'); ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: right; white-space: nowrap;">Estágio:</td>
                                <td style="width: 100%;">
                                    <select id="selLevel" style="width: 200px; font-style: italic;" onchange="styleSelectBox(this);" onkeyup="styleSelectBox(this);">
                                        <option value="0" style="font-style: italic;">- Selecionar Estágio -</option>
<?php

$result = $db->query("SELECT ID, Name FROM levels WHERE Active = 1 ORDER BY Name");

while ($row = $result->fetch_assoc()){
    echo '<option value="' . $row['ID'] . '" style="font-style: normal;"' . ($level == $row['ID'] ? ' selected="selected"' : '') . '>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>';
}

$result->close();

?>
                                    </select>
                                </td>
                            </tr>
<?php

$result = $db->query("SELECT * FROM schools WHERE Active = 1 ORDER BY Name");

// check number of units available
$numOfUnits = $result->num_rows;

if ($numOfUnits > 1){
?>
                            <tr>
                                <td style="text-align: right; white-space: nowrap;">Unidade:</td>
                                <td style="width: 100%;">
                                    <select id="selUnit" style="width: 200px; font-style: italic;" onchange="styleSelectBox(this);" onkeyup="styleSelectBox(this);">
                                        <option value="0" style="font-style: italic;">- Selecionar Unidade -</option>
<?php



while ($row = $result->fetch_assoc()){
    echo '<option value="' . $row['ID'] . '" style="font-style: normal;"' . ($unit == $row['ID'] ? ' selected="selected"' : '') . '>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>';
}



?>
                                    </select>
                                </td>
                            </tr>
<?php } ?>
                        </table>
<?php

// one unit available, store info in hidden field
if ($numOfUnits == 1){
    
    $row = $result->fetch_assoc();
    
    echo '<input type="hidden" id="hidUnit" value="' . $row['ID'] . '"/>' . PHP_EOL;
    
}

$result->close();

?>
                        
                    </fieldset>
                </td>
            </tr>
            <tr>
                <td style="white-space: nowrap; text-align: right; vertical-align: top;">&nbsp;</td>
                <td style="width: 100%; padding-bottom: 15px;">
                    <fieldset style="border-radius: 5px; width: 500px;">
                        <legend><input type="radio" id="radExiClass" name="class" value="exi" onclick="enableControls(false);"<?php if ($mode === 'e') echo ' checked="checked"'; ?>/><label for="radExiClass"> Adicionar à turma existente</label></legend>
                        Turma: &nbsp;&nbsp;
                        <select id="selClass" style="width: 300px; font-style: italic;" onchange="styleSelectBox(this);" onkeyup="styleSelectBox(this);">
                            <option value="0" style="font-style: italic;">- Selecionar -</option>
<?php

$result = $db->query("SELECT classes.ID, classes.Name, schools.Name AS School FROM classes JOIN schools ON classes.School = schools.ID WHERE classes.User = $uid AND classes.Campaign = $openCampID ORDER BY Name");

while ($row = $result->fetch_assoc()){
    echo '<option value="' . $row['ID'] . '" style="font-style: normal;"' . ($mode === 'e' && $row['ID'] == $cid ? ' selected="selected"' : '') . '>' . htmlentities($row['Name'] . ' (' . $row['School'] . ')', 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
}

$result->close();

?>
                        </select>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <td style="text-align: right;">Situação:</td>
                <td>
                    <select id="selSituation" name="situation" style="width: 130px;">
                        <option>&nbsp;</option>
                        <option value="0"<?php if ($situation === '0') echo ' selected="selected"'; ?>>Ativo</option>
                        <option value="1"<?php if ($situation === '1') echo ' selected="selected"'; ?>>Evadido</option>
                        <option value="2"<?php if ($situation === '2') echo ' selected="selected"'; ?>>Cancelado</option>
                        <option value="3"<?php if ($situation === '3') echo ' selected="selected"'; ?>>Concluinte</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="white-space: nowrap; text-align: right; vertical-align: top;">Alunos:</td>
                <td style="width: 100%;">
                    <table style="border-collapse: collapse;">
                        <tr>
                            <td style="padding:0;"><textarea id="txtNumbers" class="linecount" cols="1" disabled="disabled"></textarea></td>
                            <td style="padding:0;"><textarea id="txtStudents" class="mainarea" name="txtStudents" onkeyup="modifyCount();" onchange="modifyCount();" onscroll="element('txtNumbers').scrollTop=this.scrollTop;"><?php
if (is_array($stds)){                            
    foreach ($stds as $std) {
        echo htmlentities($std, 0, 'ISO-8859-1') . PHP_EOL;
    }
}          
                            ?></textarea></td>
                        </tr>
                    </table>
                    <span style="color: red; font-style: italic;">* Inserir um aluno por linha.</span>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td><input type="button" value="Salvar" onclick="saveStudents();"/></td>
            </tr>
        </table>

        <form id="form1" action="newstudent.php?uid=<?php echo $uid; ?>" method="post"></form>

<?php
    
}

?>