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

$msg = null;
$cid = $cInfo['ID'];
$cName = $cInfo['Name'];

if (getPost('delgr') == 1){
    
    $grId = getPost('gid');
    
    if (!isNum($grId)){
        $msg = '<span style="color: red; font-style: italic;">Parametros inválidos.</span>';
    }
    elseif ($db->query("DELETE FROM `groups` WHERE `ID` = $grId")){
        
        if (!$db->affected_rows){
            $msg = '<span style="color: red; font-style: italic;">Grupo não encontrado no grupo.</span>';
        }
        else {
            $msg = '<span style="color: blue; font-style: italic;">Grupo removido com sucesso.</span>';
        }
        
    }
    else {
        $msg = '<span style="color: red; font-style: italic;">Error: ' . $db->error . '</span>';
    }
    
}
elseif (getPost('del') == 1){
    
    $grId = getPost('gid');
    $tcId = getPost('tid');
    
    if (!isNum($grId) || !isNum($tcId)){
        $msg = '<span style="color: red; font-style: italic;">Parametros inválidos.</span>';
    }
    elseif ($db->query("DELETE FROM `group_teachers` WHERE `Group` = $grId AND `User` = $tcId")){
        
        if (!$db->affected_rows){
            $msg = '<span style="color: red; font-style: italic;">Professor não encontrado no grupo.</span>';
        }
        else {
            $msg = '<span style="color: blue; font-style: italic;">Professor removido com sucesso.</span>';
        }
        
    }
    else {
        $msg = '<span style="color: red; font-style: italic;">Error: ' . $db->error . '</span>';
    }
    
}
elseif (getPost('add') !== null) {
    
    $grId = getPost('gid');
    $tcId = getPost('tid');
    
    if (!isNum($grId) || !isNum($tcId)){
        $msg = '<span style="color: red; font-style: italic;">Parametros inválidos.</span>';
    }
    elseif (!$db->query("SELECT COUNT(*) FROM `groups` WHERE `ID` = $grId")->fetch_row()[0]){
        $msg = '<span style="color: red; font-style: italic;">Grupo não encontrado.</span>';
    }
    elseif (!$db->query("SELECT COUNT(*) FROM users WHERE Blocked = 0 AND Status < 2 AND ID = $tcId")->fetch_row()[0]){
        $msg = '<span style="color: red; font-style: italic;">Professor não encontrado.</span>';
    }
    elseif ($db->query("SELECT COUNT(*) FROM group_teachers WHERE `Group` = $grId AND `User` = $tcId")->fetch_row()[0]){
        $msg = '<span style="color: red; font-style: italic;">O professor já faz parte do grupo.</span>';
    }
    elseif ($db->query("INSERT INTO group_teachers (`Group`, `User`) VALUES ($grId, $tcId)")) {
        $msg = '<span style="color: blue; font-style: italic;">Professor inserido com sucesso.</span>';
    }
    else {
        $msg = '<span style="color: red; font-style: italic;">Error: ' . $db->error . '</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Gerenciamento de Grupos</title>
    
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
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            element('selTeachers').initializeSelect();
            
        };
        
        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('helpBox').style.opacity = '1';
        }
        
        function showAddTeacherBox(gid){
            
            element('tdGrName').innerHTML = element('groupName' + gid).innerHTML;
            element('selTeachers').selectedIndex = 0;
            element('selTeachers').styleOption();
            element('hidGid').value = gid;
            
            element('overlay').style.visibility = 'visible';
            element('addTeacher').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('addTeacher').style.opacity = '1';
            
        }
        
        function hideBoxes(){
            element('helpBox').style.opacity = '0';
            element('addTeacher').style.opacity = '0';
            element('overlay').style.opacity = '0';
            element('overlay').style.visibility = 'hidden';
            element('helpBox').style.visibility = 'hidden';
            element('addTeacher').style.visibility = 'hidden';
        }
        
        function deleteRecord(gid, tid){
            
            if (confirm('O professor será removido do grupo. Tem certeza?')){
                
                var frm = document.createElement('form');
                frm.method = 'post';
                frm.action = 'managegrp.php';
                
                document.body.appendChild(frm);
                
                var hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'del';
                hid.value = '1';
                
                frm.appendChild(hid);
                
                var hid1 = document.createElement('input');
                hid1.type = 'hidden';
                hid1.name = 'gid';
                hid1.value = gid;
                
                frm.appendChild(hid1);
                
                var hid2 = document.createElement('input');
                hid2.type = 'hidden';
                hid2.name = 'tid';
                hid2.value = tid;
                
                frm.appendChild(hid2);
                frm.submit();
                
            }
            
        }
        
        function deleteGroup(gid){
            
            if (confirm('O grupo e todos os seus professores serão removidos. Tem certeza?')){
                
                var frm = document.createElement('form');
                frm.method = 'post';
                frm.action = 'managegrp.php';
                
                document.body.appendChild(frm);
                
                var hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'delgr';
                hid.value = '1';
                
                frm.appendChild(hid);
                
                var hid1 = document.createElement('input');
                hid1.type = 'hidden';
                hid1.name = 'gid';
                hid1.value = gid;
                
                frm.appendChild(hid1);
                frm.submit();
                
            }
            
        }
        
        function validTeacher(){
            
            if (element('selTeachers').selectedIndex > 0) return true;
            else alert('Selecione o professor.');
            
            return false;
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="managegrp.php">
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: 600px; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Gerenciamento de Grupos <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/></span>
            <hr/>
            
            <table style="border-collapse: collapse; width: 100%;">
                <tr>
                    <td>Campanha:</td>
                    <td style="font-weight: bold; font-style: italic; width: 100%;"><?php echo htmlentities($cName, 0, 'ISO-8859-1'); ?></td>
                    <td style="white-space: nowrap;"><button type="button" onclick="window.location = 'newgroup.php';"><img src="<?php echo IMAGE_DIR; ?>plus.png"/> Adicionar Grupo</button></td>
                </tr>
            </table>
            
<?php

// $groupArr[ID] = Name
$groupArr = fetchGroups($cid);

if (empty($groupArr)){
    echo '<div style="font-style: italic; color: red; padding: 5px;">Não há grupos nesta campanha.</div>';
}
else {
    
    foreach ($groupArr as $gid => $gname) {
        
        echo '<div style="padding: 5px;"><table style="border-collapse: collapse; width: 100%; border: solid 1px #61269e;">' . PHP_EOL .
                '<tr style="background-color: #61269e;">' . PHP_EOL .
                '<td style="width: 100%;"><a id="groupName' . $gid . '" href="group.php?gid=' . $gid . '" style="color: white;">' . htmlentities($gname, 0, 'ISO-8859-1') . '</a></td>' . PHP_EOL .
                '<td style="white-space: nowrap;"><img src="' . IMAGE_DIR . 'person2.png" title="Adicionar Professor" style="cursor: pointer;" onclick="showAddTeacherBox(' . $gid . ');"/> &nbsp; ' .
                '<img src="' . IMAGE_DIR . 'recycle.png" title="Deletar Grupo" style="cursor: pointer;" onclick="deleteGroup(' . $gid . ');"/></td>'. PHP_EOL .
                '</tr>' . PHP_EOL;
        
        $teachers = fetchTeachersFromGroup($gid);
        
        if (!empty($teachers)){
        
            $bgcolor = null;

            foreach ($teachers as $tid => $tname) {

                $bgcolor = ($bgcolor == "#c6c6c6" ? '#ffffff' : '#c6c6c6');

                echo '<tr style="background-color: ' . $bgcolor . ';">' . PHP_EOL .
                      '<td>' . htmlentities($tname, 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                      '<td style="text-align: right;"><img src="' . IMAGE_DIR . 'recycle2.png" title="Remover Professor" style="cursor: pointer;" onclick="deleteRecord(' . $gid . ',' . $tid . ');"/></td>' . PHP_EOL .
                      '</tr>' . PHP_EOL;

            }
        
        }
        else {
            echo '<tr><td style="color:red; font-style: italic;">O grupo não possui professores.</td><td></td></tr>';
        }
        
        echo '</table></div>' . PHP_EOL;
        
    }
    
}

?>
            
        </div>
        
        
    </div>

    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay"></div>
    <div class="helpBox" id="helpBox" style="width: 550px; height: 240px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Ajuda - Gerenciar Grupos</span>
        <hr/>
        <ul style="line-height: 150%;">
            <li>Para adicionar um novo grupo, clique no botão <span style="font-style: italic;">'Adicionar Grupo'</span>.</li>
            <li>Para adicionar um professor ao grupo, clique no ícone <img src="<?php echo IMAGE_DIR; ?>person2.png"/> na aba do respectivo grupo.</li>
            <li>Para remover um professor do grupo, clique no ícone <img src="<?php echo IMAGE_DIR; ?>recycle2.png"/> referente ao professor desejado.</li>
            <li>Para remover completamente o grupo, clique no ícone <img src="<?php echo IMAGE_DIR; ?>recycle.png"/> na aba do respectivo grupo. É importante lembrar que o processo não poderá ser desfeito.</li>
        </ul>
    </div>
    <div class="helpBox" id="addTeacher" style="width: 550px; height: 110px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Adicionar Professor</span>
        <hr/>
        
        <table style="width: 100%;">
            <tr>
                <td style="text-align: right;">Grupo:</td>
                <td id="tdGrName" style="width: 100%; font-weight: bold;"></td>
            </tr>
            <tr>
                <td style="white-space: nowrap; text-align: right;">Selecione o professor:</td>
                <td>
                    <form method="post" action="managegrp.php">
                    <select id="selTeachers" name="tid">
                        <option value="0">- Selecione -</option>
<?php

// only users who are active and who are teachers
$result = $db->query("SELECT ID, Name FROM users WHERE Blocked = 0 AND Status < 2 ORDER BY Name");

while ($row = $result->fetch_assoc()){
    echo '<option value="' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
}

$result->close();

?>
                    </select>
                    <input type="hidden" id="hidGid" name="gid" value=""/>
                    <input type="submit" name="add" value="Adicionar" onclick="return validTeacher();"/>
                    </form>
                </td>
            </tr>
        </table>
        
    </div>
        
</body>
</html>
<?php

$db->close();

//---------------------------------------

function fetchGroups($cid){
    
    global $db;

    // $groupArr[ID] = Name
    $groupArr = array();
    
    // fetch groups
    $result = $db->query("SELECT `ID`, `Name` FROM groups WHERE `Campaign` = $cid ORDER BY `Name`");

    while ($row = $result->fetch_assoc()){
        $groupArr[$row['ID']] = $row['Name'];
    }

    $result->close();
    
    return $groupArr;
    
}

//---------------------------------------

function fetchTeachersFromGroup($gid){
    
    global $db;

    // $tch[ID] = Name
    $tch = array();
    
    $result = $db->query("SELECT `group_teachers`.`User` AS ID, `users`.`Name` FROM `group_teachers` JOIN `users` ON `group_teachers`.`User` = `users`.`ID` WHERE `group_teachers`.`Group` = $gid ORDER BY Name");
    
    while ($row = $result->fetch_assoc()){
        $tch[$row['ID']] = $row['Name'];
    }
    
    $result->close();
    
    return $tch;
    
}

?>