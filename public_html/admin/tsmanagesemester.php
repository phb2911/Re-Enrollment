<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once '../../dbconn/dbconn.php';

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

if (isset($_POST['r'])){
    // remove semester
    
    $semId = $_POST['r'];
    
    // check if semester id is numeric and fetch semester info
    if (isNum($semId) && $semInfo = $db->query("SELECT tsSemester($semId), tsSemesterHasGroups($semId)")->fetch_row()){
        
        // semInfo[0] = semester name
        // semInfo[1] = semester has groups
        
        // check if semester was found
        if ($semInfo[0] === null){
            $msg = 'O semestre não foi encontrado.';
        }
        // has groups?
        elseif (!!$semInfo[1]){
            $msg = 'O semestre \'' . $semInfo[0] . '\' contem turmas e não pode ser removido.';
        }
        // remove semester
        elseif ($db->query("DELETE FROM ts_semesters WHERE ID = $semId")){
            $msg = '<span style="color: blue;">O semestre \'' . $semInfo[0] . '\' foi removido com sucesso.</span>';
        }
        // error removing
        else {
            $msg = 'Error: ' . $db->error;
        }
        
    }
    else {
        $msg = 'Parametros inválidos[0].';
    }
    
}
elseif (isset($_POST['yr']) && isset($_POST['sem'])){
    // create new semester
    
    $newYear = $_POST['yr'];
    $newSem = $_POST['sem'];
    
    // validate input
    if (isNum($newYear) && isNum($newSem) && $newYear >= 1970 && $newYear <= 2099 && ($newSem == 1 || $newSem == 2)){
        
        // check if semester already exists
        if (!!$db->query("SELECT 1 FROM ts_semesters WHERE Year = $newYear AND Semester = $newSem")->fetch_row()[0]){
            $msg = 'O semestre \'' . $newYear . '.' . $newSem . '\' já existe.';
        }
        // insert new semester
        elseif ($db->query("INSERT INTO ts_semesters (Year, Semester) VALUES ($newYear, $newSem)")){
            $msg = '<span style="color: blue;">O semestre \'' . $newYear . '.' . $newSem . '\' foi criado com sucesso.</span>.';
        }
        // error inserting
        else {
            $msg = 'Error: ' . $db->error;
        }
        
    }
    else {
        $msg = 'Parametros inválidos[1].';
    }
    
}
// edit semester
elseif (isset($_POST['edyr']) && isset($_POST['edsem']) && isset($_POST['semid'])){
    
    $edYear = $_POST['edyr'];
    $edSem = $_POST['edsem'];
    $edSemId = $_POST['semid'];
    
    // validate input
    if (isNum($edYear) && isNum($edSem) && isNum($edSemId) && $edYear >= 1970 && $edYear <= 2099 && ($edSem == 1 || $edSem == 2)){
        
        // check if semester already exists
        if (!!$db->query("SELECT 1 FROM ts_semesters WHERE Year = $edYear AND Semester = $edSem AND ID != $edSemId")->fetch_row()[0]){
            $msg = 'O semestre \'' . $edYear . '.' . $edSem . '\' já existe.';
        }
        // update semester
        elseif ($db->query("UPDATE ts_semesters SET Year = $edYear, Semester = $edSem WHERE ID = $edSemId")){
            if ($db->affected_rows){
                $msg = '<span style="color: blue;">O semestre foi alterado com sucesso.</span>.';
            }
            else {
                // no rows affected or same value inserted.
                $msg = 'O semestre não foi alterado. Possivelmente porque o valor anterior é igual ao novo valor.';
            }
        }
        // error updating
        else {
            $msg = 'Error: ' . $db->error;
        }
        
    }
    else {
        $msg = 'Parametros inválidos[2].';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Admin - TS - Gerenciar Semestres</title>
    
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
        
        Object.prototype.resetSelected = function(){
            
            if (this.nodeName.toLowerCase() != 'select' || this.options.length == 0) return;
            
            for (var i = 0; i < this.options.length; i++){
                if (this.options[i].defaultSelected){
                    this.options[i].selected = true;
                    return;
                }
            }
            
            this.options[0].selected = true;
            
        };
        
        function validateNewInput(selName){
            if (!element(selName).selectedIndex) {
                alert('Por favor selecione o semestre.');
                return false;
            } 
            else return true;
        }
        
        function hideBoxes(){
            element('overlay').style.visibility = 'hidden';
            element('newSem').style.visibility = 'hidden';
            element('editSem').style.visibility = 'hidden';
        }
        
        function removeSevester(id){
            
            if (confirm('Este semestre será removido permanentemente. Deseja continuar?')){
                
                var frm = document.createElement('form');
                frm.method = 'post';
                frm.action = 'tsmanagesemester.php';
                
                document.body.appendChild(frm);
                
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'r';
                input.value = id;
                
                frm.appendChild(input);
                frm.submit();
                
            }
            
        }
        
        function addSemester(){
            
            element('selSem').selectedIndex = 0;
            element('selYr').resetSelected();
            element('overlay').style.visibility = 'visible';
            element('newSem').style.visibility = 'visible';
            
        }
        
        function editSemester(name, id){
            
            var sem = name.split('.');
            var sel = element('selEdYr');
            
            for (var i = 0; i < sel.options.length; i++){
                if (sel.options[i].value == sem[0]){
                    sel.options[i].selected = true;
                    break;
                }
            }
            
            element('selEdSem').selectedIndex = parseInt(sem[1], 10);
            element('hidSemId').value = id;
            element('tdSem').innerHTML = name;
            
            element('overlay').style.visibility = 'visible';
            element('editSem').style.visibility = 'visible';
            
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: 500px; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <div style="color: red; font-style: italic; padding-right: 10px;"><?php echo $msg; ?></div>
            </div>
            <br/>
        </div>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Talk Session - Gerenciar Semestres</span>
            <hr/>
            
<?php

$result = $db->query("SELECT ID, tsSemester(ID) AS Name, tsSemesterHasGroups(ID) AS HasGroups FROM ts_semesters ORDER BY Name DESC");

if ($result->num_rows){
?>
            <table style="width: 100%; border-collapse: collapse; border: #fd7706 solid 1px;">
                <tr>
                    <td style="background-color: #fd7706; color: white;" colspan="2">Semestres</td>
                </tr>
<?php

    while ($row = $result->fetch_assoc()){
        
        $bgcolor = ($bgcolor == '#e1e1e1') ? '#ffffff' : '#e1e1e1';
        $hasGroups = !!$row['HasGroups'];
        
        echo '<tr style="background-color: ' . $bgcolor . ';"><td style="width: 100%;">' . $row['Name'] . 
                '</td><td style="white-space: nowrap;"><img src="' . IMAGE_DIR . 'pencil1.png" style="cursor: pointer; width: 16px; height: 16px;" title="Editar" onclick="editSemester(\'' . 
                $row['Name'] . '\',' . $row['ID'] . ');"/> &nbsp; <img src="' . IMAGE_DIR . ($hasGroups ? 'recycle.png': 'recycle2.png') . 
                '" style="cursor: ' . ($hasGroups ? 'not-allowed' : 'pointer') . '; width: 16px; height: 16px;" ' . 
                ($hasGroups ? '' : 'title="Remover" onclick="removeSevester(' . $row['ID'] . ');"') . '/></td></tr>' . CRLF;
        
    }

?>
            </table>
            <br/>
<?php
}
else {
    echo '<div style="color: red; font-style: italic; padding: 5px;">Não há semestres disponíveis no banco de dados.</div>';
}

$result->close();

?>
            <button onclick="addSemester();"><img src="<?php echo IMAGE_DIR; ?>plus.png"/> Adicionar Semestre</button>
        </div>
        
        <div class="overlay" id="overlay"></div>
        <div class="helpBox" id="newSem" style="width: 420px; height: 90px;">
            <div class="closeImg" onclick="hideBoxes();"></div>
            <span style="font-weight: bold;">TS - Adicionar Novo Semestre</span>
            <hr/>
            <form action="tsmanagesemester.php" method="post">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Selecione o semestre:</td>
                    <td style="width: 100%;">
                        <select id="selYr" name="yr">
<?php

$curYear = date('Y');

for ($i = 1970; $i <= 2099; $i++){
    echo '<option value="' . $i . '"' . ($curYear == $i ? ' selected="selected"' : '') . '>' . $i . '</option>' . CRLF;
}

?>
                        </select> .
                        <select id="selSem" name="sem">
                            <option valu="0"></option>
                            <option valu="1">1</option>
                            <option valu="2">2</option>
                        </select>
                        <input type="submit" value="Criar" onclick="return validateNewInput('selSem');"/>
                    </td>
                </tr>
                <tr>
                </tr>
            </table>
            </form>
        </div>
        <div class="helpBox" id="editSem" style="width: 420px; height: 110px;">
            <div class="closeImg" onclick="hideBoxes();"></div>
            <span style="font-weight: bold;">TS - Editar Semestre</span>
            <hr/>
            <form action="tsmanagesemester.php" method="post">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Valor atual:</td>
                    <td id="tdSem" style="width: 100%; font-weight: bold;"></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Selecione o novo valor:</td>
                    <td style="width: 100%;">
                        <select id="selEdYr" name="edyr">
<?php

for ($i = 1970; $i <= 2099; $i++){
    echo '<option value="' . $i . '">' . $i . '</option>' . CRLF;
}

?>
                        </select> .
                        <select id="selEdSem" name="edsem">
                            <option valu="0"></option>
                            <option valu="1">1</option>
                            <option valu="2">2</option>
                        </select>
                        <input type="submit" value="Modificar" onclick="return validateNewInput('selEdSem');"/>
                    </td>
                </tr>
                <tr>
                </tr>
            </table>
            <input type="hidden" id="hidSemId" name="semid" value=""/>
            </form>
        </div>
        
    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>