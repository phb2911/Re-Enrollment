<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once '../genreq/date_functions.php';

define('CRLF', "\r\n");

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn(true)){
    $db->close();
    die('<span style="font-style: italic; color: red;">Você não tem acesso a esta página.</style>');
}

$type = $_GET['t'];

if (!isset($type) || !preg_match('/^([0-9])+$/', $type)){
    $db->close();
    die('<span style="font-style: italic; color: red;">Parametros inválidos (0).</style>');
}

if ($type == 1){
    
    $uid = $_GET['uid'];
    $range = $_GET['r'];
    $d1 = $_GET['d1'];
    $d2 = $_GET['d2'];
    
    if (!preg_match('/^([0-9])+$/', $uid) || !$userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]){
        $errCode = '1.1';
    }
    elseif (!preg_match('/^([0-9])+$/', $range) || $range > 3){
        $errCode = '1.2';
    }
    elseif ($range == 2 && (!isValidDate($d1) || !isValidDate($d2))){
        $errCode = '1.3';
    }
    
    if (isset($errCode)){
        $db->close();
        die('<span style="font-style: italic; color: red;">Parametros inválidos (' . $errCode . ').</style>');
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Admin - Imprimir Relatório</title>
    
    <style type="text/css">
        
        div.main {
            width: 800px;
            margin-left: auto; 
            margin-right:auto;
            margin-top: 0px;
            position: relative;
            font-family: calibri;
        }
        
        table.headder td {
            padding: 0;
        }
        
        table.main {
            width: 100%;
            border-collapse: collapse;
        }
        
        table.main td {
            padding: 5px;
            border-top: #404040 solid 1px;
            border-bottom: #404040 solid 1px;
        }
        
    </style>
    
    <style type="text/css" media="print">
        
        .noprint {
            display: none;
        }
               
    </style>
    
    <script type="text/javascript">
    
        
    
    </script>
    
</head>
<body>

    <div class="main">
        
        <img src="<?php echo IMAGE_DIR; ?>logo2.jpg" style="vertical-align: middle;"/>
        
<?php

if ($type == 1){
    displayEventReport($uid, $userName, $range, $d1, $d2);
}
elseif ($type == 2){
    displayLoginIdReport();
}
else {
    echo '<br/><br/><span style="font-style: italic; color: red;">Parametros inválidos (a).</style>';
}

?>
        
    </div>
    
</body>
</html>
<?php

$db->close();

//--------------------------------------------------------

function displayEventReport($uid, $userName, $range, $date1, $date2){
    
    global $db;
    
    if ($range == 0){
        // last 30 days
        $t = time() - (60*60*24*30);
        $q = 'AND EventDate >= ' . $t;
        $p = date('d/m/Y', $t) . ' a ' . date('d/m/Y', time());
    }
    elseif ($range == 1){
        // last six months
        $t = time() - (60*60*24*183);
        $q = 'AND EventDate >= ' . $t;
        $p = date('d/m/Y', $t) . ' a ' . date('d/m/Y', time());
    }
    elseif ($range == 2){
        // between two dates
        if (compareDates($date1, $date2) == 1){
            // swap dates
            $tmpDate = $date1;
            $date1 = $date2;
            $date2 = $tmpDate;
        }
        
        $q = 'AND EventDate >= ' . strtotime(parseDate($date1) . ' 00:00:00') . ' AND EventDate <= ' . strtotime(parseDate($date2) . ' 23:59:59');
        
        $p = $date1 . ' a ' . $date2;
        
    }
    elseif ($range == 3){
        // display all
        $q = '';
        $p = 'Todos';
    }
        
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%; padding: 5px;" colspan="2">
                    <span style="font-weight: bold; font-size: 21px;">Lista de Eventos</span>
                </td>
            </tr>
            <tr>
                <td style="width: 100%; padding: 5px;" colspan="2">
                    <span style="font-size: 19px;">Colaborador: <span style="font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?></span></span>
                </td>
            </tr>
            <tr>
                <td style="width: 100%; padding: 5px;">
                    <span style="font-size: 19px;">Período: <?php echo $p; ?></span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo IMAGE_DIR; ?>printer.png" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
        <br/>
<?php

    $result = $db->query("SELECT `events`.ID, `events`.Description, `events`.EventDate, event_reasons.Description AS Reason FROM `events` JOIN event_reasons ON `events`.Reason = event_reasons.ID WHERE User = $uid $q ORDER BY EventDate DESC");
    
    if ($result->num_rows){

?>
        <table class="main">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 15%; border-left: #404040 solid 1px;">Data</td>
                <td style="width: 30%;">Motivo</td>
                <td style="width: 55%; border-right: #404040 solid 1px;">Descrição</td>
            </tr>
<?php
        while ($row = $result->fetch_assoc()){
?>
            <tr>
                <td style="border-left: #404040 solid 1px;"><?php echo date('d/m/Y', $row['EventDate']); ?></td>
                <td><?php echo htmlentities($row['Reason'], 0, 'ISO-8859-1'); ?></td>
                <td style="border-right: #404040 solid 1px;"><?php echo nl2br(htmlentities($row['Description'], 0, 'ISO-8859-1')); ?></td>
            </tr>
<?php
        }
?>
        </table>
        <p>&nbsp;</p>
<?php
    }
    else {
        echo '<span style="font-style: italic; color: red; font-size: 18px;">Este colaborador não possui eventos no período selecionado.</span>' . CRLF;
    }
    
    $result->close();
    
}

//--------------------------------------------------------

function displayLoginIdReport(){
    
    global $db;
    
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%;">
                    <span style="font-weight: bold; font-size: 21px;">Lista de Login IDs</span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo IMAGE_DIR; ?>printer.png" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
        <br/>
        <table class="main">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 55%; border-left: #404040 solid 1px;">Nome</td>
                <td style="width: 22%;">Status</td>
                <td style="width: 23%; border-right: #404040 solid 1px;">Login ID</td>
            </tr>
<?php

    $result = $db->query("SELECT ID, Name, LoginID, CASE WHEN Status = 0 THEN 'Professor' WHEN Status = 1 THEN 'Professor/Admin' WHEN Status = 2 THEN 'Admin' END AS StatusName FROM users WHERE Blocked = 0 ORDER BY Name");
    
    while ($row = $result->fetch_assoc()){
?>
            <tr>
                <td style="border-left: #404040 solid 1px;"><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></td>
                <td><?php echo $row['StatusName']; ?></td>
                <td style="border-right: #404040 solid 1px;"><?php echo htmlentities($row['LoginID'], 0, 'ISO-8859-1'); ?></td>
            </tr>
<?php 
    }
    
    $result->close();

?>
        </table>
        <p>&nbsp;</p>
<?php
    
}

?>