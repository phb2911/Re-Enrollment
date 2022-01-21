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

// post back flag
$postback = getPost('postback');
$msg = null;

if ($postback == 1){
    // add new program
    
    // input
    $newProg = trim(getPost('newprg'));
    
    // validate input
    if (!strlen($newProg)){
        $msg = '<span style="font-style: italic; color: red;">A descrição inserida não é válida.</span>';
    }
    // check if program exists
    elseif (!!$db->query("SELECT COUNT(*) FROM programs WHERE Name = '" . $db->real_escape_string($newProg) . "'")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">O programa \'' . htmlentities($newProg, 0, 'ISO-8859-1') . '\' já existe no banco de dados.</span>';
    }
    // insert data
    elseif ($db->query("INSERT INTO programs (Name) VALUES ('" . $db->real_escape_string($newProg) . "')")){
        $msg = '<span style="font-style: italic; color: blue;">O programa \'' . htmlentities($newProg, 0, 'ISO-8859-1') . '\' foi inserido com sucesso.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
    }
    
}
elseif ($postback == 2){
    // delete program
    
    // input
    $prDelId = getPost('del');
    
    // validate input
    if (isNum($prDelId) && $progName = $db->query("SELECT Name FROM programs WHERE ID = $prDelId")->fetch_row()[0]){
        
        // check if program is in use
        if (!!$db->query("SELECT COUNT(*) FROM levels WHERE Program = $prDelId")->fetch_row()[0]){
            $msg = '<span style="font-style: italic; color: red;">O programa \'' . htmlentities($progName, 0, 'ISO-8859-1') . '\' está em uso e não pode ser removido.</span>';
        }
        // remove program
        elseif ($db->query("DELETE FROM programs WHERE ID = $prDelId")){
            $msg = '<span style="font-style: italic; color: blue;">O programa \'' . htmlentities($progName, 0, 'ISO-8859-1') . '\' foi removido com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">O programa não foi encontrado.</span>';
    }
    
}
elseif ($postback == 3){
    // activate/deactivate program
    
    $act = getPost('act');
    
    // validate input
    // validate input
    if (isNum($act) && $progInfo = $db->query("SELECT Name, Active FROM programs WHERE ID = $act")->fetch_assoc()){
        
        // activate/deactivate
        if ($db->query("UPDATE programs SET Active = IF(Active = 0, 1, 0) WHERE ID = $act")){
            $msg = '<span style="font-style: italic; color: blue;">O programa \'' . htmlentities($progInfo['Name'], 0, 'ISO-8859-1') . '\' foi ' . ($progInfo['Active'] ? 'desativado' : 'ativado') . ' com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">O estágio não foi encontrado.</span>';
    }
    
}
elseif ($postback == 4){
    // edit program
    
    // input
    $prEditId = getPost('editprgid');
    $progNewName = trim(getPost('prgnewname'));
    
    // validate input
    if (!strlen($progNewName)){
        $msg = '<span style="font-style: italic; color: red;">A descrição inserida não é válida.</span>';
    }
    elseif (!isNum($prEditId) || !$db->query("SELECT 1 FROM programs WHERE ID = $prEditId")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">O programa não é válido.</span>';
    }
    elseif (!!$db->query("SELECT COUNT(*) FROM programs WHERE ID != $prEditId AND Name = '" . $db->real_escape_string($progNewName) . "'")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">O programa \'' . htmlentities($progNewName, 0, 'ISO-8859-1') . '\' já existe no banco de dados.</span>';
    }
    elseif ($db->query("UPDATE programs SET Name = '" . $db->real_escape_string($progNewName) . "' WHERE ID = $prEditId")){
        $msg = '<span style="font-style: italic; color: blue;">O programa foi renomeado com sucesso.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Gerenciamento de Estágios</title>
    
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
                
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
        };
        
        function validateNewProgram(){
            
            if (!element('txtNewProgram').value.trim().length){
                alert('Por favor insira a descrição');
                element('txtNewProgram').focus();
                return false;
            }
            
            return true;
            
        }
        
        function removeProgram(prId){
            
            if (confirm('O programa será removido permanentemente. Tem certeza?')){
                
                // create form
                var frm = document.createElement('form');
                frm.method = 'post';
                frm.action = 'manageprog.php';

                document.body.appendChild(frm);
                
                // create postback flag
                var hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'postback';
                hid.value = '2';
                
                frm.appendChild(hid);
                
                // create delete id element
                var del = document.createElement('input');
                del.type = 'hidden';
                del.name = 'del';
                del.value = prId;
                
                frm.appendChild(del);
                frm.submit();
            }
            
        }
        
        function showNewProgramBox(){
            element('txtNewProgram').value = '';
            element('overlay').style.visibility = 'visible';
            element('newProgram').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('newProgram').style.opacity = '1';
        }
        
        function editProgram(prId){
            
            // fetch description
            element('tdEditProgram').innerHTML = element('spProgName' + prId).innerHTML.trim();
            
            // clear text box
            element('txtEditProgram').value = '';
            
            // set hidden field's value
            element('hidPrgId').value = prId;
            
            // display box
            element('overlay').style.visibility = 'visible';
            element('editProgram').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('editProgram').style.opacity = '1';
            
        }

        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('helpBox').style.opacity = '1';
        }

        function hideBoxes(){
            element('helpBox').style.opacity = '0';
            element('newProgram').style.opacity = '0';
            element('editProgram').style.opacity = '0';
            element('overlay').style.opacity = '0';
            element('overlay').style.visibility = 'hidden';
            element('helpBox').style.visibility = 'hidden';
            element('newProgram').style.visibility = 'hidden';
            element('editProgram').style.visibility = 'hidden';
        }
        
        function activateProg(prId){
            
            // create form
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = 'manageprog.php';

            document.body.appendChild(frm);

            // create postback flag
            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'postback';
            hid.value = '3';

            frm.appendChild(hid);

            // create delete id element
            var act = document.createElement('input');
            act.type = 'hidden';
            act.name = 'act';
            act.value = prId;

            frm.appendChild(act);
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
                <form id="frmChangeCamp" method="post" action="manageprog.php">
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
            
            <span style="font-weight: bold;">Gerenciamento de Programas <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/></span>
            <hr/>
            <button onclick="showNewProgramBox();"><img src="<?php echo IMAGE_DIR; ?>plus.png"> Adicionar Programa</button>
<?php

$result = $db->query("SELECT programs.*, (SELECT COUNT(*) FROM levels WHERE levels.Program = programs.ID) AS NumInLevels FROM programs ORDER BY Name");

if ($result->num_rows){
?>
            <div style="font-style: italic; color: red; font-size: 13px; padding-top: 5px;">* Programas em vermelhor estão inativos.</div>
            <table class="tbl">
                <tr style="background-color: #61269e; color: white;">
                    <td colspan="2">Programa</td>
                </tr>
<?php

    $bgcolor = null;

    while ($row = $result->fetch_assoc()){
        
        $bgcolor = ($bgcolor == '#e1e1e1') ? '#ffffff' : '#e1e1e1';
        
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td style="width: 100%;"><?php echo '<span id="spProgName' . $row['ID'] . '"' . ($row['Active'] ? '' : ' style="color: red;"') . '>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</span>'; ?></td>
                    <td style="white-space: nowrap;">
                        <img src="<?php echo IMAGE_DIR; ?>pencil1.png" title="Renomear" style="vertical-align: middle; cursor: pointer;" onclick="editProgram(<?php echo $row['ID']; ?>);"/> &nbsp;
                        <img src="<?php echo IMAGE_DIR . ($row['Active'] ? 'off' : 'on'); ?>.png" title="<?php echo ($row['Active'] ? 'Desativar' : 'Ativar'); ?>" style="vertical-align: middle; cursor: pointer;" onclick="activateProg(<?php echo $row['ID']; ?>);"/> &nbsp;
                        <?php
                        
                        if ($row['NumInLevels']){
                            echo '<img src="' . IMAGE_DIR . 'recycle.png" style="vertical-align: middle; cursor: not-allowed; opacity: 0.5;"/>';
                        }
                        else {
                            echo '<img src="' . IMAGE_DIR . 'recycle2.png" title="Remover" style="vertical-align: middle; cursor: pointer;" onclick="removeProgram(' . $row['ID'] . ');"/>';
                        }
                        
                        ?>
                        
                    </td>
                </tr>
<?php } ?>
            </table>
<?php    
}
else {
    echo '<div style="font-style: italic; color: red; padding: 10px 0 10px 0;">Não há programas no banco de dados.</div>';
}

$result->close();

?>
        </div>
        
        
    </div>

    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay"></div>
    <div class="helpBox" id="newProgram" style="width: 420px; height: 110px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Adicionar Programa</span>
        <hr/>
        <form action="manageprog.php" method="post">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: right;">Descrição:</td>
                <td style="width: 100%;"><input type="text" id="txtNewProgram" name="newprg" maxlength="100" style="width: 300px;" autocomplete="off"/></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td style="width: 100%;">
                    <input type="submit" value="Salvar" onclick="return validateNewProgram();"/>
                    <input type="button" value="Cancelar" onclick="hideBoxes();"/>
                </td>
            </tr>
        </table>
        <input type="hidden" name="postback" value="1"/>
        </form>
    </div>
    <div class="helpBox" id="editProgram" style="width: 520px; height: 140px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Renomear Programa</span>
        <hr/>
        <form action="manageprog.php" method="post">
        <table style="width: 100%;">
            <tr>
                <td style="white-space: nowrap; text-align: right; vertical-align: top;">Descrição Atual:</td>
                <td style="width: 100%;" id="tdEditProgram"></td>
            </tr>
            <tr>
                <td style="white-space: nowrap; text-align: right;">Nova Descrição:</td>
                <td style="width: 100%;"><input type="text" id="txtEditProgram" name="prgnewname" maxlength="100" style="width: 95%;" autocomplete="off"/></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td style="width: 100%;">
                    <input type="submit" id="btnEditSubmit" value="Salvar" onclick="return validateEdit();"/>
                    <input type="button" value="Cancelar" onclick="hideBoxes();"/>
                </td>
            </tr>
        </table>
        <input type="hidden" id="hidPrgId" name="editprgid"/>
        <input type="hidden" name="postback" value="4"/>
        </form>
    </div>
    <div class="helpBox" id="helpBox" style="width: 550px; height: 210px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Ajuda - Gerenciar Estágios</span>
        <hr/>
        <ul style="line-height: 150%;">
            <li>Click no botão <span style="font-style: italic;">'Adicionar Programa'</span> para inserir um novo programa.</li>
            <li>Click em <img src="<?php echo IMAGE_DIR; ?>pencil1.png"/> para renomear o programa.</li>
            <li>Click em <img src="<?php echo IMAGE_DIR; ?>on.png"/> para ativar/desativar o programa.</li>
            <li>Click em <img src="<?php echo IMAGE_DIR; ?>recycle2.png"/> para remover permanentemente o programa.<br>
                <span style="color: red;">Atenção: </span> O programa só poderá ser removido caso este não esteja
                assossiado a nenhum estágio, caso contrário, a lixeira estará indisponível.</li>
        </ul>
    </div>
        
</body>
</html>
<?php

$db->close();

?>