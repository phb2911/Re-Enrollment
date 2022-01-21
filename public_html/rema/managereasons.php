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
$sids = getPost('sids');
$msg = null;

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

if (isset($_POST['newreason'])){ // insert new reason
    
    $desc = trim(getPost('newreason'));
    
    // validate
    if (strlen($desc)){
        
        if ($db->query("INSERT INTO reasons (Description) VALUE ('" . $db->real_escape_string($desc) . "')")){
            $msg = '<span style="font-style: italic; color: blue;">Motivo \'' . htmlentities($desc, 0, 'ISO-8859-1') . '\' inserido com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">O novo motivo não é válido.</span>';
    }
    
}
elseif (isset($_POST['rid'])){ // edit readon
    
    $rid = trim(getPost('rid'));
    $newDesc = trim(getPost('editreason'));
    
    if (isset($newDesc) && strlen($newDesc) && isNum($rid)){
        
        if ($db->query("UPDATE reasons SET Description = '" . $db->real_escape_string($newDesc) . "' WHERE ID = $rid")){
            
            if ($db->affected_rows){
                $msg = '<span style="font-style: italic; color: blue;">Motivo alterado com sucesso.</span>';
            }
            else {
                $msg = '<span style="font-style: italic; color: red;">O motivo não foi modificado. Possivelmente por que o novo motivo é igual ao antigo.</span>';
            }
            
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Dados inválidos. O motivo não foi modificado.</span>';
    }
    
}
elseif (isset($_POST['r'])){ // delete readon
    
    $rid = getPost('r');
    
    if (isNum($rid)){
        
        if ($db->query("DELETE FROM reasons WHERE ID = $rid")){
            $msg = '<span style="font-style: italic; color: blue;">Motivo removido com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Dados inválidos. O motivo não foi removido.</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Gerenciamento de Motivos</title>
    
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
        
        function chkClicked(id){
            
            var chks = document.getElementsByClassName('chk');
            
            for (var i = 0; i < chks.length; i++){
                if (chks[i].id == 'chk' + id) chks[i].checked = true;
                else chks[i].checked = false;
            }
            
        }
        
        function hideBoxes(){
            
            var elIndCol = ['helpBox', 'newReason', 'editReason', 'confRem', 'overlay'];
            
            for (var i = 0; i < elIndCol.length; i++){
                element(elIndCol[i]).style.opacity = '0';
                element(elIndCol[i]).style.visibility = 'hidden';
            }
            
        }
        
        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('helpBox').style.opacity = '1';
        }
        
        function showNewReasonBox(){
            element('txtNewReason').value = '';
            element('overlay').style.visibility = 'visible';
            element('newReason').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('newReason').style.opacity = '1';
        }
        
        function showEditReadonBox(rid, desc){
            element('hidRid').value = rid;
            element('tdEditReason').innerHTML = desc;
            element('txtEditReason').value = '';
            element('overlay').style.visibility = 'visible';
            element('editReason').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('editReason').style.opacity = '1';
        }
        
        function showConfRemBox(){
            
            var chks = document.getElementsByClassName('chk');
            var rid = 0;
            
            for (var i = 0; i < chks.length; i++){
                if (chks[i].checked){
                    rid = chks[i].value;
                    break;
                }
            }
            
            if (rid != 0){
                element('hidRemove').value = rid;
                element('overlay').style.visibility = 'visible';
                element('confRem').style.visibility = 'visible';
                element('overlay').style.opacity = '0.6';
                element('confRem').style.opacity = '1';
            }
            else {
                alert('Por favor selecione um motivo.');
            }
            
        }
        
        function validateField(id){
            if (element(id).value.trim().length) return true;
            else {
                alert('Por favor insira a descrição');
                element(id).focus();
                return false;
            }
        }
        
        function editSelected(){
            
            var chks = document.getElementsByClassName('chk');
            var rid = 0;
            var desc = '';
            
            for (var i = 0; i < chks.length; i++){
                if (chks[i].checked){ 
                    rid = chks[i].value;
                    desc = element('lblDescr' + rid).innerHTML;
                }
            }
            
            if (rid == 0) alert('Por favor selecione um motivo.');
            else {
                showEditReadonBox(rid, desc);
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
                <form id="frmChangeCamp" method="post" action="managereasons.php">
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
            
            <span style="font-weight: bold;">Gerenciamento de Motivos <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/></span>
            <hr/>
            <button onclick="showNewReasonBox();"><img src="<?php echo IMAGE_DIR; ?>plus.png"> Adicionar</button>
<?php

$result = $db->query("SELECT * FROM reasons WHERE ID != 1 ORDER BY Description");

if ($result->num_rows){
?>
            <button onclick="editSelected();"><img src="<?php echo IMAGE_DIR; ?>pencil1.png"> Editar Selecionado</button>
            <button onclick="showConfRemBox();"><img src="<?php echo IMAGE_DIR; ?>recycle2.png"> Remover Selecionado</button>
            <br/>
            <br/>
            <table style="width: 100%; border-collapse: collapse; border: #61269e solid 1px;">
                <tr style="background-color: #61269e; color: white;">
                    <td style="width: 100%;">Descrição</td>
                </tr>
                <tr>
                    <td style="padding: 0;">
                        <div style="line-height: 150%; height: 200px; padding: 5px; overflow: auto;">
                            <input type="checkbox" disabled="disabled" style="cursor: not-allowed;"/> Outros<br/>
<?php

    while ($row = $result->fetch_assoc()){
        
        echo '<input type="checkbox" class="chk" id="chk' . $row['ID'] . '" name="chk' . $row['ID'] . '" value="' . $row['ID'] . '" onclick="chkClicked(' . $row['ID'] . ');"/>' . 
                '<label id="lblDescr' . $row['ID'] . '" for="chk' . $row['ID'] . '"> ' . htmlentities($row['Description'], 0, 'ISO-8859-1') . '</label><br/>' . PHP_EOL;
        
    }

?>
                        </div>
                    </td>
                </tr>
            </table>
<?php
}
else {
    echo '<div style="font-style: italic; color: red; padding: 10px;">Não há motivos no banco de dados.</div>';
}

$result->close();

?>
        </div>
        
        <p>&nbsp;</p>
    </div>
    
    <div class="overlay" id="overlay"></div>
    <div class="helpBox" id="helpBox" style="width: 420px; height: 230px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Ajuda - Gerenciar Motivos</span>
        <hr/>
        <ul style="line-height: 150%;">
            <li>Click no botão <span style="font-style: italic;">'Adicionar'</span> para inserir um novo motivo.</li>
            <li>Click no botão <span style="font-style: italic;">'Editar Selecionado'</span> para modificar um motivo existente.</li>
            <li>
                Click no botão <span style="font-style: italic;">'Remover Selecionado'</span> para modificar um motivo existente.<br/>
                <span style="color: red;">Atenção: </span> Ao remover-se um motivo, aos alunos que tiverem atribído a este, será atribuído o motivo 
                <span style="font-style: italic;">'Outros'</span>.
            </li>
        </ul>
    </div>
    <div class="helpBox" id="newReason" style="width: 420px; height: 110px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Adicionar Motivo</span>
        <hr/>
        <form action="managereasons.php" method="post">
        <table style="width: 100%;">
            <tr>
                <td>Descrição:</td>
                <td style="width: 100%;"><input type="text" id="txtNewReason" name="newreason" maxlength="25" style="width: 300px;"/></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td style="width: 100%;">
                    <input type="submit" value="Salvar" onclick="return validateField('txtNewReason');"/>
                    <input type="button" value="Cancelar" onclick="hideBoxes();"/>
                </td>
            </tr>
        </table>
        </form>
    </div>
    <div class="helpBox" id="editReason" style="width: 420px; height: 140px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Editar Motivo</span>
        <hr/>
        <form action="managereasons.php" method="post">
        <table style="width: 100%;">
            <tr>
                <td style="white-space: nowrap; text-align: right;">Descrição Atual:</td>
                <td style="width: 100%;" id="tdEditReason"></td>
            </tr>
            <tr>
                <td style="white-space: nowrap; text-align: right;">Nova Descrição:</td>
                <td style="width: 100%;"><input type="text" id="txtEditReason" name="editreason" maxlength="25" style="width: 95%;"/></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td style="width: 100%;">
                    <input type="submit" value="Salvar" onclick="return validateField('txtEditReason');"/>
                    <input type="button" value="Cancelar" onclick="hideBoxes();"/>
                </td>
            </tr>
        </table>
        <input type="hidden" id="hidRid" name="rid"/>
        </form>
    </div>
    <div class="helpBox" id="confRem" style="width: 420px; height: 160px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Remover Motivo</span>
        <hr/>
        <div style="padding: 5px;">
            <span style="color: red;">Atenção:</span>
            Este motivo será removido. O motivo 'Outros' será atribuído a todos os alunos marcados com este.<br/><br/>
            Deseja continuar?<br/>
        </div>
        <div style="padding: 5px; text-align: right;">
            <form action="managereasons.php" method="post">
                <input type="hidden" id="hidRemove" name="r"/>
                <input type="submit" value="OK" style="width: 70px;"/>
                <input type="button" value="Cancelar" onclick="hideBoxes();"/>
            </form>
        </div>
    </div>
    
</body>
</html>
<?php

$db->close();

?>