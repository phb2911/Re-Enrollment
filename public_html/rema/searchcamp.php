<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/genreq.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()) closeDbAndGoTo($db, LOGIN_PAGE);

$isAdmin = $loginObj->isAdmin();

$cid = getPost('cid');

// validate campaign
if (isset($cid) && isNum($cid) && !!$db->query("SELECT COUNT(*) FROM campaigns WHERE ID = $cid")->fetch_row()[0]){
    
    // store id in cookie
    setcookie('curCampId', $cid, 0, '/', COOKIE_DOMAIN);
    
    // redirect to appropriate page
    $redir = getGet('redir');
    
    closeDbAndGoTo($db, (validateRedir($redir) ? $redir : '.'));
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Buscar Campanha</title>
    
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
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selCamp')) styleSelectBox(element('selCamp'));
            
        };
        
        function submitForm(sel){
            if (sel.selectedIndex > 0) element('frmChangeCamp').submit();
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
        
<?php

renderDropDown($db, $isAdmin);

?>
            
        </div>
        
        <br/>
        <div class="panel" style="width: 400px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Buscar Campanha</span>
            <hr/>
<?php

$result = $db->query("SELECT ID, campaignName(ID) AS Name, Open FROM campaigns ORDER BY Name DESC");

if ($result->num_rows){
?>
            
            <table style="width: 100%;">
                <tr>
                    <td style="white-space: nowrap;">Selecione o semestre:</td>
                    <td style="width: 100%;">
                        <form id="frmChangeCamp" method="post" action="searchcamp.php<?php if (strlen($_SERVER['QUERY_STRING'])) echo '?' . $_SERVER['QUERY_STRING']; ?>">
                        <select id="selCamp" name="cid" onchange="styleSelectBox(this); submitForm(this);" onkeyup="styleSelectBox(this); submitForm(this);">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    while ($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;' . ($row['Open'] ? ' font-weight: bold;' : '') . '">' . $row['Name'] . '</option>' . PHP_EOL;
    }

?>
                            
                        </select>
                        </form>
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

    </div>
    
</body>
</html>
<?php

$db->close();

?>