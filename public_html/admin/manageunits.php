<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn(true)){
    $db->close();
    header("Location: " . LOGIN_PAGE);
    die();
}

$msg = null;
$postback = getPost('postback');

// add new unit
if ($postback == 1){
    
    $newSch = trim(getPost('newsch'));
    
    if (strlen($newSch) == 0 || strlen($newSch) > 55){
        $msg = '<span style="font-style: italic; color: red;">O nome da nova unidade não é válido.</span>';
    }
    elseif (!!$db->query("SELECT 1 FROM schools WHERE Name = '" . $db->real_escape_string($newSch) . "'")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">A unidade \'' . htmlentities($newSch, 0, 'ISO-8859-1') . '\' já existe. Por favor escolha um nome diferente.</span>';
    }
    elseif ($db->query("INSERT INTO schools (Name) VALUES ('" . $db->real_escape_string($newSch) . "')")){
        $msg = '<span style="font-style: italic; color: blue;">A unidade \'' . htmlentities($newSch, 0, 'ISO-8859-1') . '\' foi adicionada com sucesso.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
    }
    
}
// delete school
elseif ($postback == 2){
    
    $delSchId = trim(getPost('remsch'));
    
    if (!isNum($delSchId) || !$unitName = $db->query("SELECT Name FROM schools WHERE ID = $delSchId")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">O ID da unidade não é válido.</span>';
    }
    elseif (!!$db->query("SELECT EXISTS(SELECT 1 FROM classes WHERE School = $delSchId)")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">A unidade \'' . htmlentities($unitName, 0, 'ISO-8859-1') . '\' está associada a uma ou mais turmas e não pode ser removida.</span>';
    }
    elseif ($db->query("DELETE FROM schools WHERE ID = $delSchId")){
        $msg = '<span style="font-style: italic; color: blue;">A unidade \'' . htmlentities($unitName, 0, 'ISO-8859-1') . '\' foi removida com sucesso.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
    }
    
}
// activate/deactivate school
elseif ($postback == 3){
    
    $actSchId = getPost('actsch');
    
    if (!isNum($actSchId)){
        $msg = '<span style="font-style: italic; color: red;">O ID da unidade não é válido.</span>';
    }
    elseif ($db->query("UPDATE schools SET Active = IF(Active = 0, 1 ,0) WHERE ID = $actSchId")){
        if ($db->affected_rows){
            $msg = '<span style="font-style: italic; color: blue;">A unidade foi desativada/reativada com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Unidade não encontrada no banco de dados.</span>';
        }
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
    }
    
}
// edit school name
elseif ($postback == 4){
    
    $newSchName = trim(getPost('newschname'));
    $schId = getPost('schid');
    
    if (strlen($newSchName) == 0 || strlen($newSchName) > 55){
        $msg = '<span style="font-style: italic; color: red;">O nome da nova unidade não é válido.</span>';
    }
    elseif (!isNum($schId) || !$db->query("SELECT 1 FROM schools WHERE ID = $schId")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">Parametros inválidos</span>';
    }
    elseif (!!$db->query("SELECT 1 FROM schools WHERE Name = '" . $db->real_escape_string($newSchName) . "' AND ID != $schId")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">A unidade \'' . htmlentities($newSchName, 0, 'ISO-8859-1') . '\' já existe. Por favor escolha um nome diferente.</span>';
    }
    elseif ($db->query("UPDATE schools SET Name = '" . $db->real_escape_string($newSchName) . "' WHERE ID = $schId")){
        $msg = '<span style="font-style: italic; color: blue;">A nome da unidade foi alterado com sucesso.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Admin - Gerenciamento de Unidades</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
    
    <script type="text/javascript" src="dropdown/dropdownMenu.js"></script>
    <link href="dropdown/dropdownMenu.css" rel="stylesheet" type="text/css" />
    
    <style type="text/css">
        
        td {
            padding: 5px;
        }
             
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            DropdownMenu.initialise();
        };
        
        var newSchFlag = false;
        
        function addUnit(){
            
            // check if new school is being executed
            if (newSchFlag) return;
            
            // check if edit school field is open
            if (curEditId > 0) {
                cancelEdit();
            }
            
            var table;
            
            // check if table exists
            if (element('tblUnits')){
                table = element('tblUnits');
            }
            else {
                // create table
                table = createTable();
            }
            
            // create row
            var tr = document.createElement('tr');
            tr.id = 'trNewSchool';
            // check if table has even or odd number or rows
            tr.style.backgroundColor = (table.getElementsByTagName('tr').length % 2 === 1 ? '#d1d1d1' : 'white');

            // cells
            var td1 = document.createElement('td');
            td1.colSpan = '2';

            var td2 = document.createElement('td');
            td2.style.whiteSpace = 'nowrap';

            // textbox
            var txtBox = document.createElement('input');
            txtBox.type = 'text';
            txtBox.id = 'txtNewSch';
            txtBox.maxLength = '55';
            txtBox.style.width = '95%';

            // save image
            var img1 = document.createElement('img');
            img1.src = '<?php echo IMAGE_DIR; ?>disk2.png';
            img1.style.cursor = 'pointer';
            img1.title = 'Salvar';
            img1.onclick = function(){saveSchool();};

            // cancel image
            var img2 = document.createElement('img');
            img2.src = '<?php echo IMAGE_DIR; ?>cancel2.png';
            img2.style.cursor = 'pointer';
            img2.title = 'Cancelar';
            img2.onclick = function(){cancelNew();};

            td1.appendChild(txtBox);
            td2.appendChild(img1);
            td2.appendChild(document.createTextNode('\u00A0\u00A0\u00A0')); // 3 blank spaces
            td2.appendChild(img2);
            tr.appendChild(td1);
            tr.appendChild(td2);
            table.appendChild(tr);

            newSchFlag = true;
            
        }
        
        function createTable(){
            
            var table = document.createElement('table');
            table.id = 'tblUnits';
            table.style.width = '100%';
            table.style.border = '#fd7706 solid 1px';
            table.style.borderCollapse = 'collapse';
            
            var tr = document.createElement('tr');
            tr.style.backgroundColor = '#fd7706';
            tr.style.color = 'white';
            
            table.appendChild(tr);
            
            var td = document.createElement('td');
            td.style.width = '80%';
            td.innerHTML = 'Unidade';
            
            tr.appendChild(td);
            
            td = document.createElement('td');
            td.style.width = '20%';
            td.style.textAlign = 'center';
            td.innerHTML = 'Status';
            
            tr.appendChild(td);
            
            td = document.createElement('td');
            td.appendChild(document.createTextNode('\u00A0')); // 1 blank spaces
            
            tr.appendChild(td);

            element('divTable').appendChild(table);
            
            return table;
            
        }
        
        function cancelNew(){
            
            if (element('tblUnits').getElementsByTagName('tr').length <= 2 && element('trNewSchool')){
                element('tblUnits').kill(); // from general.js
                newSchFlag = false;
            }
            else if (element('trNewSchool')){
                element('trNewSchool').kill(); // from general.js
                newSchFlag = false;
            }
            
        }
        
        function saveSchool(){
            
            var newSch = element('txtNewSch').value.trim();
                        
            if (newSch.length){
                
                var frm = document.createElement('form');
                frm.method = 'post';
                frm.action = 'manageunits.php';
                
                document.body.appendChild(frm);
                
                var hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'newsch';
                hid.value = newSch;
                
                var pb = document.createElement('input');
                pb.type = 'hidden';
                pb.name = 'postback';
                pb.value = '1';

                frm.appendChild(hid);
                frm.appendChild(pb);
                frm.submit();
                
            }
            else {
                alert('Por favor insira o nome da unidade.');
                element('txtNewSch').focus();
            }
            
        }
        
        function removeSchool(schId){
            
            if (confirm('A unidade será removida permanentemente. Tem certeza?')){
                
                var frm = document.createElement('form');
                frm.method = 'post';
                frm.action = 'manageunits.php';
                
                document.body.appendChild(frm);
                
                var hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'remsch';
                hid.value = schId;
                
                var pb = document.createElement('input');
                pb.type = 'hidden';
                pb.name = 'postback';
                pb.value = '2';

                frm.appendChild(hid);
                frm.appendChild(pb);
                frm.submit();
                
            }
            
        }
        
        function activateSchool(schId){
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = 'manageunits.php';

            document.body.appendChild(frm);

            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'actsch';
            hid.value = schId;
            
            var pb = document.createElement('input');
            pb.type = 'hidden';
            pb.name = 'postback';
            pb.value = '3';

            frm.appendChild(hid);
            frm.appendChild(pb);
            frm.submit();
            
        }
        
        var curEditId = 0;
        
        function editSchool(sid){
            
            // check if new school field is open
            if (newSchFlag){
                cancelNew();
            }
            
            // attribute text to textbox value
            element('txtEdit' + sid).value = element('spUnitName' + sid).innerHTML;
            
            // hide text and show textbox
            element('spUnitName' + sid).style.display = 'none';
            element('txtEdit' + sid).style.display = 'inline';
            
            // hide icons 1 and show icons 2
            element('divIcons1_' + sid).style.display = 'none';
            element('divIcons2_' + sid).style.display = 'block';
            
            curEditId = sid;
            
        }
        
        function cancelEdit(){
            
            // prevent unwanted result
            if (curEditId == 0) return;
            
            // clear textbox
            element('txtEdit' + curEditId).value = '';
            
            // show text and hide textbox
            element('spUnitName' + curEditId).style.display = 'inline';
            element('txtEdit' + curEditId).style.display = 'none';
            
            // hide icons 2 and show icons 1
            element('divIcons1_' + curEditId).style.display = 'block';
            element('divIcons2_' + curEditId).style.display = 'none';
            
            curEditId = 0;
            
        }
        
        function saveEdit(){
            
            var newSchName = element('txtEdit' + curEditId).value.trim();
            
            if (newSchName.length){
                
                var frm = document.createElement('form');
                frm.method = 'post';
                frm.action = 'manageunits.php';
                
                document.body.appendChild(frm);
                
                // new name
                var hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'newschname';
                hid.value = newSchName;

                // school id
                var hid2 = document.createElement('input');
                hid2.type = 'hidden';
                hid2.name = 'schid';
                hid2.value = curEditId;

                var pb = document.createElement('input');
                pb.type = 'hidden';
                pb.name = 'postback';
                pb.value = '4';
                
                frm.appendChild(hid);
                frm.appendChild(hid2);
                frm.appendChild(pb);
                frm.submit();
                
            }
            else {
                alert('Por favor insira o nome da unidade.');
                element('txtEdit' + curEditId).focus();
            }
            
        }
        
        function menuClicked(menu, sid){
            
            // check if new school field is open
            if (newSchFlag){
                cancelNew();
            }
            
            // check if edit school field is open
            if (curEditId > 0) {
                cancelEdit();
            }
            
            switch (menu){
                case 1:
                    editSchool(sid);
                    break;
                case 2:
                    activateSchool(sid);
                    break;
                case 3:
                    removeSchool(sid);
                    break;
                default:
            }
            
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: 600px; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Gerenciamento de Unidades</span>
            <hr/>
            <div id="divTable">
<?php

// retrieve schools
$result = $db->query("SELECT * FROM schools ORDER BY Active DESC, Name");

if ($result->num_rows){
    
?>
            <table id="tblUnits" style="width: 100%; border: #fd7706 solid 1px; border-collapse: collapse;">
                <tr style="background-color: #fd7706; color: white;">
                    <td style="width: 75%;">Unidade</td>
                    <td style="width: 15%; text-align: center;">Status</td>
                    <td style="width: 10%;">&nbsp;</td>
                </tr>
<?php

    $bgcolor = null;

    while ($row = $result->fetch_assoc()){
        
        $bgcolor = ($bgcolor == '#d1d1d1') ? '#ffffff' : '#d1d1d1';
        
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td>
                        <span class="spUnitName" id="spUnitName<?php echo $row['ID']; ?>" style="display: inline;"><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></span>
                        <input type="text" id="txtEdit<?php echo $row['ID']; ?>" style="width: 95%; display: none;" maxlength="55"/>
                    </td>
                    <td style="text-align: center;">
                        <?php echo ($row['Active'] == 1 ? 'Ativa' : '<span style="color: red; font-style: italic;">Inativa</span>') . PHP_EOL; ?>
                    </td>
                    <td style="white-space: nowrap; text-align: right;">
                        <div id="divIcons1_<?php echo $row['ID']; ?>">
                            <ul class="dropdownMenu">
                                <li>
                                    <span><img src="<?php echo IMAGE_DIR; ?>list.png"></span>
                                    <ul style="min-width: 150px; text-align: left;">
                                        <li><a href="#" onclick="menuClicked(1, <?php echo $row['ID']; ?>); return false;"><img src="<?php echo IMAGE_DIR; ?>pencil1.png"/> &nbsp; Editar</a></li>
                                        <li><a href="#" onclick="menuClicked(2, <?php echo $row['ID']; ?>); return false;"><?php echo '<img src="' . IMAGE_DIR . ($row['Active'] ? 'off' : 'on') . '.png"/> &nbsp; ' . ($row['Active'] ? 'Desativar' : 'Ativar'); ?></a></li>
                                        <li><a href="#" onclick="menuClicked(3, <?php echo $row['ID']; ?>); return false;"><img src="<?php echo IMAGE_DIR; ?>recycle2.png"/> &nbsp; Remover</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                        <div id="divIcons2_<?php echo $row['ID']; ?>" style="display: none;">
                            <img src="<?php echo IMAGE_DIR; ?>disk2.png" style="width: 16px; height: 16px; cursor: pointer;" title="Salvar" onclick="saveEdit();"/> &nbsp;
                            <img src="<?php echo IMAGE_DIR; ?>cancel2.png" style="width: 16px; height: 16px; cursor: pointer;" title="Cancelar" onclick="cancelEdit();"/>
                        </div>
                    </td>
                </tr>
<?php } ?>
            </table>
<?php
    
}

$result->close();

?>
            </div>
            <div style="padding-top: 10px;">
                <button type="button" onclick="addUnit();"><img src="<?php echo IMAGE_DIR; ?>plus.png"/> Adicionar Unidade</button>
            </div>
            
        </div>
        
    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>