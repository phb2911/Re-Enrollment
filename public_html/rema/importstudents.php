<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

define('UPLOAD_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR);

require_once 'dropdown/dropdown.php';
require_once '../genreq/genreq.php';
require_once 'required/campaigninfo.php';

// specific to this script
require_once 'required/importstudents/loadXmlObj.php';
require_once 'required/importstudents/selectFile.php';
require_once 'required/importstudents/displayInfo.php';
require_once 'required/importstudents/validateStep3Input.php';
require_once 'required/importstudents/saveData.php';
require_once 'required/importstudents/displaySumary.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()) closeDbAndGoTo($db, LOGIN_PAGE);

$isAdmin = $loginObj->isAdmin();

if (!$isAdmin) closeDbAndGoTo($db, ".");

// fetch all campaigns and store into array
$allCamp = allCampaigns($db);

$cid = getPost('cid');
$sids = getPost('sids');

// check if current campaign id submitted by select element
if (isset($cid) && isNum($cid) && isset($allCamp[intval($cid, 10)])){
    $cInfo = $allCamp[intval($cid, 10)];
    // save current campaign id into cookie
    setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN);
}
elseif (!$cInfo = getCampaignInfo($db, $allCamp)){ // get current campaign info
    // current campaing not valid
    closeDbAndGoTo($db, "searchcamp.php");
}

// declare variables
$msg = null;
$uid = null;
$cats = null;
$isDropOuts = null;
$errArr = null;

// step flag
$step = 0;

