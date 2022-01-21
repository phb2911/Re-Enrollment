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

// initialize variables
$uid = getGet('uid');
$isValid = false;
$msg = '';

// fetch all active user and current $uid owner even if it's not active
$q = "SELECT ID, Name FROM users WHERE Blocked = 0" . (isNum($uid) ? " OR ID = $uid " : " ") . "ORDER BY Name";

$result = $db->query($q);

// $userArr[ID] = Name
$userArr = array();

while ($row = $result->fetch_assoc()){
    $userArr[$row['ID']] = $row['Name'];
    if ($uid == $row['ID']) $isValid = true; // user id found in db
}

$result->close();

if ($isValid){
    
    // check if delete command submitted
    if (getPost('d') == $uid){
        
        // remove profile
        if ($db->query("DELETE FROM user_profile WHERE User = $uid")){
            
            // redirect to avoid deletion on page reload
            $db->close();
            
            header("Location: userprofile.php?uid=$uid");
            die();
            
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">O correu um erro ao tentar remover a informações do perfil. Por favor tente novamente.</span>';
        }
        
    }
    
    $headder = array(
        'Perfil de Turmas',
        'Acompanhamento de Alunos',
        'Contato com Responsável Pedagógico',
        'Monitoramento do YConnect/House of English',
        'Envio de E-mails',
        'Preenchimento de Cadernetas',
        'Correção de Tarefas',
        'Registro de Ocorrências',
        'Reposições',
        'Treinamento do YConnect/House of English',
        'Entrega de SIRs',
        'Entrega de Plano de Aula (quando solicitado)',
        'Universidade Corporativa',
        'Faltas às Aulas',
        'Faltas às Reuniões Pedagógicas',
        'Outras Observações'
    );
    
    $startYear = null;
    $startSemester = null;
    $education = null;
    
    if ($row = $db->query("SELECT * FROM user_profile WHERE User = $uid")->fetch_assoc()){ // fetch data

        $startYear = $row['StartYear'];
        $startSemester = $row['StartSemester'];
        $education = $row['Education'];
        
        $profileInfo = array(
            $row['ClassProfile'],
            $row['StudentCare'],
            $row['ParentContact'],
            $row['PortalMonitoring'],
            $row['EmailSubmition'],
            $row['ClassRecordBook'],
            $row['Correction'],
            $row['EventRecording'],
            $row['CatchUpClass'],
            $row['PortalTraining'],
            $row['GradeRegistration'],
            $row['ClassPrepForm'],
            $row['UC'],
            $row['ClassAbsence'],
            $row['MeetingAbsence'],
            $row['Other']
        );
                        
    }
        
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Perfil do Colaborador</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        div.closeImg2{
            position: absolute; 
            right: 5px;
            top: 5px;
            width: 16px; 
            height: 16px; 
            background: url(<?php echo IMAGE_DIR; ?>close1.png) no-repeat;
            opacity: 0.5;
            display: none;
        }

    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
             // scroll up arrow
            element('imgUpArrow').style.visibility = (window.pageYOffset == 0 ? 'hidden' : 'visible');
            
            if (element('selUser')) element('selUser').styleOption();
            
        };
        
        window.onscroll = function(){
            element('imgUpArrow').style.visibility = (window.pageYOffset == 0 ? 'hidden' : 'visible');
        };
        
        function redir(sel){
            
            if (sel.selectedValue()){
                element('overlay').style.visibility = 'visible';
                element('loader2').style.visibility = 'visible';
                window.location = 'userprofile.php?uid=' + sel.selectedValue();
            }
            
        }
        
        function editStEd(yr, sm){
            
            if(element('selStartYear').options.length){
                // select current year
                for (var i = 0; i < element('selStartYear').options.length; i++){
                    if (element('selStartYear').options[i].value == yr){
                        element('selStartYear').options[i].selected = true;
                        break;
                    }
                }
            }
            else {
                // create options and select current year
                for (var i = 0; i < 50; i++){
                    
                    var val = i + 2000;
                    
                    element('selStartYear').options[i] = new Option(val, val, i == 0, val == yr);
                    
                }
            }
            
            // semester selected
            element('selStartSem').selectedIndex = sm;
            
            // get original value and add to textbox
            element('txtEdu').value = element('hidEdu').value;
            
            // hide display, show edit
            stEdChageState(true);
            
        }
        
        function salvarStEd(uid){
            
            // validate input
            if (!element('selStartSem').selectedIndex){
                alert('Por favor selecione o semestre.');
                element('selStartSem').focus();
                return;
            }
            
            var yr = element('selStartYear').selectedValue();
            var sem = element('selStartSem').selectedValue();
            var edu;
            
            try {
                edu = encodeURIComponent(btoa(element('txtEdu').value.trim())); // base 64 encoded and URI encoded
            }
            catch(err) {
                alert("A formação do colaborador contem caracteres inválidos.");
                return;
            }
            
            // hide edit buttons and show loader
            element('divIcon2').style.display = 'none';
            element('divIcon3').style.display = 'block';
            
            var xmlhttp = xmlhttpobj();
            
            xmlhttp.onreadystatechange = function() {

                // request ready
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

                    var obj = JSON.parse(xmlhttp.responseText);
                    
                    // hide loader
                    element('divIcon3').style.display = 'none';

                    if (obj.Error){ // error
                        
                        alert(atob(obj.Error));
                        // show edit icons
                        element('divIcon2').style.display = 'block';
                        
                    }
                    else {
                        
                        var education = atob(obj.Education); // base 64 decode
                        
                        element('divStart').innerHTML = obj.Year + '.' + obj.Semester;
                        element('divEdu').innerHTML = education.htmlEntities();
                        element('hidEdu').value = education;
                        element('imgEdit').onclick = function(){
                            // pencil icon, on click show current year
                            editStEd(obj.Year, obj.Semester);
                        };
                        
                        stEdChageState(false);
                        
                    }

                }

            };

            // THIS WILL NOT STOP PAGE
            xmlhttp.open("POST", "aj_saveprof.php?" + Math.random(), true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.send("uid=" + uid + "&yr=" + yr + "&sem=" + sem + "&edu=" + edu);
            
        }
        
        function stEdChageState(flag){
            
            element('divStart').style.display = (flag ? 'none' : 'block');
            element('divStartEdit').style.display = (!flag ? 'none' : 'block');
            element('divEdu').style.display = (flag ? 'none' : 'block');
            element('divEduEdit').style.display = (!flag ? 'none' : 'block');
            element('divIcon1').style.display = (flag ? 'none' : 'block');
            element('divIcon2').style.display = (!flag ? 'none' : 'block');
            
        }
        
        function setElementState(state){
            
            // state must be 0, 1 or 2
            
            element('txtEdit').readOnly = (state == 2 ? false : true);
            element('imgEditSave').src = '<?php echo IMAGE_DIR; ?>' + (state == 2 ? 'disk2.png' : 'disk1.png');
            element('btnEditSave').disabled = (state == 2 ? false : true);
            element('imgEditClear').src = '<?php echo IMAGE_DIR; ?>' + (state == 2 ? 'eraser.png' : 'eraser1.png');
            element('btnEditClear').disabled = (state == 2 ? false : true);
            element('imgLoader').style.visibility = (state == 2 ? 'hidden' : 'visible');
            element('divClose').style.display = (state == 0 ? 'none' : 'block');
            element('divClose2').style.display = (state == 0 ? 'block' : 'none');
            
        }
        
        function saveEdit(uid, index){
            
            var cont;
            
            try {
                cont = encodeURIComponent(btoa(element('txtEdit').value)); // base 64 encoded and URI encoded
            }
            catch(err) {
                alert("O texto contem caracteres inválidos.");
                return;
            }
            
            // disable elements
            setElementState(0);
            
            // save content
            var xmlhttp = xmlhttpobj();
            
            xmlhttp.onreadystatechange = function() {

                // request ready
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

                    var obj = JSON.parse(xmlhttp.responseText);

                    if (obj.Error){ // error
                        
                        alert(atob(obj.Error));
                        
                        // disable elements
                        setElementState(2);
                        
                    }
                    else {
                        // copy content from edit box to container
                        var txt = element('txtEdit').value.htmlEntities();
                        element('preContent' + index).innerHTML = (txt.length ? txt : '&nbsp;');

                        // close edit box
                        hideEditBox();
                    }

                }

            };
            
            // THIS WILL NOT STOP PAGE
            xmlhttp.open("POST", "aj_saveedit.php?" + Math.random(), true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.send("uid=" + uid + "&ind=" + index + "&cont=" + cont);
            
        }
        
        function openEditBox(uid, index){
            
            stEdChageState(false);
            
            // set headder
            element('spEditHeadder').innerHTML = element('spHeadder' + index).innerHTML;
            
            // disable elements
            element('txtEdit').value = '';
            element('btnEditSave').onclick = function(){saveEdit(uid, index);};
            
            charChanged();
            setElementState(1);
            
            element('overlay').style.visibility = 'visible';
            element('editBox').style.visibility = 'visible';
            
            // fetch content
            var xmlhttp = xmlhttpobj();

            xmlhttp.onreadystatechange = function() {

                // request ready
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

                    var obj = JSON.parse(xmlhttp.responseText);

                    if (obj.Error){ // error
                        alert(atob(obj.Error));
                        hideEditBox();
                    }
                    else {
                        // reset elements
                        element('txtEdit').value = atob(obj.Result);
                        setElementState(2);
                        charChanged();
                    }

                }

            };

            // THIS WILL NOT STOP PAGE
            xmlhttp.open("POST", "aj_getprof.php?" + Math.random(), true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.send("uid=" + uid + "&ind=" + index);
            
        }
        
        function removeProfile(uid){
            
            if (confirm('Todas as informações do perfil do colaborador serão removidas.\nDeseja continuar?')){
                
                var frm = document.createElement('form');
                
                frm.method = 'post';
                frm.action = 'userprofile.php?uid=' + uid;
                
                document.body.appendChild(frm);
                
                var inp = document.createElement('input');
                
                inp.type = 'hidden';
                inp.name = 'd';
                inp.value = uid;
                
                frm.appendChild(inp);
                frm.submit();
                
            }
            
        }
        
        function removeStEd(uid){
            
            // hide edit buttons and show loader
            element('divIcon2').style.display = 'none';
            element('divIcon3').style.display = 'block';
            
            var xmlhttp = xmlhttpobj();

            xmlhttp.onreadystatechange = function() {

                // request ready
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

                    // hide loader
                    element('divIcon3').style.display = 'none';

                    var obj = JSON.parse(xmlhttp.responseText);

                    if (obj.Error){ // error
                        alert(atob(obj.Error));
                        element('divIcon2').style.display = 'block';
                    }
                    else {
                        // reset elements
                        element('divIcon1').style.display = 'block';
                        element('divStart').innerHTML = '';
                        element('divEdu').innerHTML = '';
                        element('hidEdu').value = '';
                        element('divEdu').style.display = 'block';
                        element('divEduEdit').style.display = 'none';
                        element('divStart').style.display = 'block';
                        element('divStartEdit').style.display = 'none';
                        element('imgEdit').onclick = function(){
                            // pencil icon, on click show current year
                            editStEd((new Date()).getFullYear(), 0);
                        };
                    }

                }

            };

            // THIS WILL NOT STOP PAGE
            xmlhttp.open("POST", "aj_reminfo.php?" + Math.random(), true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.send("uid=" + uid);
            
        }
        
        function hideEditBox(){
            element('overlay').style.visibility = 'hidden';
            element('editBox').style.visibility = 'hidden';
            element('imgLoader').style.visibility = 'hidden';
            element('divClose').style.display = 'none';
            element('divClose2').style.display = 'none';
        }
        
        function charChanged(){
            element('tdCharCount').innerHTML = 'Caracteres disponíveis: ' + (15000 - element('txtEdit').value.length);
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
        <div id="msgBox" style="display: <?php echo (strlen($msg) ? 'block' : 'none'); ?>;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
<?php

if ($isValid){
    
?>
        <div class="panel">
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="font-weight: bold; width: 100%; padding: 0;">Perfil do Colaborador</td>
                    <td style="padding: 0 5px 0 0;">
                        <img src="<?php echo IMAGE_DIR; ?>recycle2.png" style="cursor: pointer;" title="Remover Perfil" onclick="removeProfile(<?php echo $uid; ?>);"/>
                    </td>
                </tr>
            </table>
            <hr/>
            
            <table style="border-collapse: collapse;">
                <tr>
                    <td style="text-align: right; white-space: nowrap; vertical-align: top;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;">
                        <select style="width: 300px;" onchange="redir(this);" onkeyup="redir(this);">
<?php

    foreach ($userArr as $userId => $userName) {
        echo '<option value="' . $userId . '" style="font-style: normal;"' . ($uid == $userId ? ' selected="selected"' : '') . '>' . htmlentities($userName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }

?>
                        </select>
                    </td>
                    <td style="white-space: nowrap; vertical-align: bottom;" rowspan="3">
                        <div id="divIcon1">
                            <img src="<?php echo IMAGE_DIR; ?>trans.png" style="width: 16px; height: 16px;"/> &nbsp;
                            <img src="<?php echo IMAGE_DIR; ?>trans.png" style="width: 16px; height: 16px;"/> &nbsp;
                            <img id="imgEdit" src="<?php echo IMAGE_DIR; ?>pencil1.png" style="cursor: pointer;" title="Editar data de entrada e formação" onclick="editStEd(<?php echo (isset($startYear) && isset($startSemester) ? $startYear . ',' . $startSemester : date('Y') . ',0'); ?>);"/>
                        </div>
                        <div id="divIcon2" style="display: none;">
                            <img src="<?php echo IMAGE_DIR; ?>disk2.png" style="cursor: pointer;" title="Salvar" onclick="salvarStEd(<?php echo $uid; ?>);"/> &nbsp;
                            <img src="<?php echo IMAGE_DIR; ?>eraser.png" style="cursor: pointer;" title="Apagar data de entrada e formação" onclick="removeStEd(<?php echo $uid; ?>);"/> &nbsp;
                            <img src="<?php echo IMAGE_DIR; ?>cancel2.png" style="cursor: pointer;" title="Cancelar" onclick="stEdChageState(false);"/>
                        </div>
                        <div id="divIcon3" style="display: none;">
                            <img src="<?php echo IMAGE_DIR; ?>trans.png" style="width: 16px; height: 16px;"/> &nbsp;
                            <img src="<?php echo IMAGE_DIR; ?>circle_loader.gif"/>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap; vertical-align: top;">Entrou em:</td>
                    <td style="width: 100%; font-weight: bold;">
                        <div id="divStart"><?php if (isset($startYear) && isset($startSemester)) echo $startYear . '.' . $startSemester; ?></div>
                        <div id="divStartEdit" style="display: none;">
                            <select id="selStartYear"></select> .
                            <select id="selStartSem">
                                <option value="0"></option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap; vertical-align: top;">Formação:</td>
                    <td style="width: 100%;">
                        <div id="divEdu" style="font-weight: bold;"><?php echo htmlentities($education, 0, 'ISO-8859-1'); ?></div>
                        <div id="divEduEdit" style="display: none;">
                            <input type="text" id="txtEdu" style="width: 500px;" maxlength="100" value=""/>
                            <input type="hidden" id="hidEdu" value="<?php echo htmlentities($education, 3, 'ISO-8859-1'); ?>"/>
                        </div>
                    </td>
                </tr>
            </table>
<?php

    for ($i = 0; $i < count($headder); $i++){
        
?>
            <table style="border-collapse: collapse; border: #d1d1d1 solid 1px;">
                <tr style="background-color: #d1d1d1; font-weight: bold;">
                    <td style="width: 100%;"><span id="spHeadder<?php echo $i; ?>"><?php echo $headder[$i]; ?></span> &nbsp; <img src="<?php echo IMAGE_DIR; ?>pencil1.png" style="cursor: pointer;" title="Editar <?php echo $headder[$i]; ?>" onclick="openEditBox(<?php echo $uid . ',' . $i; ?>);"/></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 0 5px 0 5px;"><pre id="preContent<?php echo $i; ?>" style="white-space: pre-wrap;"><?php echo (isset($profileInfo) && isset($profileInfo[$i]) ? htmlentities($profileInfo[$i], 0, 'ISO-8859-1') : '&nbsp;'); ?></pre></td>
                </tr>
            </table>
            <br/>
<?php
        
    }

?> 
        </div>
        <br/>
        
        <div class="helpBox" id="editBox" style="width: 750px; height: 280px;">
            <div class="closeImg" id="divClose" onclick="hideEditBox();"></div>
            <div class="closeImg2" id="divClose2"></div>
            <span style="font-weight: bold;">Editar - <span id="spEditHeadder"></span></span>
            <hr/>
            
            <div style="padding: 5px;">
                <textarea id="txtEdit" maxlength="15000" style="width: 99%; resize: none; height: 200px;" oninput="charChanged();"></textarea>
                
                <table style="border-collapse: collapse; width: 100%;">
                    <tr>
                        <td>
                            <button type="button" id="btnEditSave"><img id="imgEditSave" src="<?php echo IMAGE_DIR; ?>disk1.png"> Salvar</button>
                            <button type="button" id="btnEditClear" onclick="element('txtEdit').value = ''; charChanged();"><img id="imgEditClear" src="<?php echo IMAGE_DIR; ?>eraser1.png"> Apagar</button>
                            <img id="imgLoader" src="<?php echo IMAGE_DIR; ?>circle_loader.gif" style="vertical-align: middle;"/>
                        </td>
                        <td id="tdCharCount" style="color: red; font-style: italic; font-size: 13px; vertical-align: top; white-space: nowrap; text-align: right;"></td>
                    </tr>
                </table>
            </div>
            
        </div>
<?php 
}
else {
    selectUser($userArr);
}

?>
        <div class="overlay" id="overlay"></div>
        <div class="helpBox" id="loader2" style="width: 64px; height: 64px;">
            <img src="<?php echo IMAGE_DIR; ?>circle_loader2.gif"/>
        </div>
        
    </div>
    
    <img id="imgUpArrow" src="<?php echo IMAGE_DIR; ?>arrow_up2.png" style="position: fixed; right: 20px; bottom: 20px; cursor: pointer;" onclick="myTimer = setInterval(scrollUp, 1);" title="Topo da página"/>
</body>
</html>
<?php

$db->close();

//--------------------------------------

function selectUser(&$userArr){

?>
        <div class="panel">
            
            <span style="font-weight: bold;">Perfil do Colaborador</span>
            <hr/>
            
            <div style="padding: 10px;">
                Selecione o Colaborador:
                <select id="selUser" style="width: 300px;" onchange="this.styleOption(); redir(this);" onkeyup="this.styleOption(); redir(this);">
                    <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    foreach ($userArr as $userId => $userName) {
        echo '<option value="' . $userId . '" style="font-style: normal;">' . htmlentities($userName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }

?>
                </select>
            </div>
            
        </div>
<?php
    
}

?>