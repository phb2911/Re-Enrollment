<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/date_functions.php';
require_once '../genreq/genreq.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()){
    $db->close();
    eader("Location: " . LOGIN_PAGE);
    die();
}

$isAdmin = $loginObj->isAdmin();

if (!$isAdmin){
    $db->close();
    header("Location: .");
    die();
}

// start session
session_start();

$tbid = getSession('tbid');
$msg = null;


// get time bank info
if (isNum($tbid) && $tbinfo = $db->query("SELECT `Year`, StartDate, EndDate FROM tb_banks WHERE ID = $tbid AND Active = 1")->fetch_assoc()){
    
    $tbYear = $tbinfo['Year'];
    
    // convert dates to dd/MON/yyyy format
    $tbStartDate = formatLiteralDate($tbinfo['StartDate']);
    $tbEndDate = formatLiteralDate($tbinfo['EndDate']);
    
    $msg = '<span style="font-style: italic; color: blue;">Descontos inseridos com sucesso.</span>';
    
}
else {
    $msg = '<span style="font-style: italic; color: red;">Parametros inválidos.</span>';
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Adicionar Multiplos Descontos</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="../js/general.js"></script>
       
    <style type="text/css">
        
        .tbl td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
        };
        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="../images/banner3<?php echo ($isAdmin ? 'admin' : ''); ?>.jpg"/></a>
        
<?php

renderDropDown($db, $isAdmin);

?>
        <div id="msgBox" style="width: 800px; left: 0; right: 0; margin: auto; display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <br/>
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
        </div>
        <br/>
<?php
if (isset($tbYear)){
?>
        <div class="panel" style="width: 800px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Adicionar Descontos ao Banco de Horas</span>
            <hr/>
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Banco de Horas:</td>
                    <td style="width: 100%;">
                    <?php
                        echo '<span style="font-weight: bold;">' . $tbYear . '</span> <span style="font-style: italic;">(' . $tbStartDate . ' a ' . $tbEndDate . ')</span>';
                    ?>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Descrição:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities(getSession('description'), 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Data:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities(getSession('date'), 0, 'ISO-8859-1'); ?></td>
                </tr>
            </table>
            <table class="tbl" style="width: 100%; border: black solid 1px;">
                <tr style="color: white; background-color: #1b9a87;">
                    <td style="width: 50%;">Professor</td>
                    <td style="width: 35%;">Descrição</td>
                    <td style="width: 15%; text-align: center;">Duração</td>
                </tr>
<?php

    $usrArr = getSession('users');

    if (is_array($usrArr)) {
        
        // fetch teachers' names
        $q = "SELECT ID, Name FROM users WHERE ";
        $teachers = array();
        $orFlag = false;
        
        foreach ($usrArr as $uid => $tinfo) {
            
            if ($orFlag) $q .= "OR ";
            
            $q .= "ID = $uid ";
            
            $orFlag = true;
            
        }
        
        $result = $db->query($q);
        
        while ($row = $result->fetch_assoc()){
            $teachers[$row['ID']] = $row['Name'];
        }
        
        $result->close();
        
        $bgcolor = null;
    
        foreach ($usrArr as $uid => $tinfo) {
            
            $dsc = $tinfo[0];
            $dur = $tinfo[1];
            
            $bgcolor = ($bgcolor == '#c6c6c6') ? '#f1f1f1' : '#c6c6c6';
            
            // add left zero to duration if necessary
            if (preg_match('/^\d{1}\:\d{2}$/', $dur)) $dur = '0' . $dur;
            
            echo '<tr style="background-color: ' . $bgcolor . ';"><td><a href=".?tbid=' . $tbid . '&uid=' . $uid . '">' . htmlentities($teachers[$uid], 0, 'ISO-8859-1') . '</a></td><td>' . htmlentities($dsc, 0, 'ISO-8859-1') . '</td><td style="text-align: center;">' . $dur . '</td></tr>' . PHP_EOL;
            
        }

    }
    else {
        echo '<tr><td style="font-style: italic; color: red;">Erro inesperado.</td></tr>' . PHP_EOL;
    }
    
    // kill the session variables
    session_unset();
    
?>
            </table>
        </div>
<?php } ?>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>