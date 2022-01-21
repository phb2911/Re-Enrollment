<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';

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
$cid = $_GET['cid'];

if (isset($uid) && preg_match('/^([0-9])+$/', $uid) && $row = $db->query("SELECT Name, Status FROM users WHERE ID = $uid")->fetch_assoc()){
    
    $userName = $row['Name'];
    $isTeacher = (intval($row['Status'], 10) < 2);
    
    if (isset($cid) && preg_match('/^([0-9])+$/', $uid) && $row = $db->query("SELECT CONCAT(`Year`, '.', Semester) AS CampName, Open FROM campaigns WHERE ID = $cid")->fetch_assoc()){
        $campName = $row['CampName'];
        $campIsOpen = !!$row['Open'];
        $isValid = true;
    }
    
    $isValid = (isset($cid) && preg_match('/^([0-9])+$/', $uid) && $campName = $db->query("SELECT CONCAT(`Year`, '.', Semester) FROM campaigns WHERE ID = $cid")->fetch_row()[0]);
}

if ($isValid && isset($_POST['del']) && preg_match('/^([0-9])+$/', $_POST['del'])){
    
    $clsToDel = $_POST['del'];
    
    // veryfy if class has zero students and current user owns it
    if (!$db->query("SELECT 1 FROM classes WHERE classes.ID = $clsToDel AND classes.User = $uid AND (SELECT COUNT(*) FROM students WHERE students.Class = classes.ID) = 0")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">Parametros inválidos. A turma não foi removida.</span>';
    }
    // delete class
    elseif ($db->query("DELETE FROM classes WHERE ID = $clsToDel")) {
        // delete references to sub-campaigns
        $db->query("DELETE FROM subcamp_classes WHERE Class = $clsToDel");
        $msg = '<span style="font-style: italic; color: blue;">Turma removida com sucesso.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
    }
        
}
elseif ($isValid && (isset($_POST['newname']) || isset($_POST['clsId']) || isset($_POST['schId']))){
    // save new info
    saveNewClassInfo($uid, $_POST['newname'], $_POST['clsId'], $_POST['schId'], $msg);
}
elseif ($isValid && isset($_POST['newclass'])){
    // save new class
    saveNewClass(trim($_POST['newclass']), trim($_POST['newclassunit']), $uid, $cid, $msg);
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
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            if (element('selUser')) styleSelectBox(element('selUser'));
            if (element('selCamp')) styleSelectBox(element('selCamp'));
            if (element('selUnit')) styleSelectBox(element('selUnit'));
            
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27))
                hideAllBoxes();

        };
        
        function redir(){
            
            if (element('selUser').selectedIndex == 0){
                alert('Por favor selecione o colaborador.');
            }
            else if (element('selCamp').selectedIndex == 0){
                alert('Por favor selecione a campanha.');
            }
            else {
                window.location = 'manageclasses.php?uid=' + selectedValue('selUser') + '&cid=' + selectedValue('selCamp');
            }
            
        }
        
        function modifyClass(cid){
            
            var panels = document.getElementsByClassName('pnl');
            var schPanes = document.getElementsByClassName('pnlSch');
            var spans = document.getElementsByClassName('spClsName');
            var spClsNames = document.getElementsByClassName('spSchName');
            var icons = document.getElementsByClassName('divIcons');
            var icons2 = document.getElementsByClassName('divIcons2');
            var selSch = element('selSch' + cid);
            var schId = element('hidSchId' + cid).value;
            var i;
            
            for (i = 0; i < panels.length; i++){
                if (panels[i].id != 'divBoxTextBox' + cid) panels[i].style.display = 'none';
            }
            
            for (i = 0; i < spans.length; i++){
                if (spans[i].id != 'spClsName' + cid) spans[i].style.display = 'inline';
            }
            
            for (i = 0; i < schPanes.length; i++){
                if (schPanes[i] != selSch) schPanes[i].style.display = 'none';
            }
            
            for (i = 0; i < spClsNames.length; i++){
                if (spClsNames[i].id != 'spSchName' + cid) spClsNames[i].style.display = 'inline';
            }
            
            for (i = 0; i < icons.length; i++){
                if (icons[i].id != 'divIcons' + cid) icons[i].style.display = 'block';
            }
            
            for (i = 0; i < icons2.length; i++){
                if (icons2[i].id != 'divIcons2_' + cid) icons2[i].style.display = 'none';
            }
            
            // set class name in text box
            element('txtClsName' + cid).value = revert(element('spClsName' + cid).innerHTML);
            
            // set school in select box
            for (i = 0; i < selSch.options.length; i++){
                if (selSch.options[i].value == schId){
                    selSch.options[i].selected = true;
                    break;
                }
            }
            
            element('spClsName' + cid).style.display = 'none';
            element('divBoxTextBox' + cid).style.display = 'block';
            element('spSchName' + cid).style.display = 'none';
            element('divSelSch' + cid).style.display = 'block';
            element('divIcons' + cid).style.display = 'none';
            element('divIcons2_' + cid).style.display = 'block';
            
        }
        
        function removeClass(cid){
            
            if (confirm('Tem certeza que deseja remover a turma?')){

                var hid = document.createElement('input');
                var form = element('form1');
                
                form.clearChildren(); // found in gen.js

                hid.type = 'hidden';
                hid.name = 'del';
                hid.value = cid;

                form.appendChild(hid);
                form.submit();

            }
            
        }
        
        function saveRecord(cid){
            
            var txt = element('txtClsName' + cid).value.trim();
            
            if (txt.length === 0){
                alert('Por favor insira o novo nome da turma.');
            }
            else if (txt.length > 55){
                alert('O nome da turma não pode conter mais de 55 caracteres.');
            }
            else {
                
                var form = element('form1');
                
                form.clearChildren(); // found in gen.js

                var hidNewName = document.createElement('input');
                hidNewName.type = 'hidden';
                hidNewName.name = 'newname';
                hidNewName.value = txt;
                
                var hidCid = document.createElement('input');
                hidCid.type = 'hidden';
                hidCid.name = 'clsId';
                hidCid.value = cid;
                
                var hidSid = document.createElement('input');
                hidSid.type = 'hidden';
                hidSid.name = 'schId';
                hidSid.value = selectedValue('selSch' + cid);

                form.appendChild(hidNewName);
                form.appendChild(hidCid);
                form.appendChild(hidSid);
                form.submit();
                
            }
            
        }
        
        function cancelEdit(cid){
            element('spClsName' + cid).style.display = 'inline';
            element('divBoxTextBox' + cid).style.display = 'none';
            element('spSchName' + cid).style.display = 'inline';
            element('selSch' + cid).style.display = 'none';
            element('divIcons' + cid).style.display = 'block';
            element('divIcons2_' + cid).style.display = 'none';
        }
        
        function validateInput(){
            
            if (!element('txtNewClass').value.trim().length){
                element('spMsg').innerHTML = 'Por favor digite o nome da turma.';
                element('txtNewClass').focus();
                return false;
            }
            
            if (!element('selUnit').selectedIndex){
                element('spMsg').innerHTML = 'Por favor selecione a unidade.';
                return false;
            }
            
            return true;
            
        }
        
        function showHelpBox(show){
            element('overlay').style.visibility = (show ? 'visible' : 'hidden');
            element('helpBox').style.visibility = (show ? 'visible' : 'hidden');
        }
        
        function showAddClassBox(show){
            
            element('txtNewClass').value = '';
            element('selUnit').selectedIndex = 0;
            element('selUnit').style.fontStyle = 'italic';
            element('overlay').style.visibility = (show ? 'visible' : 'hidden');
            element('addClassBox').style.visibility = (show ? 'visible' : 'hidden');
            
        }
        
        function hideAllBoxes(){
            element('overlay').style.visibility = 'hidden';
            element('helpBox').style.visibility = 'hidden';
            if (element('addClassBox')) element('addClassBox').style.visibility = 'hidden';
        }
        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="<?php echo IMAGE_DIR; ?>banner1.jpg"/></a>
        
