<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';

define('CRLF', "\r\n");

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn(true)){
    $db->close();
    header("Location: login.php?redir=" . urlencode($_SERVER['REQUEST_URI']));
    die();
}

$uname = $_POST['uname'];

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Buscar Colaborador</title>
    
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
        
        function validateQuery(){
            
            if (!element('txtUserName').value.trim().length){
                alert('Por favor digite o nome do colaborador.');
                return false;
            }
            
            return true;
            
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
        
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Buscar Colaborador</span>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td>Nome:</td>
                    <td style="width: 100%;">
                        <form id="form1" action="searchuser.php" method="post" onsubmit="return validateQuery();">
                            <input type="text" id="txtUserName" name="uname" value="<?php echo htmlentities($uname, 3, 'ISO-8859-1'); ?>" style="width: 370px;"/>
                            <img src="<?php echo IMAGE_DIR; ?>search.png" style="width: 24px; height: 24px; vertical-align: bottom; cursor: pointer;" onclick="if (validateQuery()) element('form1').submit();"/>
                        </form>
                    </td>
                </tr>
            </table>     

        </div>
<?php

if (isset($uname)){

    echo '<br/><div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">' . CRLF;
    
    $result = $db->query("SELECT ID, Name FROM users WHERE Name LIKE '%" . $db->real_escape_string($uname) . "%' ORDER BY LOCATE('" . $db->real_escape_string($uname) . "', Name)");
    
    echo '<div style="font-weight: bold; text-align: center; font-size: 12px;">' . $result->num_rows . ' resultado(s) encontrdo(s).</div>' . CRLF;
    
    while ($row = $result->fetch_assoc()){
        
        $bgcolor = ($bgcolor == '#f0f0f0') ? '#ffffff' : '#f0f0f0';
        
        echo '<div style="padding: 5px; background-color: ' . $bgcolor . ';"><a href="user.php?uid=' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a></div>' . CRLF;
        
    }
    
    $result->close();
    
    echo '</div>' . CRLF;
} 

?>
    </div>
    
</body>
</html>
<?php

$db->close();

?>