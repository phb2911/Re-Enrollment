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

if (!$isAdmin) closeDbAndGoTo($db, ".");

$openCamp = ($openCampId = $db->query("SELECT ID FROM campaigns WHERE Open = 1")->fetch_row()[0]);
$year = getPost('year');
$sem = getPost('sem');
$errMsg = null;

if (!$openCamp && (isset($year) || isset($sem))){
    
    if (!isNum($year) || +$year < 2000 || +$year > 2050){
        $errMsg = 'O ano não é válido.';
    }
    elseif ($sem != 1 && $sem != 2){
        $errMsg = 'O semestre não é válido.';
    }
    else {
        
        // check if campaign already exists
        if (!!$db->query("SELECT 1 FROM campaigns WHERE campaigns.Year = $year AND Semester = $sem")->fetch_row()[0]){
            $errMsg = 'A campanha ' . $year . '.' . $sem . ' já existe.';
        }
        elseif ($db->query("INSERT INTO campaigns (Year, Semester) VALUES ($year, $sem)")){
            
            // save new campaign id into cookie
            setcookie('curCampId', $db->insert_id, 0, '/', COOKIE_DOMAIN);
            
            closeDbAndGoTo($db, "camp.php");
                        
        }
        else {
            $errMsg = $db->error;
        }
        
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Nova Campanha</title>
    
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
        
        function validateFields(){
            
            if (element('selSem').selectedIndex === 0){
                element('divErrMsg').innerHTML = 'Por favor selecione o semestre.';
                return false;
            }
            
            return true;
            
        }
                        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
        
<?php

renderDropDown($db, $isAdmin);

?>
        
        <br/>
        
        <div class="panel" style="width: 400px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Criar Nova Campanha</span>
            <hr/>
<?php if (!$openCamp) { ?>
            <form id="form1" method="post" action="newcamp.php">
            
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Ano/Semestre:</td>
                    <td style="width: 100%;">
                        <select id="selYear" name="year" onchange="element('divErrMsg').innerHTML = '';">
<?php

if (!isset($year)) $year = date("Y");

for ($i = 2000; $i <= 2050; $i++){
    echo '<option value="' . $i . '"' . (+$year == $i ? ' selected="selected"' : '') . '>' . $i . '</option>' . PHP_EOL;
}

?>
                        </select>
                        .
                        <select id="selSem" name="sem" onchange="element('divErrMsg').innerHTML = '';">
                            <option value="0"></option>
                            <option value="1"<?php if ($sem == 1) echo ' selected="selected"'; ?>>1</option>
                            <option value="2"<?php if ($sem == 2) echo ' selected="selected"'; ?>>2</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><input type="submit" value="Salvar" onclick="return validateFields();"/></td>
                </tr>
            </table>
            
            </form>
<?php

}
else {
    echo '<div style="font-style: italic; color: red; padding: 5px;">Há uma campanha que ainda está aberta. Esta precisa ser fechada antes que uma nova seja criada.<br/></br>' . 
            'Clique <a href="camp.php">aqui</a> para voltar para a página inicial.</div>';
}

?>             
        </div>
        <br/>
        <div id="divErrMsg" style="color: red; width: 400px; left: 0; right: 0; margin: auto; font-style: italic;"><?php echo $errMsg; ?></div>
       
    </div>
    
</body>
</html>
<?php

$db->close();

?>