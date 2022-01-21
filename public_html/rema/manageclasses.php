<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';
require_once 'required/campaigninfo.php';

// specific for this script
require_once 'required/manageclasses/selectUser.php';
require_once 'required/manageclasses/displayClasses.php';
require_once 'required/manageclasses/displayAllClasses.php';

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
if (isNum($cid) && isset($allCamp[intval($cid, 10)])){
    $cInfo = $allCamp[intval($cid, 10)];
    // save current campaign id into cookie
    setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN);
}
elseif (!$cInfo = getCampaignInfo($db, $allCamp)){ // get current campaign info
    // current campaing not valid
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

// set campaign variables
$cid = $cInfo['ID'];
$uid = getPost('uid');
$s = getPost('s');
$campName = $cInfo['Name'];
$campIsOpen = !!$cInfo['Open'];
$isValid = false;
$allTeachers = false;
$isTeacher = false;
$msg = null;

// validate user id and fetch user info
if (isNum($uid)){
    
    if ($uid == 0){
        $allTeachers = true;
    }
    elseif ($row = $db->query("SELECT Name, Status FROM users WHERE ID = $uid")->fetch_assoc()){
        $userName = $row['Name'];
        $isTeacher = (intval($row['Status'], 10) < 2);

        $isValid = true;
    }
    
}

if (($isValid || $allTeachers) && getPost('newClass') !== null){
    //
    // create new class
    //
    $newClass = trim(getPost('newclass'));
    $newClsLvlId = getPost('newlvlid');
    $newClsUnId = getPost('newclassunit');
    $newTchId = getPost('tid');
    
    if (!strlen(trim($newClass)) || !isNum($newClsLvlId) || !isNum($newClsUnId) || !isNum($newTchId)){
        $msg = '<span style="font-style: italic; color: red;">Parâmetro(s) inválido(s).</span>';
    }
    elseif (!!$db->query("SELECT COUNT(*) FROM classes WHERE Name = '" . $db->real_escape_string($newClass) . "' AND Campaign = $cid AND School = $newClsUnId AND User = $newTchId")->fetch_row()[0]){
        // class exists
        $msg = '<span style="font-style: italic; color: red;">Este colaborador já possue uma turma com o nome \'' . htmlentities($newClass, 0, 'ISO-8859-1') . '\' na mesma unidade nesta campanha.</span>';
    }
    elseif ($db->query("INSERT INTO classes (Name, Campaign, Level, School, User) VALUES ('" . $db->real_escape_string($newClass) . "', $cid, $newClsLvlId, $newClsUnId, $newTchId)")) {
        $msg = '<span style="font-style: italic; color: blue;">Turma adicionada com sucesso.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
    }
    
}
elseif (($isValid || $allTeachers) && isset($_POST['editClass'])){
    //
    // Edit class
    //
    $editClsId = getPost('classid');
    $editClsName = trim(getPost('classname'));
    $editLvlId = getPost('lvlid');
    $editUnit = getPost('classunit');
    $edituid = getPost('newuserid');
    $editOldUid = getPost('olduserid');
    
    // validate input
    if (!strlen($editClsName) || !isNum($editClsId) || !isNum($editLvlId) ||
            !isNum($editUnit) || !isNum($edituid)){
        
        $msg = '<span style="font-style: italic; color: red;">Parâmetro(s) inválido(s).</span>';
        
    }
    // if users are different, check if new user has class
    elseif ($edituid != $editOldUid && !!$db->query("SELECT COUNT(*) FROM classes WHERE Name = '" . $db->real_escape_string($editClsName) . "' AND Campaign = $cid AND School = $editUnit AND User = $edituid")->fetch_row()[0]){
        // class exists
        $msg = '<span style="font-style: italic; color: red;">O colaborador selecionado já possue uma turma com o nome \'' . htmlentities($editClsName, 0, 'ISO-8859-1') . '\' na mesma unidade nesta campanha.</span>';
    }
    elseif ($db->query("UPDATE classes SET Name = '" . $db->real_escape_string($editClsName) . "', Level = $editLvlId, School = $editUnit, User = $edituid WHERE ID = $editClsId")){
        $msg = '<span style="font-style: italic; color: blue;">Turma modificada com sucesso.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
    }
    
}
elseif (($isValid || $allTeachers) && isset($_POST['del'])){
    //
    // Delete class
    //
    $clsToDel = getPost('del');
    
    // veryfy if class has zero students and current user owns it
    if (!isNum($clsToDel) || !!$db->query("SELECT StudentCount($clsToDel, 0)")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">Parametros inválidos. A turma não foi removida.</span>';
    }
    // delete class
    elseif ($db->query("DELETE FROM classes WHERE ID = $clsToDel")) {
        // delete references to sub-campaigns
        if ($db->affected_rows)
            $msg = '<span style="font-style: italic; color: blue;">Turma removida com sucesso.</span>';
        else
            $msg = '<span style="font-style: italic; color: red;">Turma não encontrada no banco de dados.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
    }
        
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Gerenciamento de Turmas</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        table.tbl {
            width: 100%;
            border-collapse: collapse;
            border: #61269e solid 1px;
        }
        
        table.tbl td {
            padding: 8px;
        }
        
        span.link {
            color: white;
            cursor: pointer;
        }
        
        span.link:hover {
            text-decoration: underline;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selUser')) element('selUser').initializeSelect();
            if (element('selUserEdit')) element('selUserEdit').styleOption();
            if (element('selLevel')) element('selLevel').styleOption();
            if (element('selUnit')) element('selUnit').styleOption();
            if (element('selEditUnit')) element('selEditUnit').styleOption();
            if (element('selLvlEdit')) element('selLvlEdit').styleOption();
            if (element('selNewTeacher')) element('selNewTeacher').styleOption();
            
        };
        
        function validateNewClass(){
            
            if (element('selNewTeacher') && !element('selNewTeacher').selectedIndex){
                element('spMsg').innerHTML = 'Por favor selecione o Professor.';
                element('selNewTeacher').focus();
                return false;
            }
            
            if (!element('txtNewClass').value.trim().length){
                element('spMsg').innerHTML = 'Por favor digite o nome da turma.';
                element('txtNewClass').focus();
                return false;
            }
            
            if (!element('selLevel').selectedIndex){
                element('spMsg').innerHTML = 'Por favor selecione o Estágio.';
                element('selLevel').focus();
                return false;
            }
            
            if (!element('selUnit').selectedIndex){
                element('spMsg').innerHTML = 'Por favor selecione a unidade.';
                element('selUnit').focus();
                return false;
            }
            
            return true;
            
        }
        
        function validateEdit(){
            
            if (element('selUserEdit').selectedIndex == 0){
                element('spMsg2').innerHTML = 'Por favor selecione o Professor.';
                return false;
            }
            
            if (!element('txtClsName').value.trim().length){
                element('spMsg2').innerHTML = 'Por favor digite o nome da turma.';
                element('txtClsName').focus();
                return false;
            }
            
            if (element('selLvlEdit').selectedIndex == 0){
                element('spMsg2').innerHTML = 'Por favor selecione o Estágio.';
                return false;
            }
            
            if (element('selEditUnit').selectedIndex == 0){
                element('spMsg2').innerHTML = 'Por favor selecione a unidade.';
                return false;
            }
            
            return true;
            
        }
        
        function removeClass(cid){
            
            if (confirm('Tem certeza que deseja remover a turma?')){

                // attribute class id to hidden field
                element('hidDel').value = cid;
                // submit form
                element('form1').submit();
                
            }
            
        }
        
        function hideAllBoxes(){
            element('helpBox').style.opacity = '0';
            element('overlay').style.opacity = '0';
            element('overlay').style.visibility = 'hidden';
            element('helpBox').style.visibility = 'hidden';
            
            if (element('addClassBox')){
                element('addClassBox').style.opacity = '0';
                element('addClassBox').style.visibility = 'hidden';
            }
            
            if (element('editClassBox')){
                element('editClassBox').style.opacity = '0';
                element('editClassBox').style.visibility = 'hidden';
            }
             
        }
        
        function showHelpBox(){
            
            element('overlay').onclick = function(){hideAllBoxes()};
            
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('helpBox').style.opacity = '1';
            
        }
        
        function showAddClassBox(){
            
            element('overlay').onclick = function(){}; // empty function
            
            // hide message from previous display
            element('spMsg').innerHTML = '';
            
            if (element('selNewTeacher')) element('selNewTeacher').options[0].selected = true;
            element('txtNewClass').value = '';
            element('selLevel').options[0].selected = true;
            element('selUnit').options[0].selected = true;
            
            element('selLevel').styleOption();
            element('selUnit').styleOption();
            
            element('overlay').style.visibility = 'visible';
            element('addClassBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('addClassBox').style.opacity = '1';
            
        }
        
        function showEditBox(uid, clsId, schId, prgId){
            
            var seluid = element('selUserEdit');
            var selLvl = element('selLvlEdit');
            var selUnit = element('selEditUnit');
            var i;
            
            // hide message from previous display
            element('spMsg2').innerHTML = '';
            
            element('hidClsId').value = clsId;
            element('hidOldUser').value = uid;
            
            // select current user
            for (i = 0; i < seluid.options.length; i++){
                if (seluid.options[i].value == uid){
                    seluid.options[i].selected = true;
                    break;
                }
            }
            
            element('txtClsName').value = element('spClsName' + clsId).innerHTML;
            
            // select current level
            selLvl.options[0].selected = true; // prevent unwanted result
            
            for (i = 0; i < selLvl.options.length; i++){
                if (selLvl.options[i].value == prgId){
                    selLvl.options[i].selected = true;
                    break;
                }
            }
            
            // select current unit
            selUnit.options[0].selected = true; // prevent unwanted result
            
            for (i = 1; i < selUnit.options.length; i++){
                if (selUnit.options[i].value == schId){
                    selUnit.options[i].selected = true;
                    break;
                }
            }
            
            seluid.styleOption();
            selUnit.styleOption();
            selLvl.styleOption();
            
            element('overlay').onclick = function(){}; // empty function
            
            element('overlay').style.visibility = 'visible';
            element('editClassBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('editClassBox').style.opacity = '1';
            
        }
        
        function sort(uid, so){
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = 'manageclasses.php';
            
            document.body.appendChild(frm);
            
            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'uid';
            hid.value = uid;
            
            frm.appendChild(hid);
            
            var hid2 = document.createElement('input');
            hid2.type = 'hidden';
            hid2.name = 's';
            hid2.value = so;
            
            frm.appendChild(hid2);
            frm.submit();
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="manageclasses.php">
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: <?php echo ($allTeachers ? '100%' : '900px'); ?>; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
<?php

if ($allTeachers) displayAllClasses($db, $uid, $cid, $campName, $campIsOpen, $s);
elseif ($isValid) displayClasses($db, $uid, $userName, $cid, $campName, $campIsOpen, $isTeacher);
else selectUser($db, $campName);

?>        
    </div>
<?php if ($isValid || $allTeachers){ ?>
    <div class="overlay" id="overlay"></div>
    <div class="helpBox" id="helpBox" style="width: 500px; height: 245px;">
        <div class="closeImg" onclick="hideAllBoxes()"></div>
        <span style="font-weight: bold;">Ajuda - Gerenciamento de Turmas</span>
        <hr>
        <ul style="line-height: 150%;">
            <li>Click em <img src="<?php echo IMAGE_DIR; ?>pencil1.png"/> para modificar o nome, o estágio, a unidade ou o professor da turma.</li>
            <li>Para remover permanentemente uma turma, clique em <img src="<?php echo IMAGE_DIR; ?>recycle2.png"/>. Esta opção está disponível apenas para turmas que não contém alunos.</li>
            <li>Para escolher outro colaborador, clique em <img src="<?php echo IMAGE_DIR; ?>person.png" style="width: 16px; height: 16px;"/>.</li>
            <li>Para adicionar nova turma, clique no botão "Adicionar Turma". O botão só estará disponível se o colaborador for professor e se a campanha estiver aberta.</li>
        </ul>
    </div>
    <div class="helpBox" id="editClassBox" style="width: 450px; height: 230px;">
        <div class="closeImg" onclick="hideAllBoxes()"></div>
        <span style="font-weight: bold;">Modificar Turma</span>
        <hr>
        <form id="form3" method="post" action="manageclasses.php">
        <table>
            <tr>
                <td style="text-align: right;">Campanha:</td>
                <td style="font-weight: bold;"><?php echo $campName; ?></td>
            </tr>
            <tr>
                <td style="text-align: right;">Colaborador:</td>
                <td style="font-weight: bold;">
                    <select id="selUserEdit" name="newuserid" style="width: 300px;" onchange="this.styleOption(); element('spMsg2').innerHTML = '';" onkeyup="this.styleOption(); element('spMsg2').innerHTML = '';">
                        <option value="0" style="font-style: italic;">- Selecione -</option>
<?php 
                
    // only users who are active and who are teachers
    $result = $db->query("SELECT ID, Name FROM users WHERE Blocked = 0 AND Status < 2 GROUP BY ID ORDER BY Name");
    
    // $teachers[ID] = Name
    $teachers = array();
    
    while ($row = $result->fetch_assoc()){
        
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
        
        $teachers[$row['ID']] = $row['Name'];
        
    }
    
    $result->close();    
                
?>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align: right;">Turma:</td>
                <td style="font-weight: bold;"><input type="text" id="txtClsName" name="classname" style="width: 300px;" maxlength="55" onkeyup="element('spMsg2').innerHTML = '';"/></td>
            </tr>
            <tr>
                <td style="text-align: right;">Estágio:</td>
                <td style="font-weight: bold;">
                    <select id="selLvlEdit" name="lvlid" style="width: 300px;" onchange="this.styleOption(); element('spMsg2').innerHTML = '';" onkeyup="this.styleOption(); element('spMsg2').innerHTML = '';">
                        <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    $result = $db->query("SELECT ID, Name FROM levels WHERE Active = 1 ORDER BY Name");
    
    // $levels[ID] = Name
    $levels = array();
    
    while ($row = $result->fetch_assoc()){
        
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
        
        $levels[$row['ID']] = $row['Name'];
        
    }
    
    $result->close();

?>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align: right;">Unidade:</td>
                <td style="font-weight: bold;">
                    <select id="selEditUnit" name="classunit" style="width: 300px;" onchange="this.styleOption(); element('spMsg2').innerHTML = '';" onkeyup="this.styleOption(); element('spMsg2').innerHTML = '';">
                        <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    $result = $db->query("SELECT * FROM schools WHERE Active = 1 ORDER BY Name");
    
    // $units[ID] = Name
    $units = array();
    
    while ($row = $result->fetch_assoc()){
        
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
        
        $units[$row['ID']] = $row['Name'];
        
    }
    
    $result->close();
    
?>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input type="submit" name="editClass" value="Salvar" onclick="return validateEdit();"/> &nbsp;&nbsp;
                    <span id="spMsg2" style="color: red; font-style: italic;"></span>
                </td>
            </tr>
        </table>
        <input type="hidden" id="hidClsId" name="classid"/>
        <input type="hidden" id="hidOldUser" name="olduserid"/>
        <input type="hidden" name="uid" value="<?php echo $uid; ?>"/>
        <input type="hidden" name="s" value="<?php echo $s; ?>"/>
        </form>
    </div>
<?php if ($isTeacher && $campIsOpen){ ?>
    <div class="helpBox" id="addClassBox" style="width: 450px; height: 230px;">
        <div class="closeImg" onclick="hideAllBoxes()"></div>
        <span style="font-weight: bold;">Adicionar Nova Turma</span>
        <hr>
        <form id="form2" method="post" action="manageclasses.php">
        <table>
            <tr>
                <td style="text-align: right;">Campanha:</td>
                <td style="font-weight: bold;"><?php echo $campName; ?></td>
            </tr>
            <tr>
                <td style="text-align: right;">Professor:</td>
                <td style="font-weight: bold;"><?php echo $userName; ?></td>
            </tr>
            <tr>
                <td style="text-align: right;">Turma:</td>
                <td style="font-weight: bold;"><input type="text" id="txtNewClass" name="newclass" style="width: 300px;" maxlength="55" onkeyup="element('spMsg').innerHTML = '';"/></td>
            </tr>
            <tr>
                <td style="text-align: right;">Estágio:</td>
                <td style="font-weight: bold;">
                    <select id="selLevel" name="newlvlid" style="width: 300px;" onchange="this.styleOption(); element('spMsg').innerHTML = '';" onkeyup="this.styleOption(); element('spMsg').innerHTML = '';">
                        <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    foreach ($levels as $lvlId => $lvlName){
        echo '<option value="' . $lvlId . '" style="font-style: normal;">' . htmlentities($lvlName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }

?>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align: right;">Unidade:</td>
                <td style="font-weight: bold;">
                    <select id="selUnit" name="newclassunit" style="width: 300px;" onchange="this.styleOption(); element('spMsg').innerHTML = '';" onkeyup="this.styleOption(); element('spMsg').innerHTML = '';">
                        <option value="0" style="font-style: italic;">- Selecione -</option>
<?php
    
    foreach ($units as $unitId => $unitName) {
        echo '<option value="' . $unitId . '" style="font-style: normal;">' . htmlentities($unitName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }
        
?>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input type="submit" name="newClass" value="Salvar" onclick="return validateNewClass();"/> &nbsp;&nbsp;
                    <span id="spMsg" style="color: red; font-style: italic;"></span>
                </td>
            </tr>
        </table>
        <input type="hidden" name="uid" value="<?php echo $uid; ?>"/>
        <input type="hidden" name="tid" value="<?php echo $uid; ?>"/>
        </form>
    </div>
<?php 
    }
    elseif ($allTeachers && $campIsOpen){
?>
    <div class="helpBox" id="addClassBox" style="width: 450px; height: 230px;">
        <div class="closeImg" onclick="hideAllBoxes()"></div>
        <span style="font-weight: bold;">Adicionar Nova Turma</span>
        <hr>
        <form id="form2" method="post" action="manageclasses.php">
        <table>
            <tr>
                <td style="text-align: right;">Campanha:</td>
                <td style="font-weight: bold;"><?php echo $campName; ?></td>
            </tr>
            <tr>
                <td style="text-align: right;">Professor:</td>
                <td style="font-weight: bold;">
                    <select id="selNewTeacher" name="tid" style="width: 300px;" onchange="this.styleOption(); element('spMsg').innerHTML = '';" onkeyup="this.styleOption(); element('spMsg').innerHTML = '';">
                        <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    foreach ($teachers as $tid => $tname){
        echo '<option value="' . $tid . '" style="font-style: normal;">' . htmlentities($tname, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }
    
?>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align: right;">Turma:</td>
                <td style="font-weight: bold;"><input type="text" id="txtNewClass" name="newclass" style="width: 300px;" maxlength="55" onkeyup="element('spMsg').innerHTML = '';"/></td>
            </tr>
            <tr>
                <td style="text-align: right;">Estágio:</td>
                <td style="font-weight: bold;">
                    <select id="selLevel" name="newlvlid" style="width: 300px;" onchange="this.styleOption(); element('spMsg').innerHTML = '';" onkeyup="this.styleOption(); element('spMsg').innerHTML = '';">
                        <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    foreach ($levels as $lvlId => $lvlName){
        echo '<option value="' . $lvlId . '" style="font-style: normal;">' . htmlentities($lvlName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }

?>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align: right;">Unidade:</td>
                <td style="font-weight: bold;">
                    <select id="selUnit" name="newclassunit" style="width: 300px;" onchange="this.styleOption(); element('spMsg').innerHTML = '';" onkeyup="this.styleOption(); element('spMsg').innerHTML = '';">
                        <option value="0" style="font-style: italic;">- Selecione -</option>
<?php
    
    foreach ($units as $unitId => $unitName) {
        echo '<option value="' . $unitId . '" style="font-style: normal;">' . htmlentities($unitName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }
        
?>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input type="submit" name="newClass" value="Salvar" onclick="return validateNewClass();"/> &nbsp;&nbsp;
                    <span id="spMsg" style="color: red; font-style: italic;"></span>
                </td>
            </tr>
        </table>
        <input type="hidden" name="uid" value="<?php echo $uid; ?>"/>
        <input type="hidden" name="s" value="<?php echo $s; ?>"/>
        </form>
    </div>
<?php } ?>
    <form id="form1" method="post" action="manageclasses.php">
        <input type="hidden" name="uid" value="<?php echo $uid; ?>"/>
        <input type="hidden" id="hidDel" name="del" value=""/>
    </form>
<?php } ?>

    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>