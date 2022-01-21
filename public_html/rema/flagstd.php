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

$sid = getGet('sid');
$isValid = false;
$msg = null;
$situation = null;

if (isNum($sid)){
    
    $q = "SELECT students.Name, students.Flagged, students.FlagNotes, students.Situation, classes.Name AS ClassName, users.Name AS UserName, "
            . "users.ID AS UserID, campaigns.ID AS CampID, CONCAT(campaigns.`Year`, '.', campaigns.Semester) AS CampName, "
            . "campaigns.Open AS CampOpen, (SELECT Name FROM schools WHERE ID = classes.School) AS School FROM students LEFT JOIN classes ON students.Class = classes.ID LEFT JOIN campaigns "
            . "ON classes.Campaign = campaigns.ID LEFT JOIN users ON classes.User = users.ID WHERE students.ID = $sid";
            
    if ($stdInfo = $db->query($q)->fetch_assoc()){
        
        $cid = $stdInfo['CampID'];
        $campName = $stdInfo['CampName'];
        $campIsOpen = !!$stdInfo['CampOpen'];
        $stdName = $stdInfo['Name'];
        $stdIsFlagged = !!$stdInfo['Flagged'];
        $uid = $stdInfo['UserID'];
        $usrName = $stdInfo['UserName'];
        $clsName = $stdInfo['ClassName'];
        $unit = $stdInfo['School'];
        $flagNotes = $stdInfo['FlagNotes'];
        
        switch ($stdInfo['Situation']) {
            case '1':
                $situation = ' <span style="font-style: italic;">(Evadido)</span>';
                break;
            case '2':
                $situation = ' <span style="font-style: italic;">(Cancelado)</span>';
                break;
            case '3':
                $situation = ' <span style="font-style: italic;">(Concluinte)</span>';
                break;
        }

        $isValid = true;

    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
    }
    
}

// non-admin user trying to acces another user's student
if (!$isAdmin && $isValid && $loginObj->userId != $uid){
    closeDbAndGoTo($db, ".");
}

$tempFlagNotes = getPost('flagnotes');

// remove flag
if (getGet('rf') === '1' && $isValid && ($isAdmin || $campIsOpen)){
    
    if ($db->query("UPDATE students SET Flagged = 0, FlagNotes = null WHERE ID = $sid")){
    
        $stdIsFlagged = false;
        $flagNotes = '';
        
        $msg = '<span style="font-style: italic; color: blue;">Flag removida com sucesso.</span>';
    
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
    }
    
}
// add flag
elseif (isset($tempFlagNotes) && $isValid && ($isAdmin || $campIsOpen)){
    
    $tempFlagNotes = trim($tempFlagNotes);
    
    $fnLen = strlen($tempFlagNotes);
    
    if ($fnLen > 500) {
        $msg = '<span style="font-style: italic; color: red;">A descrição do motivo não pode conter mais de 500 caracteres.</span>';
    }
    elseif ($fnLen == 0){
        $msg = '<span style="font-style: italic; color: red;">Por favor digite a descrição do motivo.</span>';
    }
    elseif ($db->query("UPDATE students SET Flagged = 1, FlagNotes = '" . $db->real_escape_string($tempFlagNotes) . "' WHERE ID = $sid")){
        
        $stdIsFlagged = true;
        $flagNotes = $tempFlagNotes;
        
        $msg = '<span style="font-style: italic; color: blue;">Aluno marcado com sucesso.</span>';
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Marcar/Desmarcar Aluno</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        img.imgClose {
            position: absolute;
            right: 5px;
            cursor: pointer;
            opacity: 0.5;
        }
        
        img.imgClose:hover {
            opacity: 1;
        }
                        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
        };
        
        function flagStudent(){
            
            var txtSize = element('txtFlagNotes').value.trim().length;
            
            if (!txtSize){
                alert('Por favor descreva o motivo.');
            }
            else if (txtSize > 500){
                alert('A descrição do motivo não pode conter mais de 500 caracteres.');
            }
            else {
                element('form1').submit();
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
                <form id="frmChangeCamp" method="post" action="flagstd.php<?php if ($isValid) echo '?sid=' . $sid; ?>">
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

if (!$isValid){
    echo '<span style="font-style: italic; color: red;">Identidade do aluno não é válida.</sapn>';
}
else {
?>
        <div id="divMsg" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <div style="position: relative; width: 500px; left: 0; right: 0; margin: auto; border-radius: 5px; box-shadow: 3px 3px 3px #808080; background-color: #c6c6c6; padding: 10px;">
                <img class="imgClose" src="<?php echo IMAGE_DIR; ?>close.png" onclick="element('divMsg').style.display = 'none';"/>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Marcar/Desmarcar Aluno</span>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%;"><?php echo $campName; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%;"><?php echo htmlentities($usrName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Aluno:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($stdName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Turma:</td>
                    <td style="width: 100%;"><?php echo htmlentities($clsName, 0, 'ISO-8859-1') . $situation; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Unidade:</td>
                    <td style="width: 100%;"><?php echo htmlentities($unit, 0, 'ISO-8859-1'); ?></td>
                </tr>
<?php 

    if (!$isAdmin && !$campIsOpen){
        echo '<tr><td style="font-style: italic; color: red;" colspan="2">Esta campanha foi encerrada, este aluno não pode ser modificado.</td></tr>';
    }
?>
            </table>

        </div>
<?php if ($isAdmin || $campIsOpen) { ?>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Flag</span>
            <hr/>
<?php

        if ($stdIsFlagged){
            
            echo '<div style="padding: 10px; font-style: italic;"><img src="' . IMAGE_DIR . 'flag.png"/> Este aluno está marcado.</div>' . PHP_EOL .
                    '<div style="padding: 0 10px 10px 10px;">Descrição do motivo: <div style="width:450px; height: 100px; border: black solid 1px; padding: 5px; overflow-y: auto;">' . nl2br(htmlentities($flagNotes, 0, 'ISO-8859-1')) . '</div></div>' . PHP_EOL .
                    '<div style="padding: 0 10px 10px 10px;"><button onclick="window.location = \'flagstd.php?sid=' . $sid . '&rf=1\'"><img src="' . IMAGE_DIR . 'forbidden.png" style="vertical-align: bottom;"/> Remover Flag</button></div>' . PHP_EOL;
            
        }
        else {
            
            echo '<div style="padding: 10px;">Descreva o motivo abaixo:<br/>' . PHP_EOL . '<form id="form1" method="post" action="flagstd.php?sid=' . $sid . '">' . PHP_EOL .
                    '<textarea id="txtFlagNotes" name="flagnotes" maxlength="500" style="resize: none; width: 450px; height: 100px;"></textarea>' . PHP_EOL . '</form>' . PHP_EOL . '</div>' . PHP_EOL . 
                    '<div style="padding: 0 10px 10px 10px;"><button onclick="flagStudent();"><img src="' . IMAGE_DIR . 'flag.png" style="vertical-align: bottom;"/> Marcar Aluno</button></div>';
            
        }

?>
        </div>
        
<?php 

    }
    
    echo '<br/><div style="width: 500px; left: 0; right: 0; margin: auto;"><img src="' . IMAGE_DIR . 'back.png" style="cursor: pointer;" title="Voltar" onclick="window.location = \'' . ($isAdmin ? 'campbyuser.php?cid=' . $cid . '&uid=' . $uid : '.') . '\'"/></div>';
    
}

?>
    
        <p>&nbsp;</p>
    
    </div>
    
</body>
</html>
<?php

$db->close();

?>