<?php

renderDropDown($db);

if ($isValid) displayClasses($uid, $userName, $cid, $campName, $campIsOpen, $isTeacher, $msg);
else selectUserAndCampaing();
    
?>        
    </div>
    
    <div class="overlay" id="overlay" onclick="hideAllBoxes();"></div>
    <div class="helpBox" id="helpBox" style="width: 450px; height: 315px;">
        <div class="closeImg" onclick="showHelpBox(false)"></div>
        <span style="font-weight: bold;">Ajuda - Gerenciamento de Turmas</span>
        <hr>
        <ul style="line-height: 150%;">
            <li>Click em <img src="<?php echo IMAGE_DIR; ?>pencil1.png"/> para modificar o nome e a unidade da turma.</li>
            <li>Apos modificar, clique em <img src="<?php echo IMAGE_DIR; ?>disk2.png"/> para salvar as alterações.</li>
            <li>Clique em <img src="<?php echo IMAGE_DIR; ?>cancel2.png"/> para cancelar o modo de edição.</li>
            <li>Para remover permanentemente uma turma, clique em <img src="<?php echo IMAGE_DIR; ?>recycle2.png"/>. Esta opção está disponível apenas para turmas que não contém alunos.</li>
            <li>Para escolher outro colaborador ou outra campanha, clique em <img src="<?php echo IMAGE_DIR; ?>back.png" style="width: 16px; height: 16px;"/>.</li>
            <li>Para adicionar nova turma, clique no botão "Adicionar Turma". O botão só estará disponível se o colaborador for professor e se a campanha estiver aberta.</li>
        </ul>
    </div>
