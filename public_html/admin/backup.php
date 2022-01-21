<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

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
    <title>YCG Rema - Backup</title>
    
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
        <div class="panel" style="width: 400px; left: 0; right: 0; margin: auto;">

            <span style="font-weight: bold;">Backup</span>
            <hr/>
            
            <div style="padding: 5px;">Click no botão abaixo para fazer o download do arquivo contendo o backup do banco de dados:</div>
            
            <div style="padding: 5px;"><button onclick="window.location='backup_gen.php';"><img src="<?php echo IMAGE_DIR; ?>cassette.png" style="vertical-align: middle;"/> Backup</button></div>
            
        </div>

    </div>
        
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

//------------------------------------------

function selectCampaign(){
    
    global $db;
    
?>
        
        <br/>
        <div class="panel" style="width: 400px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Relatório Por Unidade</span>
            <hr/>
<?php
    
    $result = $db->query("SELECT ID, CONCAT(`Year`, '.', `Semester`) AS Name, Open FROM campaigns ORDER BY Name DESC");

    if ($result->num_rows){
?>
            
            <table style="width: 100%;">
                <tr>
                    <td style="white-space: nowrap;">Selecione o semestre:</td>
                    <td style="width: 100%;">
                        <select id="selCamp" onchange="styleSelectBox(this);" onkeyup="styleSelectBox(this)">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

        while ($row = $result->fetch_assoc()){
            echo '<option value="' . $row['ID'] . '"' . (!!$row['Open'] ? ' selected="selected"' : '') . ' style="font-style: normal;">' . $row['Name'] . '</option>' . PHP_EOL;
        }

?>
                            
                        </select>
                        <img src="<?php echo IMAGE_DIR; ?>ok.png" style="vertical-align: bottom; cursor: pointer;" onclick="showReport();"/>
                    </td>
                </tr>
            </table>     
<?php
    }
    else {
        echo '<div style="padding: 5px; font-style: italic; color: red;">Não há campanhas de rematrícula disponíveis.</div>';
    }

    $result->close();
    
?>
        </div>
<?php
    
}

?>