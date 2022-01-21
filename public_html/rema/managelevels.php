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

// fectch active programs
$result = $db->query("SELECT ID, Name FROM programs WHERE Active = 1 ORDER BY Name");

$programs = array();

while ($row = $result->fetch_assoc()){
    $programs[$row['ID']] = $row['Name'];
}

$result->close();

// input
$act = getPost('act');
$del = getPost('del');
$newlvl = getPost('newlvl');
$newPrId = getPost('newprogid');
$editLevelId = getPost('editlvlid');
$editPrgoLvlId = getPost('editprglvlid');
$editPrId = getPost('editprogid');
$msg = null;

if (isset($act)){
    
    // validate input
    if (isNum($act) && $levelInfo = $db->query("SELECT Name, Active FROM levels WHERE ID = $act")->fetch_assoc()){
        
        // activate/deactivate
        if ($db->query("UPDATE levels SET Active = IF(Active = 0, 1, 0) WHERE ID = $act")){
            $msg = '<span style="font-style: italic; color: blue;">O estágio \'' . htmlentities($levelInfo['Name'], 0, 'ISO-8859-1') . '\' foi ' . ($levelInfo['Active'] ? 'desativado' : 'ativado') . ' com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">O estágio não foi encontrado.</span>';
    }
    
}
elseif (isset($del)){
    // delete level
    
    // validate input
    if (isNum($del) && $levelName = $db->query("SELECT Name FROM levels WHERE ID = $del")->fetch_row()[0]){
        
        // check if level is in use
        if (!!$db->query("SELECT COUNT(*) FROM classes WHERE Level = $del")->fetch_row()[0]){
            $msg = '<span style="font-style: italic; color: red;">O estágio \'' . htmlentities($levelName, 0, 'ISO-8859-1') . '\' está em uso e não pode ser removido.</span>';
        }
        // remove level
        elseif ($db->query("DELETE FROM levels WHERE ID = $del")){
            $msg = '<span style="font-style: italic; color: blue;">O estágio \'' . htmlentities($levelName, 0, 'ISO-8859-1') . '\' foi removido com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">O estágio não foi encontrado.</span>';
    }
    
}
elseif (isset($newlvl) || isset($newPrId)){
    // add new level
    
    // remove white spaces
    $newlvl = trim($newlvl);
    
    // validate input
    if (!strlen($newlvl)){
        $msg = '<span style="font-style: italic; color: red;">A descrição inserida não é válida.</span>';
    }
    // check if the submitted program id is found as a key in programs array
    elseif (!isNum($newPrId) || !in_array($newPrId, array_keys($programs))){
        $msg = '<span style="font-style: italic; color: red;">O programa selecionado não é válido..</span>';
    }
    else {
        
        // check if level already exists
        if (!!$db->query("SELECT COUNT(*) FROM levels WHERE Name = '" . $db->real_escape_string($newlvl) . "'")->fetch_row()[0]){
            $msg = '<span style="font-style: italic; color: red;">O estágio \'' . htmlentities($newlvl, 0, 'ISO-8859-1') . '\' já existe no banco de dados.</span>';
        }
        // insert data
        elseif ($db->query("INSERT INTO levels (Name, Program) VALUES ('" . $db->real_escape_string($newlvl) . "', $newPrId)")){
            $msg = '<span style="font-style: italic; color: blue;">O estágio \'' . htmlentities($newlvl, 0, 'ISO-8859-1') . '\' foi inserido com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    
}
elseif (isset($editLevelId)){
    // rename level
    
    $lvlNewName = trim(getPost('lvlnewname'));
    
    // validate input
    if (!strlen($lvlNewName)){
        $msg = '<span style="font-style: italic; color: red;">A descrição inserida não é válida.</span>';
    }
    elseif (!isNum($editLevelId) || !$db->query("SELECT 1 FROM levels WHERE ID = $editLevelId")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">O estágio não é válido.</span>';
    }
    elseif (!!$db->query("SELECT COUNT(*) FROM levels WHERE ID != $editLevelId AND Name = '" . $db->real_escape_string($lvlNewName) . "'")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">O estágio \'' . htmlentities($lvlNewName, 0, 'ISO-8859-1') . '\' já existe no banco de dados.</span>';
    }
    elseif ($db->query("UPDATE levels SET Name = '" . $db->real_escape_string($lvlNewName) . "' WHERE ID = $editLevelId")){
        $msg = '<span style="font-style: italic; color: blue;">O estágio foi renomeado com sucesso.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
    }
    
}
elseif (isset($editPrgoLvlId) || isset($editPrId)){
    // update level
    
    if (!isNum($editPrgoLvlId) || !$db->query("SELECT 1 FROM levels WHERE ID = $editPrgoLvlId")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">O estágio não é válido.</span>';
    }
    // check if the submitted program id is found as a key in programs array
    elseif (!isNum($editPrId) || !in_array($editPrId, array_keys($programs))){
        $msg = '<span style="font-style: italic; color: red;">O programa selecionado não é válido.</span>';
    }
    elseif ($db->query("UPDATE levels SET Program = $editPrId WHERE ID = $editPrgoLvlId")){
        $msg = '<span style="font-style: italic; color: blue;">O programa foi alterado com sucesso.</span>';
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
    
    <link href="dropdown/dropdownMenu.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="dropdown/dropdownMenu.js"></script>
       
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
            DropdownMenu.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selProg')) initializeSelect(element('selProg'));
            if (element('selProg2')) initializeSelect(element('selProg2'));
            
        };
        
        function menuClicked(menu, lvlId, prgId){
            
            switch (menu){
                case 1:
                    showEditBox(lvlId);
                    break;
                case 2:
                    showEditProgBox(lvlId, prgId);
                    break;
                case 3:
                    deleteLevel(lvlId);
                    break;
                case 4:
                    activate(lvlId);
                    break;
                default:
            }
            
        }
        
        function deleteLevel(levelId){
            
            // fetch description
            element('spRemDesc').innerHTML = "'" + element('spLvl' + levelId).innerHTML.trim() + "'";
            
            element('hidRem').value = levelId;
            
            element('overlay').style.visibility = 'visible';
            element('remLevel').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('remLevel').style.opacity = '1';
            
        }
        
        function showEditProgBox(levelId, progId){
            
            // display level name
            element('tdEditLevelProg').innerHTML = element('spLvl' + levelId).innerHTML.trim();
            
            // select current program
            var selProg = element('selProg2');
            
            selProg.selectedIndex = 0;
                
            for (var i = 1; i < selProg.options.length; i++){
                if (selProg.options[i].value == progId){
                    selProg.options[i].selected = true;
                    break;
                }
            }

            styleSelectBox(selProg);
            
            // set hidden input
            element('hidLvl2').value = levelId;
            
            // sisplay box
            element('overlay').style.visibility = 'visible';
            element('editLevelProg').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('editLevelProg').style.opacity = '1';
            
        }
        
        function showEditBox(levelId){
            
            // fetch description
            element('tdEditLevel').innerHTML = element('spLvl' + levelId).innerHTML.trim();
            
            element('hidLvl').value = levelId;
                
            element('overlay').style.visibility = 'visible';
            element('editLevel').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('editLevel').style.opacity = '1';
            
        }
        
        function activate(levelId){
            
            // create form
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = 'managelevels.php';

            document.body.appendChild(frm);

            // create input
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'act';
            inp.value = levelId;

            frm.appendChild(inp);
            frm.submit();
            
        }
        
        function showNewLevelBox(){
            element('txtNewLevel').value = '';
            element('selProg').selectedIndex = 0;
            element('selProg').style.fontStyle = 'italic';
            element('overlay').style.visibility = 'visible';
            element('newLevel').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('newLevel').style.opacity = '1';
        }
        
        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('helpBox').style.opacity = '1';
        }
        
        function hideBoxes(){
            element('newLevel').style.opacity = '0';
            element('editLevel').style.opacity = '0';
            element('helpBox').style.opacity = '0';
            element('remLevel').style.opacity = '0';
            element('editLevelProg').opacity = '0';
            element('overlay').style.opacity = '0';
            element('overlay').style.visibility = 'hidden';
            element('newLevel').style.visibility = 'hidden';
            element('editLevel').style.visibility = 'hidden';
            element('helpBox').style.visibility = 'hidden';
            element('remLevel').style.visibility = 'hidden';
            element('editLevelProg').style.visibility = 'hidden';
        }
        
        function validateEdit(){
            
            if (!element('txtEditLevel').value.trim().length) {
                alert('Por favor insira a descrição');
                element('txtEditLevel').focus();
                return false;
            }
            
            return true;
            
        }
        
        function validateEditProg(){
            
            if (element('selProg2').selectedIndex === 0){
                alert('Por favor selecione o programa.');
                return false;
            }
            
            return true;
            
        }
        
        function validateNewLevel(){
            
            if (!element('txtNewLevel').value.trim().length){
                alert('Por favor insira a descrição');
                element('txtNewLevel').focus();
                return false;
            }
            
            if (!element('selProg').selectedIndex){
                alert('Por favor selecione o programa.');
                return false;
            }
            
            return true;
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="managelevels.php">
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
            
            <span style="font-weight: bold;">Gerenciamento de Estágios <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/></span>
            <hr/>
            <button onclick="showNewLevelBox();"><img src="<?php echo IMAGE_DIR; ?>plus.png"> Adicionar Estágio</button>
<?php

$result = $db->query("SELECT levels.*, programs.Name AS ProgName FROM levels JOIN programs ON levels.Program = programs.ID ORDER BY levels.Name");

if ($result->num_rows){
?>
            <div style="font-style: italic; color: red; font-size: 13px; padding-top: 5px;">* Estágios/programas em vermelhor estão inativos.</div>
            <table class="tbl">
                <tr style="background-color: #61269e; color: white;">
                    <td>&nbsp;</td>
                    <td style="width: 50%;">Estágio</td>
                    <td style="width: 50%;">Programa</td>
                </tr>
<?php
    
    $bgcolor = null;
    
    while ($row = $result->fetch_assoc()){
        
        $bgcolor = ($bgcolor == '#e1e1e1') ? '#ffffff' : '#e1e1e1';
        
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td>
                        <ul class="dropdownMenu">
                            <li>
                                <span><img src="<?php echo IMAGE_DIR; ?>list.png"></span>
                                <ul style="min-width: 150px;">
                                    <li><a href="#" onclick="menuClicked(1, <?php echo $row['ID'] . ', ' . $row['Program']; ?>); return false;"><img src="<?php echo IMAGE_DIR; ?>pencil1.png"/> &nbsp; Renomear</a></li>
                                    <li><a href="#" onclick="menuClicked(2, <?php echo $row['ID'] . ', ' . $row['Program']; ?>); return false;"><img src="<?php echo IMAGE_DIR; ?>refresh.png"/> &nbsp; Alterar Programa</a></li>
                                    <li><a href="#" onclick="menuClicked(3, <?php echo $row['ID'] . ', ' . $row['Program']; ?>); return false;"><img src="<?php echo IMAGE_DIR; ?>recycle2.png"/> &nbsp; Remover</a></li>
                                    <li><a href="#" onclick="menuClicked(4, <?php echo $row['ID'] . ', ' . $row['Program']; ?>); return false;"><img src="<?php echo IMAGE_DIR . ($row['Active'] ? 'off' : 'on'); ?>.png"/> &nbsp; <?php echo ($row['Active'] ? 'Desativar' : 'Ativar'); ?></a></li>
                                </ul>
                            </li>
                        </ul>
                    </td>
                    <td>
                        <?php echo '<span id="spLvl' . $row['ID'] . '"' . ($row['Active'] ? '' : ' style="color: red;"') . '>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</span>' . PHP_EOL; ?>
                    </td>
                    <td><?php echo (in_array($row['Program'], array_keys($programs)) ? htmlentities($row['ProgName'], 0, 'ISO-8859-1') : '<span style="color: red;">' . htmlentities($row['ProgName'], 0, 'ISO-8859-1') . '</span>'); ?></td>
                </tr>
<?php } ?>
            </table>
            <br/>
                                   
<?php    
}
else {
    echo '<div style="font-style: italic; color: red; padding: 10px 0 10px 0;">Não há estágios no banco de dados.</div>';
}

$result->close();

?>
        </div>
        
        
    </div>

    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay"></div>
    <div class="helpBox" id="newLevel" style="width: 420px; height: 140px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Adicionar Estágio</span>
        <hr/>
        <form action="managelevels.php" method="post">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: right;">Descrição:</td>
                <td style="width: 100%;"><input type="text" id="txtNewLevel" name="newlvl" maxlength="100" style="width: 300px;" autocomplete="off"/></td>
            </tr>
            <tr>
                <td style="text-align: right;">Programa:</td>
                <td>
                    <select id="selProg" name="newprogid" style="width: 200px;">
                        <option value="0">- Selecione -</option>
<?php

foreach ($programs as $prId => $prName) {
    echo '<option value="' . $prId . '">' . htmlentities($prName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
}

?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td style="width: 100%;">
                    <input type="submit" value="Salvar" onclick="return validateNewLevel();"/>
                    <input type="button" value="Cancelar" onclick="hideBoxes();"/>
                </td>
            </tr>
        </table>
        </form>
    </div>
    <div class="helpBox" id="editLevelProg" style="width: 520px; height: 140px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Alterar Programa</span>
        <hr/>
        <form action="managelevels.php" method="post">
        <table style="width: 100%;">
            <tr>
                <td style="white-space: nowrap; text-align: right; vertical-align: top;">Estágio:</td>
                <td style="width: 100%;" id="tdEditLevelProg"></td>
            </tr>
            <tr>
                <td style="text-align: right;">Programa:</td>
                <td>
                    <select id="selProg2" name="editprogid" style="width: 200px;">
                        <option value="0">- Selecione -</option>
<?php

foreach ($programs as $prId => $prName) {
    echo '<option value="' . $prId . '">' . htmlentities($prName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
}

?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td style="width: 100%;">
                    <input type="submit" id="btnEditSubmit" value="Salvar" onclick="return validateEditProg();"/>
                    <input type="button" value="Cancelar" onclick="hideBoxes();"/>
                </td>
            </tr>
        </table>
        <input type="hidden" id="hidLvl2" name="editprglvlid"/>
        </form>
    </div>
    <div class="helpBox" id="editLevel" style="width: 520px; height: 140px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Renomear Estágio</span>
        <hr/>
        <form action="managelevels.php" method="post">
        <table style="width: 100%;">
            <tr>
                <td style="white-space: nowrap; text-align: right; vertical-align: top;">Descrição Atual:</td>
                <td style="width: 100%;" id="tdEditLevel"></td>
            </tr>
            <tr>
                <td style="white-space: nowrap; text-align: right;">Nova Descrição:</td>
                <td style="width: 100%;"><input type="text" id="txtEditLevel" name="lvlnewname" maxlength="100" style="width: 95%;" autocomplete="off"/></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td style="width: 100%;">
                    <input type="submit" id="btnEditSubmit" value="Salvar" onclick="return validateEdit();"/>
                    <input type="button" value="Cancelar" onclick="hideBoxes();"/>
                </td>
            </tr>
        </table>
        <input type="hidden" id="hidLvl" name="editlvlid"/>
        </form>
    </div>
    <div class="helpBox" id="remLevel" style="width: 520px; height: 130px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Remover Estágios</span>
        <hr/>
        
        <form action="managelevels.php" method="post">
        <div style="height: 60px;">O estágio <span id="spRemDesc" style="font-style: italic; font-weight: bold;"></span> será removido permanentemente. Deseja continuar?</div>
        <div style="text-align: center; padding: 10px;">
            <input type="submit" value="OK" style="width: 70px;"/> &nbsp;
            <input type="button" value="Cancelar" style="width: 70px;" onclick="hideBoxes();"/>
        </div>
        <input type="hidden" id="hidRem" name="del"/>
        </form>
    </div>
    <div class="helpBox" id="helpBox" style="width: 550px; height: 290px;">
        <div class="closeImg" onclick="hideBoxes();"></div>
        <span style="font-weight: bold;">Ajuda - Gerenciar Estágios</span>
        <hr/>
        <ul style="line-height: 150%;">
            <li>Click no botão <span style="font-style: italic;">'Adicionar Estágio'</span> para inserir um novo estágio.</li>
            <li>Click no menu <img src="<?php echo IMAGE_DIR; ?>list.png" style="width: 12px; height: 12px;"/> e selecione uma das opções:</li>
            <ul>
                <li>Selecione <span style="font-style: italic;">'Editar'</span> para modificar um estágio existente.</li>
                <li>Selecione <span style="font-style: italic;">'Alterar Programa'</span> para modificar o programa referente ao estágio.</li>
                <li>Selecione <span style="font-style: italic;">'Remover'</span> para remover permanentemente um estágio.<br/>
                    <span style="color: red;">Atenção: </span> Para que um estágio existente seja removido, é necessário que o
                    mesmo não tenha sido atribuído a uma turma, caso esteja, a remoção não será efetuada.</li>
                <li>Selecione <span style="font-style: italic;">'Desativar' ou 'Ativar'</span> para desativar ou reativar um estágio.</li>
            </ul>
        </ul>
    </div>
        
</body>
</html>
<?php

$db->close();

?>