<?php if ($isTeacher && $campIsOpen){ ?>
    <div class="helpBox" id="addClassBox" style="width: 450px; height: 205px;">
        <div class="closeImg" onclick="showAddClassBox(false)"></div>
        <span style="font-weight: bold;">Adicionar nova turma</span>
        <hr>
        <form id="form2" method="post" action="manageclasses.php?uid=<?php echo $uid; ?>&cid=<?php echo $cid; ?>">
        <table>
            <tr>
                <td style="text-align: right;">Colaborador:</td>
                <td style="font-weight: bold;"><?php echo $userName; ?></td>
            </tr>
            <tr>
                <td style="text-align: right;">Campanha:</td>
                <td style="font-weight: bold;"><?php echo $campName; ?></td>
            </tr>
            <tr>
                <td style="text-align: right;">Turma:</td>
                <td style="font-weight: bold;"><input type="text" id="txtNewClass" name="newclass" style="width: 300px;" maxlength="55" onkeyup="element('spMsg').innerHTML = '';"/></td>
            </tr>
            <tr>
                <td style="text-align: right;">Unidade:</td>
                <td style="font-weight: bold;">
                    <select id="selUnit" name="newclassunit" style="width: 300px;" onchange="styleSelectBox(this); element('spMsg').innerHTML = '';" onkeyup="styleSelectBox(this); element('spMsg').innerHTML = '';">
                        <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    $result = $db->query("SELECT * FROM schools WHERE Active = 1 ORDER BY Name");
    
    while ($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . CRLF;
    }
    
    $result->close();
    // continue here
    // - add save button
    // - check active units when editin class
?>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input type="submit" value="Salvar" onclick="return validateInput();"/> &nbsp;&nbsp;
                    <span id="spMsg" style="color: red; font-style: italic;"></span>
                </td>
            </tr>
        </table>
        </form>
    </div>
<?php } ?>
    
</body>
</html>
<?php

$db->close();

//--------------------------------------------------------------------------

function saveNewClass($clsName, $schId, $uid, $cid, &$msg){
    
    global $db;
    
    if (!strlen(trim($clsName)) || !isset($schId) || !preg_match('/^([0-9])+$/', $schId) || !$db->query("SELECT COUNT(*) FROM schools WHERE ID = $schId")->fetch_row()[0]){
        // invalid class name or invalid school id
        $msg = '<span style="font-style: italic; color: red;">Parâmetro inválido.</span>';
    }
    elseif (!!$db->query("SELECT COUNT(*) FROM classes WHERE Name = '" . $db->real_escape_string($clsName) . "' AND Campaign = $cid AND School = $schId AND User = $uid")->fetch_row()[0]){
        // class exists
        $msg = '<span style="font-style: italic; color: red;">Este colaborador já possue uma turma com o nome \'' . htmlentities($clsName, 0, 'ISO-8859-1') . '\' na mesma unidade nesta campanha.</span>';
    }
    elseif ($db->query("INSERT INTO classes (Name, Campaign, School, User) VALUES ('" . $db->real_escape_string($clsName) . "', $cid, $schId, $uid)")) {
        $msg = '<span style="font-style: italic; color: blue;">Turma adicionada com sucesso.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
    }
    
}

//--------------------------------------------------------------------------