// check if there's an open campaign
if (!!$cInfo['Open']){
    
    $step = 1;
        
    if (isset($_FILES['fileToUpload'])){ // file uploaded
        
        $uploadedFile = $_FILES['fileToUpload'];
    
        $target_file = createFileName();

        if (strpos(getMimeType($uploadedFile["tmp_name"]), 'text') === false){
            $msg = 'O tipo do arquivo enviado não é válido. ';
        }
        elseif (move_uploaded_file($uploadedFile["tmp_name"], UPLOAD_DIR . $target_file . '.tmp')) {

            if (formatUploadedFile($target_file, $msg)){

                // delete temporary file
                unlink(UPLOAD_DIR . $target_file . '.tmp');
                
                // redirect to step 2
                closeDbAndGoTo($db, "importstudents.php?f=" . $target_file);
                
            }

        }
        else {
            $msg = 'O correu um erro. Por favor tente novamente.';
        }

    }
    elseif (isset($_GET['f'])){ // step 2
        
        $fileName = $_GET['f'];

        removeOldFiles(); // do clean up

        if (!file_exists(UPLOAD_DIR . $fileName)){
            $msg = 'Arquivo não encontrado.';
        }
        elseif (validateXml(UPLOAD_DIR . $fileName)){
            $step = 2;
        }
        else {
            $msg = 'Erro ao carregar arquivo importado.';
        }

    }
    elseif (isset($_POST['step']) && isNum($_POST['step']) && ($_POST['step'] == 3 || $_POST['step'] == 4)){ // step 3 or 4
        
        // set flag
        $step = intval($_POST['step'], 10);

        // get input
        $fileName = getPost('f');
        $uid = getPost('uid');
        $cats = getPost('cats');
        $isDropOuts = (getPost('dos') == 1);
            
        // validate file
        if (isset($fileName)){

            if (!file_exists(UPLOAD_DIR . $fileName)){
                $msg = 'Arquivo não encontrado.';
            }
            elseif (validateXml(UPLOAD_DIR . $fileName)){

                // upload is valid
                $validUpload = true;
                
                // retrieve active teachers from DB
                // $actTeachers[ID] = Name
                $actTeachers = fetchActiveTeachers();
                
                // retrieve all levels from DB
                // $levels[ID] = array(ID, Name, Program, Active)
                $levels = fetchAllLevels();

                // fetch all active programs from DB
                // $programs[ID] = Name
                $programs = fetchActivePrograms();

                // validate unit
                if (!isNum($uid) || !$unitName = $db->query("SELECT Name FROM schools WHERE ID = $uid AND Active = 1")->fetch_row()[0]){
                    $msg = 'A unidade não inválida.';
                    // execution cannot advance to next step
                    // go back one step
                    $step--;
                }
                elseif (!validateCategories($cats)){ // validate columns/categories
                    $msg = 'Uma ou mais categorías são inválidas.';
                    // execution cannot advance to next step
                    // go back one step
                    $step--;
                }
                elseif($step == 4){
                    // validate input
                    if (!validateStep3Input($errArr, $actTeachers, $programs, $levels, getPost('action'), getPost('repbyid'), getPost('lvlaction'), getPost('repprg'), getPost('replvl'))){
                        $msg = 'Um ou mais campos são inválidos. Por favor, revise e tente novamente.';
                        // execution cannot advance to next step
                        // go back one step
                        $step--;
                    }
                    // save data
                    elseif (!saveData($db, $actTeachers, $programs, $levels, $cInfo['ID'], $counters, $saveDataError)){
                        $msg = 'Houveram erros no registro das informações. Detalhes: ' . $saveDataError . '<br/>Por favor, tente novamente.';
                        // execution cannot advance to next step
                        // go back one step
                        $step--;
                    }
                }
                
            }
            else {
                $msg = 'Erro ao carregar arquivo importado. Por favor clique <a href="importstudents.php">aqui</a> para reiniciar o processo.';
            }

        }
        else {
            $msg = 'Nome do arquivo inválido. Por favor clique <a href="importstudents.php">aqui</a> para reiniciar o processo.';
        }
        
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Importar Alunos</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        table.tbl {
            border: #61269e solid 1px;
            box-shadow: 5px 5px 5px #808080;
            left: 0;
            right: 0;
            margin: auto;
        }
                
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            // scroll up arrow
            element('imgUpArrow').style.visibility = (window.pageYOffset == 0 ? 'hidden' : 'visible');
            
            if (element('selUnit')) element('selUnit').styleOption();
            
            // style categories on step 2
            var sels = document.getElementsByClassName('selCat');
            
            for (var i = 0; i < sels.length; i++){
                styleSelCatBox(sels[i]);
            }
            
            // display/hide select options on step 3
            var selAct = document.getElementsByClassName('selAction');
            var selTch;
                        
            for (var i = 0; i < selAct.length; i++){
                
                // use selAction's id to retrieve the index
                selTch = element('selTeacher' + selAct[i].id.substring(9));
            
                selTch.styleOption();
                
                selTch.style.visibility = (selAct[i].selectedValue() == 3 ? 'visible' : 'hidden');
                
            }
            
            var selLvlAct = document.getElementsByClassName('selLvlAction');
            var selLvl, selPrg;
            
            for (var i = 0; i < selLvlAct.length; i++){
                
                // use selLvlAction's id to retrieve the index
                var index = selLvlAct[i].id.substring(12);
                
                selLvl = element('selLevel' + index);
                selLvl.styleOption();
                selLvl.style.display = (selLvlAct[i].selectedValue() == 3 ? 'inline' : 'none');
                
                selPrg = element('selProgr' + index);
                
                // check if select box exists
                if (selPrg){
                    selPrg.styleOption();
                    selPrg.style.display = (selLvlAct[i].selectedValue() == 1 ? 'inline' : 'none');
                }
                
            }
            
        };
        
        window.onscroll = function(){
            element('imgUpArrow').style.visibility = (window.pageYOffset == 0 ? 'hidden' : 'visible');
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27))
                showHelpBox(false);

        };
        
        function styleActSel(sel, index){
            
            var selTch = element('selTeacher' + index);
            
            // show/hide select teacher
            selTch.style.visibility = (sel.selectedValue() == 3 ? 'visible' : 'hidden');
            
            // reset select teacher
            if (sel.selectedValue() != 3) {
                selTch.selectedIndex = 0;
                selTch.style.backgroundColor = '';
                selTch.styleOption();
            }
            
        }
        
        function styleLvlActSel(sel, index){
            
            var selLvl = element('selLevel' + index);
            var selPrg = element('selProgr' + index);
            
            // show/hide select level
            selLvl.style.display = (sel.selectedValue() == 3 ? 'inline' : 'none');
            
            // reset select level/program
            if (sel.selectedValue() != 3) {
                selLvl.selectedIndex = 0;
                selLvl.style.backgroundColor = '';
                selLvl.styleOption();
            }
            
            // check if select element exists
            if (selPrg){
                
                // show/hide select program
                selPrg.style.display = (sel.selectedValue() == 1 ? 'inline' : 'none');
                
                if (sel.selectedValue() != 1) {
                    selPrg.selectedIndex = 0;
                    selPrg.style.backgroundColor = '';
                    selPrg.styleOption();
                }
                
            }
            
        }
        
        function showHelpBox(show){
            
            if (show){
                element('overlay').style.visibility = 'visible';
                element('helpBox').style.visibility = 'visible';
                element('overlay').style.opacity = '0.6';
                element('helpBox').style.opacity = '1';
            }
            else {
                element('helpBox').style.opacity = '0';
                element('overlay').style.opacity = '0';
                element('helpBox').style.visibility = 'hidden';
                element('overlay').style.visibility = 'hidden';
            }
        
        }
        
        function validateFileUpload(){
            
            if (element('fileToUpload').value == ''){
                alert('Por favor selecione um arquivo.');
                return false;
            }
            
            return true;
            
        }
        
        function styleSelCatBox(sel){
            
            if (sel.selectedIndex == 0){
                sel.style.fontStyle = 'italic';
                sel.style.backgroundColor = '';
            }
            else {
                sel.style.fontStyle = 'normal';
                sel.style.backgroundColor = 'cornflowerblue';
            }
            
        }
        
        function selChanged(sel){
            
            if (sel.selectedIndex == 0) return;
            
            var sels = document.getElementsByClassName('selCat');
            
            for (var i = 0; i < sels.length; i++){
                if (sel != sels[i] && sel.selectedIndex == sels[i].selectedIndex){
                    sels[i].selectedIndex = 0;
                    styleSelCatBox(sels[i]);
                }
            }
            
        }
        
