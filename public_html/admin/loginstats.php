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
    header("Location: login.php?redir=" . urlencode($_SERVER['REQUEST_URI']));
    die();
}

$o = getGet('o');

// order by parameter
$orderBy = isset($o) && isNum($o) && $o >= 1 && $o <= 5 ? intval($o, 10) : 0;

switch ($orderBy) {
    case 1:
        $ob = "Name DESC";
        break;
    case 2:
        $ob = 'NumAccesses DESC, Name';
        break;
    case 3:
        $ob = 'NumAccesses, Name';
        break;
    case 4:
        $ob = 'LastAccess DESC, Name';
        break;
    case 5:
        $ob = 'LastAccess, Name';
        break;
    default:
        $ob = 'Name';
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Histórico de Acesso</title>
    
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
        
        span.spLink {
            cursor: pointer;
        }
        
        span.spLink:hover {
            text-decoration: underline;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
        };
        
        function sort(index){
            
            window.location = 'loginstats.php' + (index > 0 ? '?o=' + index : '');
            
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
            
            <span style="font-weight: bold;">Histórico de Acessos</span>
            <hr/>
            
            <span style="font-style: italic; color: red; font-size: 13px;">* Apenas os colaboradores ativos são exibidos.</span>
            <table class="list">
                <tr style="background-color: #fd7706; color: white;">
                    <td style="width: 60%;"><span class="spLink" onclick="sort(<?php echo ($orderBy == 0 ? '1' : '0'); ?>);">Colaborador</span><?php if ($orderBy == 0 || $orderBy == 1) echo ' &nbsp;<img src="' . IMAGE_DIR . ($orderBy == 1 ? 'sort_down.png' : 'sort_up.png') . '" style="vertical-align: top;"/>'; ?></td>
                    <td style="width: 15%; text-align: center;"><span class="spLink" onclick="sort(<?php echo ($orderBy == 2 ? '3' : '2'); ?>);">Acessos</span><?php if ($orderBy == 2 || $orderBy == 3) echo ' &nbsp;<img src="' . IMAGE_DIR . ($orderBy == 3 ? 'sort_down.png' : 'sort_up.png') . '" style="vertical-align: top;"/>'; ?></td>
                    <td style="width: 25%; text-align: center;"><span class="spLink" onclick="sort(<?php echo ($orderBy == 4 ? '5' : '4'); ?>);">Último Acesso</span><?php if ($orderBy == 4 || $orderBy == 5) echo ' &nbsp;<img src="' . IMAGE_DIR . ($orderBy == 5 ? 'sort_down.png' : 'sort_up.png') . '" style="vertical-align: top;"/>'; ?></td>
                </tr>
<?php

$result = $db->query("SELECT users.ID, users.Name, (SELECT MAX(access_log.DateTime) FROM access_log WHERE access_log.UserID = users.ID) AS LastAccess, " .
        "(SELECT COUNT(*) FROM access_log WHERE access_log.UserID = users.ID) AS NumAccesses FROM users WHERE users.Blocked = 0 ORDER BY $ob");

$bgcolor = null;

while($row = $result->fetch_assoc()){
    
    $bgcolor = ($bgcolor == '#c1c1c1') ? '#ffffff' : '#c1c1c1';
    
    echo '              <tr style="background-color: ' . $bgcolor . ';">
                    <td><a href="loginstatsbyuser.php?uid=' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a></td>
                    <td style="text-align: center;">' . $row['NumAccesses'] . '</td>
                    <td style="text-align: center;">' . (isset($row['LastAccess']) ? date('d/m/Y H:i:s', $row['LastAccess']) : '<span style="color: red; font-style: italic;">(nunca acessou)</span>') . '</td>
                </tr>';
    
}

$result->close();

?>
            </table>
        </div>
    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>