function displayClasses($uid, $userName, $cid, $campName, $campIsOpen, $isTeacher, $msg){
    
    global $db;
    
?>
        <br/>
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: 900px; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
        <div class="panel" style="width: 900px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Gerenciamento de Turmas <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox(true);"/></span>
            <hr/>
            
            <table>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
            </table>
            
<?php

    // $schools[ID] = Name
    $schools = array();
    
    $result = $db->query("SELECT * FROM schools ORDER BY Name");
    
    while ($row = $result->fetch_assoc()){
        $schools[$row['ID']] = $row['Name'];
    }
    
    $result->close();
    
    unset($result);

    $result = $db->query("SELECT classes.ID, classes.Name, schools.ID AS SchoolId, (SELECT COUNT(*) FROM students WHERE students.Class = classes.ID) AS NumStds FROM classes JOIN schools ON classes.School = schools.ID WHERE classes.User = $uid AND classes.Campaign = $cid ORDER BY Name");
    
    if ($result->num_rows){
        
        echo '<table style="width: 100%; border-collapse: collapse; border: #fd7706 solid 1px;">' . CRLF .
            '<tr style="background-color: #fd7706; color: white;"><td style="width: 65%;">Turma</td>' . CRLF .
            '<td style="width: 23%;">Unidade</td>' . CRLF .
            '<td style="text-align: center; width: 12%;">Alunos</td>' . CRLF .
            '<td style="white-space: nowrap;"><img src="' . IMAGE_DIR . 'trans.png" style="width: 16px; height: 16px;"/> &nbsp; <img src="' . IMAGE_DIR . 'trans.png" style="width: 16px; height: 16px;"/></td></tr>' . CRLF;
        
        while ($row = $result->fetch_assoc()){
            
            $bgcolor = ($bgcolor == '#e1e1e1') ? '#ffffff' : '#e1e1e1';
?>
    
                <tr>
                    <td style="background-color: <?php echo $bgcolor; ?>;">
                        <div class="pnl" id="divBoxTextBox<?php echo $row['ID']; ?>" style="display: none;">
                            <input type="text" id="txtClsName<?php echo $row['ID']; ?>" style="width: 95%;" maxlength="55"/> &nbsp;
                        </div>
                        <span class="spClsName" id="spClsName<?php echo $row['ID']; ?>"><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></span>
                    </td>
                    <td style="background-color: <?php echo $bgcolor; ?>;">
                        <span class="spSchName" id="spSchName<?php echo $row['ID']; ?>"><?php echo htmlentities($schools[$row['SchoolId']], 0, 'ISO-8859-1'); ?></span>
                        <input type="hidden" id="hidSchId<?php echo $row['ID']; ?>" value="<?php echo $row['SchoolId']; ?>"/>
                        <div class="pnlSch" id="divSelSch<?php echo $row['ID']; ?>" style="display: none;">
                            <select id="selSch<?php echo $row['ID']; ?>" style="width: 95%;">
<?php

            foreach ($schools as $schID => $schName) {
                echo '                                  <option value="' . $schID . '">' . htmlentities($schName, 0, 'ISO-8859-1') . '</option>' . CRLF;
            }

?>
                            </select>
                        </div>
                    </td>
                    <td style="background-color: <?php echo $bgcolor; ?>; text-align: center;"><?php echo $row['NumStds']; ?></td>
                    <td style="background-color: <?php echo $bgcolor; ?>; white-space: nowrap;">
                        <div class="divIcons" id="divIcons<?php echo $row['ID']; ?>">
                            <img id="img1_<?php echo $row['ID']; ?>" src="<?php echo IMAGE_DIR; ?>pencil1.png" title="Modificar" style="vertical-align: middle; width: 16px; height: 16px; cursor: pointer;" onclick="modifyClass(<?php echo $row['ID']; ?>);"/> &nbsp;
<?php

            if (!$row['NumStds']){
                echo '<img id="img2_' . $row['ID'] . '" src="' . IMAGE_DIR . 'recycle2.png" title="Remover" style="vertical-align: middle; width: 16px; height: 16px; cursor: pointer;" onclick="removeClass('. $row['ID'] . ');"/>' . CRLF;
            }
            else {
                echo '<img src="' . IMAGE_DIR . 'recycle.png" title="Indisponível" style="vertical-align: middle; width: 16px; height: 16px; opacity: 0.5;"/>' . CRLF;
            }

?>
                        </div>
                        <div class="divIcons2" id="divIcons2_<?php echo $row['ID']; ?>" style="display: none;">
                            <img src="<?php echo IMAGE_DIR; ?>disk2.png" title="Salvar" style="width: 16px; height: 16px; vertical-align: middle; cursor: pointer;" onclick="saveRecord(<?php echo $row['ID']; ?>);"/> &nbsp;
                            <img src="<?php echo IMAGE_DIR; ?>cancel2.png" title="Cancelar" style="width: 16px; height: 16px; vertical-align: middle; cursor: pointer;" onclick="cancelEdit(<?php echo $row['ID']; ?>);"/>
                        </div>
                    </td>
                </tr>
    
<?php
          
        }
        
        echo '</table>' . CRLF;
        
    }
    else {
        echo '<div style="font-style: italic; color: red; padding: 5px;">Este colaborador não possue turmas na campanha selecionada.</div>' . CRLF;
    }
    
    $result->close();

    if ($isTeacher && $campIsOpen){
        echo '<div style="padding: 10px 5px 5px 5px;"><button type="button" onclick="showAddClassBox(true);"><img src="' . IMAGE_DIR . 'plus.png" /> Adicionar Turma</button></div>';
    }

?>
        </div>
        
        <br/>
        <div style="width: 900px; left: 0; right: 0; margin: auto;">
            <img src="<?php echo IMAGE_DIR; ?>back.png" title="Voltar" style="cursor: pointer;" onclick="window.location = 'manageclasses.php';"/>
        </div>
        
        <form id="form1" action="manageclasses.php?uid=<?php echo $uid; ?>&cid=<?php echo $cid; ?>" method="post"></form>
<?php
    
}

