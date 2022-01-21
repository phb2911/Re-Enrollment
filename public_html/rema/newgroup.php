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
$groupName = getPost('grpname');

if (getPost('postback') == 1){
    
    $groupName = trim($groupName);
    
    // validate input
    if (!strlen($groupName) || strlen($groupName) > 20){
        $msg = 'O nome do grupo não é válido.';
    }
    elseif (!validateSubmittedTeachers()){
        $msg = 'Ocorreu um erro inesperado. Por favor tente novamente.';
    }
    elseif (!!$db->query("SELECT COUNT(*) FROM `groups` WHERE `Name` = '" . $db->real_escape_string($groupName) . "' AND `Campaign` = $cid")->fetch_row()[0]){
        $msg = 'Já existe um grupo com este nome nesta campanha. Por favor selecione outro nome.';
    }
    else {
        
        // create group
        if ($db->query("INSERT INTO `groups` (`Name`, `Campaign`) VALUES ('" . $db->real_escape_string($groupName) . "', $cid)")){
            
            $insId = $db->insert_id;
            
            // create reference to teachers
            $q = "INSERT INTO `group_teachers` (`Group`, `User`) VALUES ";
            $flag = false;
            
            foreach ($_POST['tch'] as $tch => $chkVal) {
                
                if ($flag) $q .= ", ";
                else $flag = true;
                
                $q .= "($insId, $tch)";
                
            }
            
            if ($db->query($q)){
                
                closeDbAndGoTo($db, "group.php?gid=$insId");
                
            }
                        
        }
        
        // if execution gets to this point
        // is because an error occured
        $msg = 'Error: ' . $db->error;
        
        
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Criar Novo Grupo</title>
    
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
            
        };
        
        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('helpBox').style.opacity = '1';
        }
        
        function hideBoxes(){
            element('helpBox').style.opacity = '0';
            element('overlay').style.opacity = '0';
            element('overlay').style.visibility = 'hidden';
            element('helpBox').style.visibility = 'hidden';
        }
        
        function validateInput(){
            
            var chkBoxes = document.getElementsByClassName('chkTchs');
            
            if (!element('txtGrpName').value.trim().length){
                alert('O nome do grupo não é válido.');
                element('txtGrpName').focus();
                return false;
            }
            
            for (var i = 0; i < chkBoxes.length; i++){
                if (chkBoxes[i].checked) return true;
            }
            
            alert('Pelo menos um professor deve ser selecionado.');
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
                <form id="frmChangeCamp" method="post" action="newgroup.php">
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
                <span style="font-style: italic; color: red;"><?php echo $msg; ?></span>
            </div>
            <br/>
        </div>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Criar Novo Grupo <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/></span>
            <hr/>
<?php

// fetch active teachers
// $teachers[ID] = Name
$teachers = fetchActiveTeachers();

if (empty($teachers)){
    echo '<div style="font-style: italic; color: red; padding: 5px;">Não há professores disponíveis para criação de um novo grupo.</div>';
}
else {
?>
            <form method="post" action="newgroup.php">
            <table style="border-collapse: collapse; width: 100%;">
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Campanha:</td>
                    <td style="font-weight: bold; font-style: italic; width: 100%;"><?php echo htmlentities($cName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Nome do Grupo:</td>
                    <td><input type="text" id="txtGrpName" name="grpname" value="<?php echo htmlentities($groupName, 3, 'ISO-8859-1'); ?>" maxlength="20" style="width: 250px;"/></td>
                </tr>
            </table>
            
            <div style="padding: 5px;">Selecione os participantes do grupo:</div>
            
            <table style="border-collapse: collapse; width: 100%;">
<?php

    foreach ($teachers as $tid => $tname) {
        echo '<tr>' . PHP_EOL .
                '<td><input type="checkbox" class="chkTchs" id="chkTch' . $tid . '" name="tch[' . $tid . ']" value="1"' . checkTeacherSubmission($tid) . '/></td>' . PHP_EOL .
                '<td style="width: 100%;"><label for="chkTch' . $tid . '">' . htmlentities($tname, 0, 'ISO-8859-1') . '</label></td>' . PHP_EOL .
                '</tr>' . PHP_EOL;
    }

?>
            </table>
            <div style="padding: 5px; text-align: right;">
                <button type="submit" name="postback" value="1" onclick="return validateInput();"><img src="<?php echo IMAGE_DIR; ?>disk2.png"/> Criar Grupo</button>
            </div>
            </form>
<?php

}

?>
            
        </div>
        
        
    </div>

    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay"></div>
    <div class="helpBox" id="helpBox" style="width: 550px; height: 125px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Ajuda - Criar Novo Grupo</span>
        <hr/>
        <ol style="line-height: 150%;">
            <li>Insira o nome do grupo no campo indicado (o limite é de 20 caracteres).</li>
            <li>Selecione um ou mais professores.</li>
            <li>Clique no botão <span style="font-style: italic;">'Criar Grupo'</span> para salvar as informações.</li>
        </ol>
    </div>
        
</body>
</html>
<?php

$db->close();

//-----------------------------------

function fetchActiveTeachers(){
    
    global $db;
    
    // only users who are active and who are teachers
    $result = $db->query("SELECT ID, Name FROM users WHERE Blocked = 0 AND Status < 2 ORDER BY Name");
    
    // $teachers[ID] = Name
    $teachers = array();
    
    while ($row = $result->fetch_assoc()){
        $teachers[$row['ID']] = $row['Name'];
    }
    
    $result->close();
    
    return $teachers;
    
}

//-----------------------------------

function checkTeacherSubmission($tid){
    
    return isset($_POST['tch']) && isset($_POST['tch'][$tid]) && $_POST['tch'][$tid] == 1 ? ' checked="checked"' : '';
    
}

//-----------------------------------

function validateSubmittedTeachers(){
    
    global $db;
    
    // validate submission
    if (!isset($_POST['tch']) || !is_array($_POST['tch'])) return false;
    
    // validate the number of items submitted
    $tchCount = count($_POST['tch']);
    
    if (!$tchCount) return false;
    
    // create query
    $q = "SELECT COUNT(*) FROM `users` WHERE `Blocked` = 0 AND `Status` < 2 AND (";
    $flag = false;
    
    foreach ($_POST['tch'] as $tch => $chkVal) {
        
        // validate submission format
        if (!isNum($tch) || $chkVal != '1') return false;
        
        if ($flag) $q .= " OR ";
        else $flag = true;
        
        $q .= "`ID` = $tch";
        
    }
    
    $q .= ")";
    
    // check if all the teachers exist in db
    return ($tchCount == $db->query($q)->fetch_row()[0]);
    
}

?>