<?php
if ($step == 2){ // print function only if step = 2
?>        
        function validateFields(){
            
            if (element('selUnit') && element('selUnit').selectedIndex == 0) {
                alert('Por favor selecione a unidade.');
                return false;
            }
            
            var sels = document.getElementsByClassName('selCat');
            var std = false;
            var tch = false;
            var cls = false;
            var lvl = false;
            
            for (var i = 0; i < sels.length; i++){
                if (sels[i].selectedIndex == 1) std = true;
                else if (sels[i].selectedIndex == 2) tch = true;
                else if (sels[i].selectedIndex == 3) cls = true;
                else if (sels[i].selectedIndex == 4) lvl = true;
            }
            
            if (!std){
                alert('Por favor selecione a coluna relativa aos alunos.');
                return false;
            }
            
            if (!tch){
                alert('Por favor selecione a coluna relativa aos professores.');
                return false;
            }
            
            if (!cls){
                alert('Por favor selecione a coluna relativa às turmas.');
                return false;
            }
            
            if (!lvl){
                alert('Por favor selecione a coluna relativa aos estágios.');
                return false;
            }
            
            return true;
            
        }
<?php 
} 
elseif ($step == 3) { // print function only if step = 3
?>
        function validateFields(){
            
            // validate teacher's action
            var selAct = document.getElementsByClassName('selAction');
            var flag = true;
            var selTch;
            
            for (var i = 0; i < selAct.length; i++){
                
                // use selectAction's id to retrieve the index
                selTch = element('selTeacher' + selAct[i].id.substring(9));
                
                if (selAct[i].selectedValue() == 3 && selTch.selectedIndex == 0){
                    selTch.style.backgroundColor = '#ff8080';
                    flag = false;
                }
                
            }
            
            // validate new level actions
            var selLvlAct = document.getElementsByClassName('selLvlAction');
            var selLvl, selPrg, index;
            
            for (var i = 0; i < selLvlAct.length; i++){
                
                // use selectAction's id to retrieve the index
                index = selLvlAct[i].id.substring(12);
                
                selLvl = element('selLevel' + index);
                selPrg = element('selProgr' + index);
                
                // make sure select element exists
                if (selPrg && selLvlAct[i].selectedValue() == 1 && selPrg.selectedIndex == 0){
                    selPrg.style.backgroundColor = '#ff8080';
                    flag = false;
                }
                
                if (selLvlAct[i].selectedValue() == 3 && selLvl.selectedIndex == 0){
                    selLvl.style.backgroundColor = '#ff8080';
                    flag = false;
                }
                
            }
            
            return flag;
            
        }
        
        function submitForm(btn){
            
            if (validateFields()){
                // display loader
                element('imgRecLoader').style.visibility = 'visible';
                // display black and white image
                element('imgDisk').src = '<?php echo IMAGE_DIR; ?>disk1.png';
                element('imgDisk').style.opacity = '0.6';
                element('imgBack').src = '<?php echo IMAGE_DIR; ?>arrowback2.png';
                element('imgBack').style.opacity = '0.6';
                // deactivate buttons
                btn.disabled = true;
                element('btnBack').disabled = true;
                // submit form
                element('frmStep3').submit();
            }
            else {
                alert('Uma ou mais opções inválidas. Por favor reveja e tente novamente.');
            }
            
        }
        
<?php } ?>
        
    </script>
    
