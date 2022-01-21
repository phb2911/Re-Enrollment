<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/genreq.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()){
    $db->close();
    header("Location: " . LOGIN_PAGE);
    die();
}

$isAdmin = $loginObj->isAdmin();

if (!$isAdmin){
    $db->close();
    header("Location: .");
    die();
}

$year = intval(date('Y'), 10);
$msg = null;

if (getPost('postback') == '1'){
    
    $year = getPost('year');
    
    if (!isNum($year) || $year < 2000 || $year > 2099){
        $msg = 'O ano é inválido.';
        $year = intval(date('Y'), 10);
    }
    elseif (!!$db->query("SELECT COUNT(*) FROM tb_banks WHERE `Year` = $year")->fetch_row()[0]){
        $msg = 'Já existe um Banco de Horas relativo ao ano <span style="font-weight: bold;">' . $year . '</span> no banco de dados.';
    }
    else {
        
        // start and end dates
        $d1 = $year . '-02-01';
        $d2 = ($year + 1) . '-01-31';
        
        // insert data
        if ($db->query("INSERT INTO tb_banks (`Year`, StartDate, EndDate) VALUES ($year, '$d1', '$d2')")){

            $insertId = $db->insert_id;

            $db->close();

            header("Location: .?tbid=$insertId");
            die();

        }
        else {
            $msg = 'Error: ' . $db->error;
        }
        
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Criar Novo</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="../js/general.js"></script>
       
    <style type="text/css">
        
        table.tbl td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
        };
        
        function hideHelpBox(){
            element('overlay').style.visibility = 'hidden';
            element('helpBox').style.visibility = 'hidden';
        }
        
        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
        }
        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="../images/banner3admin.jpg"/></a>
        
<?php

renderDropDown($db, $isAdmin);

?>
        <div id="msgBox" style="width: 500px; left: 0; right: 0; margin: auto; display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <br/>
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <span style="color: red; font-style: italic;"><?php echo $msg; ?></span>
            </div>
        </div>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Criar Novo Banco de Horas <img src="../images/question.png" style="cursor: pointer;" onclick="showHelpBox();"/></span>
            <hr/>
            
            <form action="newbank.php" method="post">
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Selecione o ano:</td>
                    <td>
                        <select id="selYear" name="year">
<?php

    $yearToSelect = $year;
    $years = array();
    
    $result = $db->query("SELECT `Year` FROM tb_banks");
    
    while ($value = $result->fetch_row()[0]){
        $years[] = $value;
        if ($yearToSelect == $value) $yearToSelect++;
    }
    
    $result->close();

    for ($i = 2000; $i <= 2099; $i++){
        echo '<option value="' . $i . '"' . ($yearToSelect == $i ? ' selected="selected"' : '') . (in_array($i, $years) ? ' disabled="disabled"' : '') . '>' . $i . '</option>' . PHP_EOL;
    }

?>
                        </select>
                    </td>
                    <td style="width: 100%;">
                        <input type="submit" value="Criar" onclick="return validateInput();" style="width: 80px;"/>
                    </td>
                </tr>
            </table>
            
            <input type="hidden" name="postback" value="1"/>
            </form>
                
        </div>

    </div>
    
    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay" onclick="hideHelpBox();"></div>
    <div class="helpBox" id="helpBox" style="width: 600px; height: 120px;">
        <div class="closeImg" onclick="hideHelpBox();"></div>
        <span style="font-weight: bold;">Ajuda - Criar Novo Banco de Horas</span>
        <hr>
        <div style="padding: 5px;">Para criar um novo Banco de Horas selecione o ano e clique no botão <span style="font-style: italic;">'Criar'</span>.</div>
        <div style="padding: 5px 5px 0 5px;"><span style="color: red;">Atenção:</span> Caso o ano esteja indisponível para ser selecionado, significa que já existe um Banco de Horas relativo ao respectivo ano.</div>
    </div>
    
</body>
</html>
<?php

$db->close();

?>