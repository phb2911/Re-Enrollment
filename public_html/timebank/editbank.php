<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/date_functions.php';
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

$tbid = getGet('tbid');
$msg = null;

if (isNum($tbid) && $row = $db->query("SELECT * FROM tb_banks WHERE ID = $tbid")->fetch_assoc()){
    
    $cYear = $row['Year'];
    $cDate1 = formatDate($row['StartDate']);
    $cDate2 = formatDate($row['EndDate']);
    $isActive = !!$row['Active'];
    
    $isValid = true;
    
    $year = $cYear;
    
}
else {
    $msg = '<span style="color: red; font-style: italic;">Parametros inválidos.</span>';
}

if ($isValid && getPost('postback') == '1'){
    
    $year = getPost('year');
    
    if (!isNum($year) || $year < 2000 || $year > 2099){
        $msg = '<span style="color: red; font-style: italic;">O ano é inválido.</span>';
        $year = intval(date('Y'), 10);
    }
    elseif (!!$db->query("SELECT COUNT(*) FROM tb_banks WHERE `Year` = $year AND ID != $tbid")->fetch_row()[0]){
        $msg = '<span style="color: red; font-style: italic;">Já existe um Banco de Horas relativo ao ano <span style="font-weight: bold;">' . $year . '</span> no banco de dados.</span>';
    }
    else {
        
        // new start and end dates
        $d1 = $year . '-02-01';
        $d2 = ($year + 1) . '-01-31';
        
        if (!!$db->query("SELECT checkNewTBDates($tbid, '$d1', '$d2')")->fetch_row()[0]){
            $msg = '<span style="color: red; font-style: italic;">O Banco de Horas não pode ser modificado pois há datas de turmas, créditos ou débitos fora do novo período especificado (' . formatDate($d1) . ' a ' . formatDate($d2) . ').</span>';
        }
        else {
            
            // update TB
            if ($db->query("UPDATE tb_banks SET `Year` = $year, StartDate = '$d1', EndDate = '$d2' WHERE ID = $tbid")){
                $msg = '<span style="color: blue; font-style: italic;">O Banco de Horas foi modificado com sucesso.</span>';
                // set current data to new data
                $cYear = $year;
                $cDate1 = formatDate($d1);
                $cDate2 = formatDate($d2);
            }
            else {
                $msg = '<span style="color: red; font-style: italic;">Error: ' . $db->error . '</span>';
            }
        
        }
        
    }
    
}
elseif ($isValid && isset($_POST['close'])){
    
    if ($db->query("UPDATE tb_banks SET Active = 0 WHERE ID = $tbid")){
        $msg = '<span style="color: blue; font-style: italic;">O Banco de Horas foi encerrado com sucesso.</span>';
        $isActive = false;
    }
    else {
        $msg = '<span style="color: red; font-style: italic;">Error: ' . $db->error . '</span>';
    }
    
}
elseif ($isValid && isset($_POST['reopen'])){
    
    if ($db->query("UPDATE tb_banks SET Active = 1 WHERE ID = $tbid")){
        $msg = '<span style="color: blue; font-style: italic;">O Banco de Horas foi reaberto com sucesso.</span>';
        $isActive = true;
    }
    else {
        $msg = '<span style="color: red; font-style: italic;">Error: ' . $db->error . '</span>';
    }
    
}
elseif ($isValid && isset($_POST['delete'])){
    
    if ($db->query("DELETE FROM tb_banks WHERE ID = $tbid")){
        $msg = '<span style="color: blue; font-style: italic;">O Banco de Horas foi removido com sucesso.</span>';
        $isValid = false;
    }
    else {
        $msg = '<span style="color: red; font-style: italic;">Error: ' . $db->error . '</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Editar Banco de Horas</title>
    
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
            element('delBox').style.visibility = 'hidden';
        }
        
        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
        }
        
        function closeBank(tbid){
            
            if (confirm('O Banco de Horas será encerrado. Tem certeza que deseja continuar?')){
            
                var form = document.createElement('form');

                form.method = 'post';
                form.action = 'editbank.php?tbid=' + tbid;

                document.body.appendChild(form);

                var hid = document.createElement('input');

                hid.type = 'hidden';
                hid.name = 'close';
                hid.value = tbid;

                form.appendChild(hid);
                form.submit();

            }
            
        }
        
        function reopenBank(tbid){
            
            var form = document.createElement('form');

            form.method = 'post';
            form.action = 'editbank.php?tbid=' + tbid;

            document.body.appendChild(form);

            var hid = document.createElement('input');

            hid.type = 'hidden';
            hid.name = 'reopen';
            hid.value = tbid;

            form.appendChild(hid);
            form.submit();
            
        }
        
        function showDelConf(){
            element('chkDelete').checked = false;
            element('btnDelete').disabled = true;
            element('overlay').style.visibility = 'visible';
            element('delBox').style.visibility = 'visible';
        }
        
        function deleteBank(tbid){
            
            if (!element('chkDelete').checked){
                alert('Por favor cheque o campo \'Remover Banco de Horas\'.');
            }
            else {
                
                var form = document.createElement('form');

                form.method = 'post';
                form.action = 'editbank.php?tbid=' + tbid;

                document.body.appendChild(form);

                var hid = document.createElement('input');

                hid.type = 'hidden';
                hid.name = 'delete';
                hid.value = tbid;

                form.appendChild(hid);
                form.submit();
                
            }
            
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
                <?php echo $msg; ?>
            </div>
        </div>
<?php if ($isValid){ ?>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Editar Banco de Horas <img src="../images/question.png" style="cursor: pointer;" onclick="showHelpBox();"/></span>
            <hr/>
            
            <fieldset style="border-radius: 5px;">
                <legend>Valores Atuais</legend>
                <table class="tbl" style="width: 100%;">
                    <tr>
                        <td style="text-align: right; white-space: nowrap;">Ano:</td>
                        <td style="width: 100%; font-weight: bold;"><?php echo $cYear; ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: right; white-space: nowrap;">Início:</td>
                        <td style="width: 100%; font-weight: bold;"><?php echo $cDate1; ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: right; white-space: nowrap;">Término:</td>
                        <td style="width: 100%; font-weight: bold;"><?php echo $cDate2; ?></td>
                    </tr>
                </table>
            </fieldset>
<?php if ($isActive){ ?>
            <form action="editbank.php?tbid=<?php echo $tbid; ?>" method="post">
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Selecione o novo ano:</td>
                    <td>
                        <select id="selYear" name="year">
<?php

    $yearToSelect = $year;
    $years = array();
    
    $result = $db->query("SELECT `Year` FROM tb_banks WHERE ID != $tbid");
    
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
                        <input type="submit" value="Alterar" onclick="return validateInput();" style="width: 80px;"/>
                        <input type="button" value="Cancelar" onclick="window.location = 'bankslist.php';"/>
                    </td>
                </tr>
            </table>
            
            <input type="hidden" name="postback" value="1"/>
            </form>
<?php 

    }
    else {
        echo '<div style="padding: 5px; color: red; font-style: italic;">* Este Banco de Horas foi encerrado e não pode ser editado.</div>';
    }

?>
        </div>
        <br/>
        <div style="width: 500px; left: 0; right: 0; margin: auto;">
<?php

        if ($isActive){
            echo '<button type="button" onclick="closeBank(' . $tbid . ');"><img src="../images/folder.png"/> Encerrar Banco de Horas</button>' . PHP_EOL .
                    '<button type="button" onclick="showDelConf();"><img src="../images/recycle2.png"/> Remover Banco de Horas</button>';
        }
        else {
            echo '<button type="button" onclick="reopenBank(' . $tbid . ');"><img src="../images/folder2.png"/> Reabrir Banco de Horas</button>';
        }

?>
        </div>
<?php } ?>
    </div>
    
    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay" onclick="hideHelpBox();"></div>
    <div class="helpBox" id="helpBox" style="width: 600px; height: 210px;">
        <div class="closeImg" onclick="hideHelpBox();"></div>
        <span style="font-weight: bold;">Ajuda - Editar Banco de Horas</span>
        <hr>
        <div style="padding: 5px;">Modifique o ano do Banco de Horas existente e clique no botão <span style="font-style: italic;">'Alterar'</span>.</div>
        <div style="padding: 5px 5px 0 5px;">Os seguintes pontos devem ser observados:</div>
        <ul style="line-height: 150%;">
            <li>Caso o banco de horas contenha turmas, créditos ou débitos, este não poderá ser modificado.</li>
            <li>Caso o ano esteja indisponível para ser selecionado, significa que já existe um Banco de Horas relativo ao respectivo ano.</li>
        </ul>
    </div>
    <div class="helpBox" id="delBox" style="width: 500px; height: 190px;">
        <div class="closeImg" onclick="hideHelpBox();"></div>
        <span style="font-weight: bold;">Remover Banco de Horas</span>
        <hr>
        <div style="padding: 5px;"><span style="color: red;">ATENÇÃO:</span> O <span style="font-weight: bold;">Banco de Horas <?php echo $year; ?></span> 
            será permanentemente removido juntamente com todos os dados relativos ao mesmo, tais como, turmas, créditos e débitos.</div>
        <div style="padding: 5px;">Para continuar, cheque o campo abaixo e pressione o botão 'Remover'.</div>
        <div style="padding: 5px;"><input type="checkbox" id="chkDelete" onclick="element('btnDelete').disabled = !this.checked;"/><label for="chkDelete"> Remover Banco de Horas</label></div>
        <div style="text-align: right; padding: 5px;"><input type="button" id="btnDelete" value="Remover" onclick="deleteBank(<?php echo $tbid; ?>);"/></div>
    </div>
    
</body>
</html>
<?php

$db->close();

?>