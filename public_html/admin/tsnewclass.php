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

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Admin - TS - New Class</title>
    
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: 500px; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <div style="color: red; font-style: italic; padding-right: 10px;"><?php echo $msg; ?></div>
            </div>
            <br/>
        </div>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Talk Session - Adicionar Nova Turma</span>
            <hr/>
            
        </div>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>