<?php

// User page from ADMIN SITE

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';

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
$msg = null;

if (isset($uid) && preg_match('/^([0-9])+$/', $uid) && $userInfo = $db->query("SELECT Name, Email, LoginID, Status, Blocked FROM users WHERE ID = $uid")->fetch_assoc()){
    $isValid = true;
}
else {
    $msg = '<span style="font-style: italic; color: red;">Parametros inválidos.</span>';
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
                
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
        };
        
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
        
        function viewCamp(uid, cid){
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = '<?php echo (isset($_SERVER['SERVER_NAME']) && strpos(strtolower($_SERVER['SERVER_NAME']), 'domain') !== false ? 'http://rema.domain.com' : 'http://localhost:88/rema'); ?>/campbyuser.php?uid=' + uid;
            
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
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="<?php echo IMAGE_DIR; ?>banner1.jpg"/></a>
        
<?php

renderDropDown($db);

?>
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
                    <td style="text-align: right;">E-mail:</td>
                    <td><input type="text" style="width: 300px;" value="<?php echo htmlentities($userInfo['Email'], 3, 'ISO-8859-1'); ?>" readonly="readonly"/></td>
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
                    
                    ?></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button type="button" onclick="window.location = 'edituser.php?uid=<?php echo $uid; ?>';"><img src="<?php echo IMAGE_DIR; ?>pencil1.png"/> Editar Colaborador</button>
                        <button type="button" onclick="window.location = 'events.php?uid=<?php echo $uid; ?>';"><img src="<?php echo IMAGE_DIR; ?>list.png"/> Lista de Eventos</button>
                        <button type="button" onclick="window.location = 'loginstatsbyuser.php?uid=<?php echo $uid; ?>';"><img src="<?php echo IMAGE_DIR; ?>key.png"/> Histórico de Acessos</button>
                        <button type="button" onclick="window.location = 'userprofile.php?uid=<?php echo $uid; ?>';"><img src="<?php echo IMAGE_DIR; ?>person.png"/> Perfil do Colaborador</button>
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
    
    echo '<table style="border-collapse: collapse; width: 100%; border: #fd7706 solid 1px;"><tr><td style="width: 80%; background-color: #fd5107; color: white;">Campanha</td><td style="background-color: #fd5107; color: white; text-align: center;">Rematriculados</td></tr>' . PHP_EOL;
    
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
        
<?php } ?>
        
    </div>
    
</body>
</html>
<?php

$db->close();

?>