<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';
require_once 'required/campaigninfo.php';

// specific for this script
require_once 'required/manageyrcont/selectUser.php';
require_once 'required/manageyrcont/displayStudents.php';

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

$cid = $cInfo['ID'];
$campName = $cInfo['Name'];
$msg = null;
$uid = getPost('uid');

$isValid = (isNum($uid) && $userName = $db->query("SELECT `Name` FROM `users` WHERE `ID` = $uid AND `Status` < 2 AND `Blocked` = 0")->fetch_row()[0]);

if ($isValid && getPost('save') == 1){
    
    if (isset($_POST['yrcont']) && is_array($_POST['yrcont'])){
        
        $q1 = "UPDATE `students` SET `YearlyContract` = 1, `Status` = 3 WHERE (";
        $q2 = "UPDATE `students` JOIN `classes` ON `students`.`Class` = `classes`.`ID` SET `students`.`YearlyContract` = 0 WHERE `classes`.`User` = $uid AND `classes`.`Campaign` = $cid AND (";
        $flag = false;
        
        foreach ($_POST['yrcont'] as $key => $value){
            
            if ($flag){
                $q1 .= " OR ";
                $q2 .= " AND ";
            }
            
            $q1 .= "`ID` = $key";
            $q2 .= "`students`.`ID` != $key";
            
            $flag = true;
            
        }
        
        $q = $q1 . "); " . $q2 . ");";
        
        if ($db->multi_query($q)){
            $msg = '<span style="font-style: italic; color: blue;">Operação realizada com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Ocorreu um erro inesperado. Por favor tente novamente.</span>';
        }
        
        clearStoredResults($db);
        
    }
    else {
        // remove all yearly contracts
        if ($db->query("UPDATE `students` JOIN `classes` ON `students`.`Class` = `classes`.`ID` SET `students`.`YearlyContract` = 0 WHERE `classes`.`User` = $uid AND `classes`.`Campaign` = $cid")){
            $msg = '<span style="font-style: italic; color: blue;">Operação realizada com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Ocorreu um erro inesperado. Por favor tente novamente.</span>';
        }
    }
    
}
    
?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Gerenciamento de Contratos Anuais</title>
    
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
            
            if (element('selUser')) element('selUser').styleOption();
            
        };
        
        function reloadPage(uid){
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = 'manageyrcont.php';
            
            document.body.appendChild(frm);
            
            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'uid';
            hid.value = uid;
            
            frm.appendChild(hid);
            frm.submit();
            
        }
        
        function validateInput(){
            
            var chks = document.getElementsByClassName('chkYrContr');
            
            for (var i = 0; i < chks.length; i++){
                
                if (chks[i].checked && element('hidSt' + chks[i].id.substring(10)).value != 3)
                    return confirm('Há alunos marcados com contrato anual que não estão marcados como rematriculados. O status destes alunos serão automaticamente modificados para rematriculado.\nDeseja continuar?');
                    
            }
            
            return true;
            
        }
        
        function hideHelpBox(){
            element('helpBox').style.opacity = '0';
            element('overlay').style.opacity = '0';
            element('helpBox').style.visibility = 'hidden';
            element('overlay').style.visibility = 'hidden';
        }
        
        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('helpBox').style.opacity = '1';
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="manageyrcont.php">
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: <?php echo ($isValid ? '1000px' : '500px'); ?>; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
        </div>
<?php

if ($isValid){
    displayStudents($db, $cid, $campName, $uid, $userName);
}
else {
    selectUser($db, $campName);
}

?>        
    </div>
    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay"></div>
    <div class="helpBox" id="helpBox" style="width: 500px; height: 230px;">
        <div class="closeImg" onclick="hideHelpBox()"></div>
        <span style="font-weight: bold;">Ajuda - Gerenciamento de Turmas</span>
        <hr/>
        <ul style="line-height: 150%;">
            <li>Para marcar os alunos com contrato anual, selecione-os e clique no botão <span style="font-style: italic;">'Salvar'</span>.</li>
            <li>Caso a seleção seja removida e o botão <span style="font-style: italic;">'Salvar'</span> clicado, o aluno será desmarcado.</li>
        </ul>
        <span style="color: red;">Atenção:</span> Caso o aluno seja marcado e o seu status seja diferente de rematriculado, este será automaticamente alterado para rematriculado. Desmarcando o aluno não restaurará o status anterior, ou seja, o aluno permanecerá com o status rematriculado.
    </div>
    
</body>
</html>
<?php

$db->close();

?>