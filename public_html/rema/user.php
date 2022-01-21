<?php

// User page from REMA SITE

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
$myUid = $loginObj->userId;

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
    // invalid campaign
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

$uid = getGet('uid');
$del = getPost('del');
$isValid = false;
$msg = null;

if (isNum($uid) && $userInfo = $db->query("SELECT Name, LoginID,Status, Blocked FROM users WHERE ID = $uid")->fetch_assoc()){
    $isValid = true;
}
else {
    $msg = '<span style="font-style: italic; color: red;">Parametros inválidos.</span>';
}

if ($isValid && getPost('act') == '1'){
    // activate/deactivate user
    
    if ($db->query("UPDATE users SET Blocked = " . (!!$userInfo['Blocked'] ? '0' : '1') . " WHERE ID = $uid")){
        closeDbAndGoTo($db, "user.php?uid=$uid");
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
    }
    
}
elseif ($isValid && isNum($del)){
    
    if ($del == $myUid){
        $msg = '<span style="font-style: italic; color: red;">Apenas outro administrador pode remover este colaborador.</span>';
    }
    elseif (deleteUser($del, $msg)){
        $isValid = false;
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Colaborador</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        div.deleteDialog {
            position: fixed;
            width: 500px;
            height: 240px;
            margin: auto;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0; 
            background-color: white;
            font-family: calibri;
            border: #61269e solid 2px;
            border-radius: 5px;
            box-shadow: 5px 5px 5px #808080;
            padding: 10px;
            opacity: 0;
            visibility: hidden;
            z-index: 1050;
            
            transition-property: opacity;
            transition-duration: .2s;
            transition-delay: .2s;
            transition-timing-function: linear;
            
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
        };
        
        function actUser(){
            
            var form = element('form1');
            
            // clear all child nodes
            while (form.hasChildNodes()) {
                form.removeChild(form.lastChild);
            }
            
            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'act';
            hid.value = '1';
            
            form.appendChild(hid);
            form.submit();
            
        }
        
        function deleteUser(id){
            
            if (element('chkDel').checked){
                
                var form = element('form1');
            
                // clear all child nodes
                form.clearChildren(); // found in gen.js

                var hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'del';
                hid.value = id;

                form.appendChild(hid);
                form.submit();
                
            }
            else {
                alert('Selecione a opção "Remover Colaborador".');
            }
            
        }
        
        function deleteDialog(show){
            
            if (show){
                
                element('chkDel').checked = false;
                element('btnDel').disabled = true;
                
                element('overlay').style.visibility = 'visible';
                element('delDia').style.visibility = 'visible';
                element('overlay').style.opacity = '0.6';
                element('delDia').style.opacity = '1';
                
            }
            else {
                element('delDia').style.opacity = '0';
                element('overlay').style.opacity = '0';
                element('overlay').style.visibility = 'hidden';
                element('delDia').style.visibility = 'hidden';
            }
            
        }
        
        function viewCamp(uid, cid){
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = 'campbyuser.php?uid=' + uid;
            
            document.body.appendChild(frm);
            
            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'cid';
            hid.value = cid;
            
            frm.appendChild(hid);
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
                <form id="frmChangeCamp" method="post" action="user.php<?php if ($isValid) echo '?uid=' . $uid;?>">
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
<?php

if ($isValid){
    
?>
        <div class="panel">
            
            <span style="font-weight: bold;"><?php echo htmlentities($userInfo['Name'], 0, 'ISO-8859-1'); ?></span>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Login ID:</td>
                    <td><input type="text" style="width: 300px;" value="<?php echo htmlentities($userInfo['LoginID'], 3, 'ISO-8859-1'); ?>" readonly="readonly"/></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Situação:</td>
                    <td><input type="text" style="width: 300px;" value="<?php echo (!!$userInfo['Blocked'] ? 'Inativo' : 'Ativo'); ?>" readonly="readonly"/></td>
                </tr>
                <tr>
                    <td style="text-align: right; vertical-align: top;">Status:</td>
                    <td style="width: 100%;">
                        <img src="<?php echo IMAGE_DIR . ($userInfo['Status'] < 2 ? 'check1.jpg' : 'check2.jpg');?>"/> Professor<br/>
                        <img src="<?php echo IMAGE_DIR . ($userInfo['Status'] > 0 ? 'check1.jpg' : 'check2.jpg');?>"/> Administrador
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Último Acesso:</td>
                    <td><?php
                    
    if ($lastLogin = $db->query("SELECT MAX(DateTime) FROM access_log WHERE UserID = $uid")->fetch_row()[0]){
        // fetches last access and formats it.
        echo date('d/m/Y H:i:s', $lastLogin);
    }
    else {
        echo '<span style="font-style: italic; color: red;">(nunca acessado)</span>';
    }
    
    // set current address for links below
    $curAddr = (isset($_SERVER['SERVER_NAME']) && strpos(strtolower($_SERVER['SERVER_NAME']), '[server name goes here]') !== false ? '[domain name goes here]' : '../admin');
                    
                    ?></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button type="button" onclick="window.location = '<?php echo $curAddr . '/edituser.php?uid=' . $uid; ?>';"><img src="<?php echo IMAGE_DIR; ?>pencil1.png"/> Editar Colaborador</button>
                        <button type="button" onclick="deleteDialog(true);"<?php if ($myUid == $uid) echo ' disabled="disabled"'; ?>><img src="<?php echo IMAGE_DIR . ($myUid == $uid ? 'recycle.png' : 'recycle2.png'); ?>"/> Remover Colaborador</button>
                        <button type="button" onclick="actUser();"<?php if ($myUid == $uid) echo ' disabled="disabled"'; ?>><img src="<?php echo IMAGE_DIR . ($myUid == $uid ? 'on_gr.png' :(!!$userInfo['Blocked'] ? 'on.png' : 'off.png')); ?>"/> <?php echo (!!$userInfo['Blocked'] ? 'Ativar' : 'Desativar'); ?> Colaborador</button>
                        <button type="button" onclick="window.location = '<?php echo $curAddr . '/events.php?uid=' . $uid; ?>';"><img src="<?php echo IMAGE_DIR; ?>list.png"/> Lista de Eventos</button>
                        <button type="button" onclick="window.location = '<?php echo $curAddr . '/loginstatsbyuser.php?uid=' . $uid; ?>';"><img src="<?php echo IMAGE_DIR; ?>key.png"/> Histórico de Acessos</button>
                    </td>
                </tr>
            </table>
            
        </div>
        <br/>
        <div class="panel">
            
            <span style="font-weight: bold;">Campanhas</span>
            <hr/>
<?php

// $campArr[ID] = CampName;
$campArr = array();

$result = $db->query("SELECT classes.Campaign AS ID, CONCAT(campaigns.`Year`, '.', campaigns.`Semester`) AS CampName FROM classes LEFT JOIN campaigns " .
        "ON classes.Campaign = campaigns.ID LEFT JOIN students ON classes.ID = students.Class WHERE classes.User = $uid AND (SELECT COUNT(*) FROM students " .
        "WHERE students.Class = classes.ID LIMIT 1) > 0 GROUP BY classes.Campaign ORDER BY campaigns.Open DESC, CampName DESC");

while ($row = $result->fetch_assoc()){
    $campArr[intval($row['ID'], 10)] = $row['CampName'];
}

$result->close();

if (count($campArr)){
    
    echo '<table style="border-collapse: collapse; width: 100%; border: #61269e solid 1px;"><tr><td style="width: 80%; background-color: #61269e; color: white;">Campanha</td><td style="background-color: #61269e; color: white; text-align: center;">Rematriculados</td></tr>' . PHP_EOL;
    
    $bgcolor = null;
    
    foreach ($campArr as $campID => $campName) {
        
        $bgcolor = ($bgcolor == '#d1d1d1') ? '#ffffff' : '#d1d1d1';
        
        $rema = $db->query("SELECT PercentEnrolled($uid, $campID)")->fetch_row()[0];
        
        echo '<tr><td style="background-color: ' . $bgcolor . ';"><img src="' . IMAGE_DIR . 'magnifying_glass.png" title="Visualizar Campanha" style="cursor: pointer;" onclick="viewCamp(' . $uid . ',' . $campID . ');"/> &nbsp; ' . $campName . '</td><td style="background-color: ' . $bgcolor . '; text-align: center;">' . +$rema . '%</td></tr>' . PHP_EOL;
        
        
    }
    
    echo '</table>' . PHP_EOL;
    
}
else {
    echo '<div style="font-style: italic; color: red; padding: 5px;">Este colaborador não participou de nenhuma campanha de rematrícula.</div>' . PHP_EOL;
}

?>
        </div>
        
        <form id="form1" method="post" action="user.php?uid=<?php echo $uid; ?>"></form>
        
        <div class="overlay" id="overlay" style="background-color: #fff;" onclick="deleteDialog(false);"></div>
        <div class="deleteDialog" id="delDia">
            <span style="font-weight: bold;">Remover Colaborador</span>
            <hr/>

            <div style="padding: 10px;">
                <span style="color: red;">Atenção:</span> O colaborador <span style="font-weight: bold;"><?php echo htmlentities($userInfo['Name'], 0, 'ISO-8859-1'); ?></span> e todos os dados
                relacionados a este(a), tais como participações em campanhas, turmas, alunos, etc., serão removidos permanentemente.
                <p>Para continuar, marque a opção abaixo e click no botão "Remover":</p>
                <p style="padding-left: 15px;"><input type="checkbox" id="chkDel" onclick="element('btnDel').disabled = !this.checked;"/><label for="chkDel"> Remover colaborador</label></p>
            </div>
            <div style="text-align: right; padding-right: 10px;">
                <input type="button" id="btnDel" value="Remover" onclick="deleteUser(<?php echo $uid; ?>);"/>
                <input type="button" value="Cancelar" onclick="deleteDialog(false);"/>
            </div>
        </div>
        
<?php } ?>
        
    </div>
    
</body>
</html>
<?php

$db->close();

//-------------------------------

function deleteUser($uid, &$msg){
    
    global $db;
    
    $res = false;
    
    $db->query("DELETE FROM users WHERE ID = $uid");
        
    if ($db->affected_rows){

        $result = $db->query("SELECT ID FROM classes WHERE User = $uid");
        
        if ($result->num_rows){
            
            $q1 = "DELETE FROM students WHERE";
            $q2 = "DELETE FROM subcamp_classes WHERE";
            
            $flag = false;
            
            while ($cid = $result->fetch_row()[0]){
                
                if ($flag){
                    $q1 .= " OR";
                    $q2 .= " OR";
                }
                
                $q1 .= " Class = $cid";
                $q2 .= " Class = $cid";
                
                $flag = true;
                
            }
            
            $db->query($q1);
            $db->query($q2);
            
        }
                
        $result->close();

        $db->query("DELETE FROM tokens WHERE UserID = $uid");
        $db->query("DELETE FROM subcamp_results WHERE User = $uid");
        $db->query("DELETE FROM access_log WHERE UserID = $uid");
        $db->query("DELETE FROM classes WHERE User = $uid");
        
        $msg = '<span style="font-style: italic; color: blue;">Colaborador removido com sucesso.</span>';
        
        $res = true;

    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Colaborador não encontrado.</span>';
    }
    
    return $res;
    
}

?>