</head>
<body>

    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="importstudents.php">
                Campanha: &nbsp;
                <select name="cid" style="width: 100px; border-radius: 5px;"<?php if ($step == 2 || ($step == 3 && $validUpload)) echo ' disabled="disabled"';?> onchange="element('imgCampLoader').style.visibility = 'visible'; element('frmChangeCamp').submit();">
<?php

// create option
foreach ($allCamp as $cmp){
    echo '<option value="' . $cmp['ID'] . '"' . ($cmp['ID'] == $cInfo['ID'] ? ' selected="selected"' : '') . ($allCamp[intval($cmp['ID'], 10)]['Open'] ? ' style="font-weight: bold;"' : '') . '>' . $cmp['Name'] . '</option>' . PHP_EOL;
}


?>
                </select>
                <img id="imgCampLoader" src="<?php echo IMAGE_DIR; ?>rema_loader.gif" style="vertical-align: middle; visibility: hidden;"/>
                </form>
            </div>
        
<?php

renderDropDown($db, $isAdmin);

?>
        </div>    
<?php

if ($step == 1){
    selectFile($msg, $cInfo['Name']);
}
elseif ($step == 2){
    loadXmlObj($db, $fileName, $cInfo['Name'], $uid, $cats, $isDropOuts, $msg);
}
elseif ($step == 3){
    
    if ($validUpload){
        displayInfo($db, $actTeachers, $levels, $programs, $fileName, $cInfo['Name'], $uid, $unitName, $cats, $isDropOuts, $errArr, $msg);
    }
    else {
        invalidMsg($msg, 'Importar Alunos - Passo 3/4');
    }
    
}
elseif ($step == 4){
    
    if ($validUpload){
        // data successfully saved
        displaySumary($cInfo['Name'], $unitName, $isDropOuts, $counters);
    }
    else {
        invalidMsg($msg, 'Importar Alunos - Passo 4/4');
    }
    
    
}
else {
    invalidMsg("A campanha " . $cInfo['Name'] . " está encerrada e não pode receber novos alunos.<br/>Por favor selecione uma campanha que esteja aberta.", 'Campanha Inválida');
}
    
?>        
    </div>
    
    <p>&nbsp;</p>
    
    <img id="imgUpArrow" src="<?php echo IMAGE_DIR; ?>arrow_up.png" style="position: fixed; right: 20px; bottom: 20px; cursor: pointer;" onclick="myTimer = setInterval(scrollUp, 1);" title="Topo da página"/>
</body>
</html>
<?php

$db->close();

//--------------------------------------------------------------------------

function invalidMsg($msg, $title){
?>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;"><?php echo $title; ?></span>
            <hr/>
            
            <div style="color: red; font-style: italic; padding: 10px;"><?php echo $msg; ?></div>
            
        </div>
<?php
}

//--------------------------------------------------------------------------

function validateCategories($cats){
    
    if (!is_array($cats)) return false;
    
    $acv = array_count_values($cats);
        
    return ($acv[1] == 1 && $acv[2] == 1 && $acv[3] == 1 && $acv[4] == 1);
    
}

//--------------------------------------------------------------------------

function formatUploadedFile($target_file, &$msg){
    
    $source = UPLOAD_DIR . $target_file;
    
    if (!$fileToRead = fopen($source . '.tmp', "r")){
        $msg = 'Não foi possível acessar o arquivo temporário.';
        return false;
    }

    if (!$fileToWrite = fopen($source, "w")){
        $msg = 'Não foi possível criar arquivo.';
        return false;
    }

    $writeFlag = false;

    // Output one line until end-of-file
    while(!feof($fileToRead)) {

        // get line of text and trim it.
        $lineoftext = trim(fgets($fileToRead));

        // if '<table' not found, try to find if,
        // if found, try to find '</table>', exit loop if found
        if (!$writeFlag) {

            $pos = strpos($lineoftext, '<table');

            // if needle found, start writing from the position where needle was found and remove 'x:num' and 'x:autofilter="all"' from string
            if ($pos !== false){
                fwrite($fileToWrite, removeUnwantedStuff(substr($lineoftext, $pos)) . PHP_EOL);
                $writeFlag = true;
            }

        }
        else {

            $pos = strpos($lineoftext, '</table>');

            // if needle not found, write line to file. remove "x:num" and 'x:autofilter="all"'
            // if needle found, write the last line and exit loop
            if ($pos === false){
                fwrite($fileToWrite, removeUnwantedStuff($lineoftext) . PHP_EOL);
            }
            else {
                // write string up to needle and discards the rest
                fwrite($fileToWrite, removeUnwantedStuff(substr($lineoftext, 0, $pos + 8)) . PHP_EOL);
                break;
            }

        }

    }

    fclose($fileToWrite);
    fclose($fileToRead);
    
    return true;
    
}