//--------------------------------------------------------------------------

function selectUserAndCampaing(){
    
    global $db;
    
?>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Gerenciamento de Turmas</span>
            <hr/>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Selecione o colaborador:</td>
                    <td style="width: 100%;">
                        <select id="selUser" style="width: 250px;" onchange="styleSelectBox(this);" onkeyup="styleSelectBox(this);">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    // active user only
    $result = $db->query("SELECT users.ID, users.Name FROM users WHERE users.Blocked = 0 AND Status < 2 GROUP BY ID ORDER BY Name");
    
    while ($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . CRLF;
    }
    
    $result->close();

?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Campanha de Rematrícula:</td>
                    <td style="width: 100%;">
                        <select id="selCamp" style="width: 250px;" onchange="styleSelectBox(this);" onkeyup="styleSelectBox(this);">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    $result = $db->query("SELECT ID, Open, CONCAT(`Year`, '.', Semester) AS Campaign FROM campaigns ORDER BY Campaign DESC");
    
    while ($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;"' . (!!$row['Open'] ? ' selected="selected"' : '') . '>' . htmlentities($row['Campaign'], 0, 'ISO-8859-1') . '</option>' . CRLF;
    }
    
    $result->close();

?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><input type="button" value="Gerenciar" onclick="redir();"/></td>
                </tr>
            </table>
            
        </div>
            
<?php
    
}

//------------------------------------------------

function saveNewClassInfo($uid, $newName, $clsId, $schId, &$msg){
    
    global $db;
    
    if (!isset($newName) || !isset($clsId) || !isset($schId)){
        $msg = '<span style="font-style: italic; color: red;">Paramatros inválidos.</span>';
    }
    elseif (!strlen($newName)){
        $msg = '<span style="font-style: italic; color: red;">Por favor insira o nome da turma.</span>';
    }
    elseif (strlen($newName) > 55){
        $msg = '<span style="font-style: italic; color: red;">O nome da turma não pode conter mais de 55 caracteres.</span>';
    }
    elseif (!preg_match('/^([0-9])+$/', $clsId)){
        $msg = '<span style="font-style: italic; color: red;">O ID da turma não é válido.</span>';
    }
    elseif (!preg_match('/^([0-9])+$/', $schId)){
        $msg = '<span style="font-style: italic; color: red;">O ID da unidade não é válido.</span>';
    }
    elseif (!$db->query("SELECT 1 FROM classes WHERE ID = $clsId AND User = $uid")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">Parametros inválidos. O nome da turma não foi alterado.</span>';
    }
    elseif (!$db->query("SELECT 1 FROM schools WHERE ID = $schId")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">Parametros inválidos. O nome da turma não foi alterado.</span>';
    }
    elseif ($db->query("UPDATE classes SET Name = '" . $db->real_escape_string($newName) . "', School = $schId WHERE ID = $clsId")){
        $msg = '<span style="font-style: italic; color: blue;">Nome da turma modificado com sucesso.</span>';
    }
    else {
        
        $DBerror = $db->error;
        
        // check for unique index violation
        // look for index 'classes_idx1'
        if (strpos($DBerror, 'classes_idx1') !== false){
            $DBerror = 'Este colaborador já tem uma turma com o mesmo nome na mesma unidade. O nome da turma não foi alterado.';
        }
        
        $msg = '<span style="font-style: italic; color: red;">' . $DBerror . '</span>';
    }
    
}

?>