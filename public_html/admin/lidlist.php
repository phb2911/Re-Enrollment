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
    <title>YCG Admin - Lista de Login IDs</title>
    
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
    
        function openReport(){
            window.open('printrept.php?t=2', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');
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
        <div class="panel" style="width: 800px; left: 0; right: 0; margin: auto;">
                
            <span style="font-weight: bold;">Lista de Login IDs</span>
            <hr/>
            <table style="border-collapse: collapse; width: 100%;">
                <tr>
                    <td style="font-style: italic; color: red; font-size: 12px; width: 100%; padding: 0;">* Apenas colaboradores ativos são exibidos.</td>
                    <td style="padding: 0 5px 0 0;"><img src="<?php echo IMAGE_DIR; ?>print.png" style="cursor: pointer;" title="Versão para impressão" onclick="openReport();"/></td>
                </tr>
            </table>
            <table style="width: 100%; border: #fd7706 solid 1px;">
                <tr style="background-color: #fd7706; color: #ffffff;">
                    <td style="width: 50%;">Colaborador</td>
                    <td style="width: 25%;">Status</td>
                    <td style="width: 25%;">Login ID</td>
                </tr>
<?php

$result = $db->query("SELECT ID, Name, LoginID, CASE WHEN Status = 0 THEN 'Professor' WHEN Status = 1 THEN 'Professor/Admin' WHEN Status = 2 THEN 'Admin' END AS StatusName FROM users WHERE Blocked = 0 ORDER BY Name");

$bgcolor = '';

while ($row = $result->fetch_assoc()){
    
    $bgcolor = ($bgcolor == '#c6c6c6') ? '#f1f1f1' : '#c6c6c6';
    
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></td>
                    <td><?php echo $row['StatusName']; ?></td>
                    <td><?php echo htmlentities($row['LoginID'], 0, 'ISO-8859-1'); ?></td>
                </tr>
<?php

}

$result->close();

?>
                
            </table>
            <div style="text-align: right; padding: 5px 5px 0 0;"><img src="<?php echo IMAGE_DIR; ?>print.png" style="cursor: pointer;" title="Versão para impressão" onclick="openReport();"/></div>
        </div>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>