//--------------------------------------------------------------------------

function removeUnwantedStuff($str){
    
    // this function removes 'x:num' and 'x:autofilter="all"' from string
    // and replaces &nbsp; for space (this would cause an error in the xml validator).
    $search = array('&nbsp;', 'x:autofilter="all"', 'x:num');
    $replace = array(' ', '', '');
    
    return str_replace($search, $replace, $str);
    
}

//--------------------------------------------------------------------------

function createFileName(){
    
    // creates a random file name based on current unix timestamp
    // and checks if file exists in upload folder
    do {
        $res = 'file' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT) . '_' . time();
    }
    while (file_exists(UPLOAD_DIR . $res));
    
    return $res;
    
}

//--------------------------------------------------------------------------

function getMimeType($filePath){
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $res = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    return $res;
    
}

//--------------------------------------------------------------------------

function removeOldFiles(){
    
    if ($handle = opendir(UPLOAD_DIR)) {

        while ($fileName = readdir($handle)) {
            if ($fileName != "." && $fileName != ".." && !is_dir(UPLOAD_DIR . $fileName)) {
                
                // remove extension
                $tempName = str_replace('.tmp', '', $fileName);
                
                // split file and get time of creation
                $fileParts = explode('_', $tempName);
                $time = isset($fileParts[1]) ? $fileParts[1] : null;
                
                // check if $time is numeric and if 3 hours have passed (3h = 10800sec)
                if (isNum($time) && (intval($time, 10) + 10800) < time()){
                    // delete old file
                    unlink(UPLOAD_DIR . $fileName);
                }
                
            }
        }

        closedir($handle);
        
    }
    
}

//--------------------------------------------------------------------------

function validateXml($path){
    
    libxml_use_internal_errors(true);

    $doc = new DOMDocument('1.0', 'utf-8');
    $xml = file_get_contents($path);
    $doc->loadXML($xml);

    return !count(libxml_get_errors());
    
}

//--------------------------------------------------------------------------

function generateLoginID($userName){
    
    $res = replaceSpecialChars(substr(strtolower(preg_replace("/\s+/", "", $userName)), 0, 5));
    
    while (strlen($res) < 8){
        $res .= mt_rand(0, 9);
    }
    
    return $res;
    
}

//--------------------------------------------------------------------------

function fetchActiveTeachers(){
    
    global $db;
    
    // $actTeachers[ID] = Name
    $actTeachers = array();

    // retrieve active teachers from DB
    $result = $db->query("SELECT ID, Name FROM users WHERE Status < 2 AND Blocked = 0 ORDER BY Name");

    while ($row = $result->fetch_assoc()){
        $actTeachers[$row['ID']] = $row['Name'];
    }

    $result->close();
    
    return $actTeachers;
    
}

//--------------------------------------------------------------------------

function fetchAllLevels(){
    
    global $db;
    
    // $levels[ID] = array(ID, Name, Program, Active)
    $levels = array();
    
    // retrieve all levels from DB
    $result = $db->query("SELECT * FROM levels ORDER BY Name");

    while ($row = $result->fetch_assoc()){
        $levels[$row['ID']] = $row;
    }

    $result->close();
    
    return $levels;
    
}

//--------------------------------------------------------------------------

function fetchActivePrograms(){
    
    global $db;
    
    // $programs[ID] = Name
    $programs = array();
    
    // fetch all active programs from DB
    $result = $db->query("SELECT ID, Name FROM programs WHERE Active = 1 ORDER BY Name");

    while ($row = $result->fetch_assoc()){
        $programs[$row['ID']] = $row['Name'];
    }

    $result->close();
    
    return $programs;
    
}

?>