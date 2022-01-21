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

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Admin</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        table.list {
            width: 100%; 
            border: #fd7706 solid 1px;
            border-collapse: collapse;
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
        
        function goToPage(pgNum){
            window.location = 'campclosinglog.php?p=' + pgNum;
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
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Histórico de Fechamento de Campanha de Rematrícula</span>
            <hr/>
            
<?php

// fetch number of records
$numRec = (int)$db->query("SELECT COUNT(*) FROM `camp_closing_log`")->fetch_row()[0];

if ($numRec){
    
    // calculate the number of pages
    $numPages = (int)($numRec/ITEMS_PER_PAGE);

    if ($numRec > $numPages * ITEMS_PER_PAGE) $numPages++;

    // get and validate page number
    $page = getGet('p');

    if (!isNum($page) || $page < 1 || $page > $numPages){
        $page = 1;
    }
    
    buildPagingMenu($numRec, $numPages, $page);
    
?>
            <table class="list">
                <tr style="background-color: #fd7706; color: white;">
                    <td style="width: 20%;">Campanha</td>
                    <td style="width: 50%;">Administrador</td>
                    <td style="width: 30%;">Data</td>
                </tr>
<?php

    $firstRec = ($page - 1) * ITEMS_PER_PAGE;

    // fetch data
    $q = "SELECT `camp_closing_log`.`Date`, `users`.`ID` AS UserID, `users`.`Name`, " .
        "`campaignName`(`camp_closing_log`.`Campaign`) AS Campaign FROM `camp_closing_log` " .
        "LEFT JOIN `users` ON  `camp_closing_log`.`User` = `users`.`ID` " .
        "LEFT JOIN `campaigns` ON `camp_closing_log`.`Campaign` = `campaigns`.`ID` " .
        "ORDER BY `Date` DESC LIMIT $firstRec, " . ITEMS_PER_PAGE;
    
    $result = $db->query($q);
    
    $bgcolor = null;
    
    while ($row = $result->fetch_assoc()){
        
        $bgcolor = ($bgcolor == '#c1c1c1') ? '#ffffff' : '#c1c1c1';
        
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td><?php echo $row['Campaign']; ?></td>
                    <td><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></td>
                    <td><?php echo formatDate($row['Date']); ?></td>
                </tr>
<?php
    }
    
    $result->close();

?>
            </table>
<?php

    buildPagingMenu($numRec, $numPages, $page);
    
}
else {
    
    echo '<div style="padding: 5px; color: red; font-style: italic;">Não há registros de fechamento de campanha de rematrícula.</div>';
    
}

?>
            
        </div>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

//-------------------------------------

function formatDate($date){
    
    $pieces = explode(' ', $date);
    
    $datePieces = explode('-', $pieces[0]);
    
    return $datePieces[2] . '/' . $datePieces[1] . '/' . $datePieces[0] . ' ' . $pieces[1];
    
}

//-------------------------------------

function buildPagingMenu($numRec, $numPages, $page){
    
    if ($numRec > ITEMS_PER_PAGE){
        
        echo '<div style="padding: 5px 0 5px 0;">' . PHP_EOL;
        
        if ($page > 1){
            echo '<div class="menuButton" onclick="goToPage(1);">&lt;&lt;</div>' . PHP_EOL . '<div class="menuButton" onclick="goToPage(' . ($page - 1) . ');">&lt;</div>' . PHP_EOL;
        }
        else {
            echo '<div class="menuButton2">&lt;&lt;</div>' . PHP_EOL . '<div class="menuButton2">&lt;</div>' . PHP_EOL;
        }
        
        for ($i = 1; $i <= $numPages; $i++){
            echo '<div class="menuButton"' . ($i == $page ? ' style="background-color: red;"' : '') . ' onclick="goToPage(' . $i . ');">' . $i . '</div>' . PHP_EOL;
        }
        
        if ($page < $numPages){
            echo '<div class="menuButton" onclick="goToPage(' . ($page + 1) . ');">&gt;</div>' . PHP_EOL . '<div class="menuButton" onclick="goToPage(' . $numPages . ');">&gt;&gt;</div>' . PHP_EOL;
        }
        else {
            echo '<div class="menuButton2">&gt;</div>' . PHP_EOL . '<div class="menuButton2">&gt;&gt;</div>' . PHP_EOL;
        }
        
        echo '</div>' . PHP_EOL;
        
    }
    
}

?>