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

// fetch campaigns and their index
$camps = array();

$result = $db->query("SELECT campaigns.ID, campaignName(campaigns.ID) AS Name, campaigns.Open, ppy_calc_index.CalcIndex FROM campaigns LEFT JOIN ppy_calc_index ON campaigns.ID = ppy_calc_index.Campaign ORDER BY Name DESC");

while ($row = $result->fetch_assoc()){
    
    $camps[intval($row['ID'], 10)] = $row;
    
}

$result->close();

// new campaing id and current camp
$newCid = getPost('ncid');

// check if current campaign id submitted by select element
if (isNum($newCid) && isset($camps[intval($newCid, 10)])){
    $cInfo = $camps[intval($newCid, 10)];
    // save current campaign id into cookie
    setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN);
}
elseif (!$cInfo = getCampaignInfo($db, $camps)){ // get current campaign info
    // invalid campaign
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

$newIndex = getPost('newindex');
$editIndex = getPost('editindex');
$del = getPost('del');
$cid = getPost('cid');
$msg = null;

if (isset($newIndex)){
    
    if (!is_numeric($newIndex) || $newIndex <= 0 || !isNum($cid)){
        $msg = 'O valor atribuído não é válido.';
    }
    elseif (!isset($camps[$cid]) || !isset($camps[$cid]['Name'])){
        $msg = 'Parametros inválidos[0].';
    }
    elseif ($db->query("INSERT INTO ppy_calc_index (Campaign, CalcIndex) VALUES ($cid, $newIndex)")){

        $msg = '<span style="color: blue;">O novo índice da campanha ' . $camps[$cid]['Name'] . ' foi adicionado com sucesso.</span>';

        // set new index to campaing array
        $camps[$cid]['CalcIndex'] = $newIndex;

    }
    else {
        $msg = 'Error: ' . $db->error;
    }
    
}
elseif (isset($editIndex)){
    
    if (!is_numeric($editIndex) || $editIndex <= 0 || !isNum($cid)){
        $msg = 'O valor atribuído não é válido.';
    }
    elseif (!isset($camps[$cid]) || !isset($camps[$cid]['Name'])){
        $msg = 'Parametros inválidos[3].';
    }
    elseif ($db->query("UPDATE ppy_calc_index SET CalcIndex = $editIndex WHERE Campaign = $cid")){

        $msg = '<span style="color: blue;">O índice da campanha ' . $camps[$cid]['Name'] . ' foi alterado com sucesso.</span>';

        // set edited index to campaing array
        $camps[$cid]['CalcIndex'] = $editIndex;

    }
    else {
        $msg = 'Error: ' . $db->error;
    }
    
}
elseif (isset($del)){
    
    if (!isNum($del) || !isset($camps[$del]['Name'])){
        $msg = 'Parametros inválidos.[1]';
    }
    elseif ($db->query("DELETE FROM ppy_calc_index WHERE Campaign = $del")){

        $msg = '<span style="color: blue;">O índice da campanha ' . $camps[$del]['Name'] . ' foi removido com sucesso.</span>';

        // remove index from campaing array
        $camps[$del]['CalcIndex'] = null;
        
    }
    else {
        $msg = 'Error: ' . $db->error;
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - PPY Índice de Cálculo</title>
    
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
        
        function hideBoxes(){
            element('newIndex').style.opacity = '0';
            element('editIndex').style.opacity = '0';
            element('overlay').style.opacity = '0';
            element('overlay').style.visibility = 'hidden';
            element('newIndex').style.visibility = 'hidden';
            element('editIndex').style.visibility = 'hidden';
        }
        
        function addNew(cid){
            
            element('tdNewCamp').innerHTML = element('tdCampName' + cid).innerHTML;
            element('hidCampId').value = cid;
            
            element('overlay').style.visibility = 'visible';
            element('newIndex').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('newIndex').style.opacity = '1';
            
        }
        
        function editIndex(cid){
            
            element('tdEditCamp').innerHTML = element('tdCampName' + cid).innerHTML;
            element('txtEditIndex').value = element('tdIndex' + cid).innerHTML;
            element('hidCampIdEdit').value = cid;
            
            element('overlay').style.visibility = 'visible';
            element('editIndex').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('editIndex').style.opacity = '1';
            
        }
        
        function validateValue(val){
            
            if (val == '' || isNaN(val) || val <= 0){
                alert('O valor é inválido.');
                return false;
            }
            
            return true;
            
        }
        
        function removeIndex(cid){
            
            if(confirm('O índice será removido permanentemente. Deseja continuar?')){
                
                var frm = document.createElement('form');
                frm.method = 'post';
                frm.action = 'ppycalcindex.php';
                
                document.body.appendChild(frm);
                
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'del';
                input.value = cid;
                
                frm.appendChild(input);
                frm.submit();
                
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
                <form id="frmChangeCamp" method="post" action="ppycalcindex.php">
                Campanha: &nbsp;
                <select name="ncid" style="width: 100px; border-radius: 5px;" onchange="element('imgCampLoader').style.visibility = 'visible'; element('frmChangeCamp').submit();">
<?php

// create option
foreach ($camps as $cmp){
    echo '<option value="' . $cmp['ID'] . '"' . ($cmp['ID'] == $cInfo['ID'] ? ' selected="selected"' : '') . ($camps[intval($cmp['ID'], 10)]['Open'] ? ' style="font-weight: bold;"' : '') . '>' . $cmp['Name'] . '</option>' . PHP_EOL;
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
                <div style="color: red; font-style: italic; padding-right: 10px;"><?php echo $msg; ?></div>
            </div>
            <br/>
        </div>
<?php if (count($camps)){ ?>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Premio Performance Yázigi (PPY) - Índice de Cálculo</span>
            <hr/>
            
            <table style="border-collapse: collapse; border: #61269e solid 1px; width: 100%;">
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="3">Informações Gerais</td>
                </tr>
                <tr style="background-color: #b4b4b4;">
                    <td style="width: 45%;">Campanha</td>
                    <td style="width: 45%; text-align: center;">Índice de Cálculo</td>
                    <td style="width: 10%;"></td>
                </tr>
<?php

    $bgcolor = null;

    foreach ($camps as $camp) {
        
        $bgcolor = ($bgcolor == '#ffffff') ? '#f1f1f1' : '#ffffff';
        
        echo '    <tr style="background-color: ' . $bgcolor . ';">
                <td id="tdCampName' . $camp['ID'] . '">' . $camp['Name'] . '</td>
                <td id="tdIndex' . $camp['ID'] . '" style="text-align: center;">' . $camp['CalcIndex'] . '</td>
                <td style="text-align: center;">';
        
        if (isset($camp['CalcIndex'])){
            echo '<img src="' . IMAGE_DIR . 'pencil1.png" title="Modificar" style="cursor: pointer; vertical-align: middle;" onclick="editIndex(' . $camp['ID'] . ');"/> &nbsp; <img src="' . IMAGE_DIR . 'cancel2.png" title="Remover" style="cursor: pointer; vertical-align: middle;" onclick="removeIndex(' . $camp['ID'] . ');"/>';
        }
        else {
            echo '<img src="' . IMAGE_DIR . 'plus.png" title="Adicionar" style="cursor: pointer; vertical-align: middle;" onclick="addNew(' . $camp['ID'] . ');"/> &nbsp; <img src="' . IMAGE_DIR . 'trans.png" style="width: 16px; height: 16px;"/>';
        }
        
        echo '</td></tr>' . PHP_EOL;
        
    }

?>
            </table>
           
        </div>
        
        <div class="overlay" id="overlay"></div>
        <div class="helpBox" id="newIndex" style="width: 420px; height: 110px;">
            <div class="closeImg" onclick="hideBoxes();"></div>
            <span style="font-weight: bold;">PPY - Adicionar Novo Índice</span>
            <hr/>
            <form action="ppycalcindex.php" method="post">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td id="tdNewCamp" style="width: 100%; font-weight: bold;"></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Valor:</td>
                    <td>
                        <input type="text" id="txtNewIndex" name="newindex" style="width: 50px;"/> &nbsp;
                        <input type="submit" value="Salvar" onclick="return validateValue(element('txtNewIndex').value.trim());"/>
                    </td>
                </tr>
            </table>
            <input type="hidden" id="hidCampId" name="cid"/>
            </form>
        </div>
        <div class="helpBox" id="editIndex" style="width: 420px; height: 110px;">
            <div class="closeImg" onclick="hideBoxes();"></div>
            <span style="font-weight: bold;">PPY - Editar Índice</span>
            <hr/>
            <form action="ppycalcindex.php" method="post">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td id="tdEditCamp" style="width: 100%; font-weight: bold;"></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Valor:</td>
                    <td>
                        <input type="text" id="txtEditIndex" name="editindex" style="width: 50px;"/> &nbsp;
                        <input type="submit" value="Salvar" onclick="return validateValue(element('txtEditIndex').value.trim());"/>
                    </td>
                </tr>
            </table>
            <input type="hidden" id="hidCampIdEdit" name="cid"/>
            </form>
        </div>
        
<?php } ?>
        <p>&nbsp;</p>
    </div>
    
</body>
</html>
<?php

$db->close();

?>