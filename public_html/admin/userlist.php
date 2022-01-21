<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

define('ITEMS_PER_PAGE', 50);

require_once 'dropdown/dropdown.php';
require_once '../genreq/genreq.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn(true)){
    $db->close();
    header("Location: " . LOGIN_PAGE);
    die();
}

$situation = (isset($_GET['s']) && ($_GET['s'] === '1' || $_GET['s'] === '2')) ? intval($_GET['s'], 10) : 0;

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Lista de Colaboradores</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        div.menuButton {
            display: inline-block;
            padding: 5px;
            background-color: #fd7706;
            width: 18px;
            text-align: center;
            color: white;
            cursor: pointer;
        }
        
        div.menuButton2 {
            display: inline-block;
            padding: 5px;
            background-color: #fd7706;
            width: 18px;
            text-align: center;
            color: white;
            opacity: 0.5;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
        };
        
        function goToPage(pgNum, sit){
            window.location = 'userlist.php?s=' + sit + '&p=' + pgNum;
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
        <div class="panel">
            <span style="font-weight: bold;">Lista de Colaboradores</span>
            <hr/>
            <div style="padding: 10px;">
                Filtrar por Situação: &nbsp;
                <select style="width: 100px;" onchange="window.location = 'userlist.php?s=' + this.selectedValue();">
                    <option value="0"<?php if ($situation === 0) echo ' selected="selected"'; ?>>Ativos</option>
                    <option value="1"<?php if ($situation === 1) echo ' selected="selected"'; ?>>Inativos</option>
                    <option value="2"<?php if ($situation === 2) echo ' selected="selected"'; ?>>Todos</option>
                </select>
            </div>
        </div>
        <br/>
<?php

// fetch record count
$q = "SELECT COUNT(*) FROM `users`";

if ($situation === 0) $q .= " WHERE `Blocked` = 0";
elseif ($situation === 1) $q .= " WHERE `Blocked` = 1";

$numRec = (int)$db->query($q)->fetch_row()[0];

// calculate the number of pages
$numPages = (int)($numRec/ITEMS_PER_PAGE);

if ($numRec > $numPages * ITEMS_PER_PAGE) $numPages++;

// get and validate page number
$page = getGet('p');

if (!isNum($page) || $page < 1 || $page > $numPages){
    $page = 1;
}

buildPagingMenu($numRec, $numPages, $page, $situation);

?>
        <table style="width: 100%; border: #fd7706 solid 1px; border-collapse: collapse; box-shadow: 3px 3px 3px #808080;">
            <tr style="background-color: #fd7706; color: white;">
                <td style="width: 60%;">Colaborador</td>
                <td style="width: 25%;">Status</td>
                <td style="width: 15%; text-align: center;">Situação</td>
            </tr>
<?php

if ($numRec){
    
    // build query
    $q = "SELECT `ID`, `Name`, IF(`Blocked`, 'Inativo', 'Ativo') AS Situation, " .
         "IF(`Status`, IF(`Status` = 1, 'Professor/Administrador', 'Administrador'), 'Professor') AS Status " .
         "FROM `users`";

    if ($situation === 0) $q .= " WHERE `Blocked` = 0";
    elseif ($situation === 1) $q .= " WHERE `Blocked` = 1";
    
    $firstRec = ($page - 1) * ITEMS_PER_PAGE;

    $q .= " ORDER BY `Name` LIMIT $firstRec, " . ITEMS_PER_PAGE;

    $result = $db->query($q);
    
    $bgcolor = null;
    
    while ($row = $result->fetch_assoc()){
        
        $bgcolor = ($bgcolor == '#f0f0f0') ? '#ffffff' : '#f0f0f0';
        
?>
            <tr style="background-color: <?php echo $bgcolor;?>;">
                <td><?php echo '<a href="user.php?uid=' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a>';?></td>
                <td><?php echo $row['Status']; ?></td>
                <td style="text-align: center;"><?php echo $row['Situation']; ?></td>
            </tr>
<?php
        
    }
    
    $result->close();
    
}
else {
    echo '<tr><td style="font-style: italic; color: red; background-color: white;" colspan="3">Nenhum colaborador encontrado.</td></tr>';
}

?>
        </table>
<?php

buildPagingMenu($numRec, $numPages, $page, $situation);

?>      
    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

//-------------------------------------

function buildPagingMenu($numRec, $numPages, $page, $situation){
    
    if ($numRec){
        
        echo '<div style="padding: 5px 0 5px 0;">' . PHP_EOL;
        
        if ($page > 1){
            echo '<div class="menuButton" onclick="goToPage(1,' . $situation . ');">&lt;&lt;</div>' . PHP_EOL . '<div class="menuButton" onclick="goToPage(' . ($page - 1) . ',' . $situation . ');">&lt;</div>' . PHP_EOL;
        }
        else {
            echo '<div class="menuButton2">&lt;&lt;</div>' . PHP_EOL . '<div class="menuButton2">&lt;</div>' . PHP_EOL;
        }
        
        for ($i = 1; $i <= $numPages; $i++){
            echo '<div class="menuButton"' . ($i == $page ? ' style="background-color: red;"' : '') . ' onclick="goToPage(' . $i . ',' . $situation . ');">' . $i . '</div>' . PHP_EOL;
        }
        
        if ($page < $numPages){
            echo '<div class="menuButton" onclick="goToPage(' . ($page + 1) . ',' . $situation . ');">&gt;</div>' . PHP_EOL . '<div class="menuButton" onclick="goToPage(' . $numPages . ',' . $situation . ');">&gt;&gt;</div>' . PHP_EOL;
        }
        else {
            echo '<div class="menuButton2">&gt;</div>' . PHP_EOL . '<div class="menuButton2">&gt;&gt;</div>' . PHP_EOL;
        }
        
        echo '</div>' . PHP_EOL;
        
    }
    
}

?>