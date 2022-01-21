<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

define('UPLOAD_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR);

require_once 'dropdown/dropdown.php';
require_once '../genreq/date_functions.php';
require_once '../genreq/genreq.php';
require_once 'require/createCheckFlagArray.php';
require_once 'require/validateData.php';

// specific to this script
require_once 'require/importclasses/genfunc.php';
require_once 'require/importclasses/selectFile.php';
require_once 'require/importclasses/loadDOMDoc.php';
require_once 'require/importclasses/displayInfo.php';
require_once 'require/importclasses/processUpload.php';
require_once 'require/importclasses/saveClass.php';

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

$step = (isset($_GET['step']) && isNum($_GET['step']) && $_GET['step'] > 1 && $_GET['step'] <= 3) ? intval($_GET['step'], 10) : 1;
$msg = null;
$cats = array();

removeOldFiles(); // clean up

if ($step == 2){
    
    $tbId = getPost('tb');
    $step = 1;   // step goes back to 2 on success
    
    // validate input
    if (!isNum($tbId) || !$tbYear = $db->query("SELECT `Year` FROM `tb_banks` WHERE `Active` = 1 AND `ID` = $tbId")->fetch_row()[0]){
        $msg = 'O Banco de Horas selecionado n&atilde;o &eacute; v&aacute;lido. Por favor tente novamente.';
    }
    elseif (!isset($_FILES['fileToUpload'])){
        $msg = 'O arquivo n&atilde;o foi enviado corretamente. Por favor tente novamente.';
    }
    else {
        
        $uploadedFile = $_FILES['fileToUpload'];
        
        $target_file = createFileName();  // file name
        $tempUpload = UPLOAD_DIR . $target_file . '.tmp'; // temporary uploaded file
        
        // validate file type
        if (strpos(getMimeType($uploadedFile["tmp_name"]), 'text') === false){
            $msg = 'O tipo do arquivo enviado n&atilde;o &eacute; v&aacute;lido.';
        }
        elseif (move_uploaded_file($uploadedFile["tmp_name"], $tempUpload)) {
            
            if (processUpload($target_file, $msg)) $step = 2; // upload successful
        
            // delete temporary upload
            unlink($tempUpload);
            
        }
        else {
            $msg = 'O correu um erro. Por favor tente novamente.';
        }
        
    }
    
}
elseif ($step == 3){
    
    $tempCats = getGet('cats');
    $tbId = getGet('tb');
    $target_file = getGet('f');
    $line = isset($_GET['line']) && isNum($_GET['line']) ? intval($_GET['line'], 10) : 0;
    
    session_start();
    
    if (getSession($target_file) === null){
        $_SESSION[$target_file] = array('Saved' => 0, 'Skipped' => 0);
    }
    
    if (!file_exists(UPLOAD_DIR . $target_file) || is_dir(UPLOAD_DIR . $target_file)){
        $msg = 'Erro inesperado. Por favor tente novamente.';
        $step = 1;
    }
    elseif (!isNum($tbId) || !$tbInfo = $db->query("SELECT `Year`, `StartDate`, `EndDate` FROM `tb_banks` WHERE `Active` = 1 AND `ID` = $tbId")->fetch_assoc()){
        $msg = 'O Banco de Horas selecionado n&atilde;o &eacute; v&aacute;lido. Por favor tente novamente.';
        $step = 1;
    }
    elseif (!validateCategories($tempCats)){
        $msg = 'Por favor selecione a turma e o professor corretamente.';
        $tbYear = $tbInfo['Year'];
        $step = 2;
    }
    else {
        
        $cats = $tempCats;
        $saveErr = false;
        $tbYear = $tbInfo['Year'];
        $tbStartDate = formatDate($tbInfo['StartDate']); // convert dates from yyyy-mm-dd to dd/mm/yyyy
        $tbEndDate = formatDate($tbInfo['EndDate']);
        
        // check if it is post back
        if (getPost('PostBack') == 1){
            
            if (getPost('skip') == 1) {
                // skipt currect line
                $_SESSION[$target_file]['Skipped']++;
                
                header("Location: " . buildRedirPage($target_file, $tbId, ++$line, $cats)); // $line incremented
                die();
                
            }
            elseif (getPost('save') == 1){
                
                // save info
                if (saveClass($db, $tbId, $tbStartDate, $tbEndDate, $msg)){
                    
                    // redirect to next line to prevent
                    // duplicate class on page reload
                    $_SESSION[$target_file]['Saved']++;
                    
                    header("Location: " . buildRedirPage($target_file, $tbId, ++$line, $cats)); // $line incremented
                    die();
                    
                    
                }
                else {
                    $saveErr = true; // saving fail
                }
                
            }
            
        }
                        
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Adicionar Turmas</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="../js/general.js"></script>
    <script type="text/javascript" src="../js/dateFunctions.js"></script>
    <link href="../calendar/calendar.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="../calendar/calendar.js"></script>
    <script type="text/javascript" src="js/inscls_validation.js"></script>
       
    <style type="text/css">
        
        .tbl td {
            padding: 5px;
        }
        
        table.tbl {
            border: #61269e solid 1px;
            box-shadow: 5px 5px 5px #808080;
            left: 0;
            right: 0;
            margin: auto;
        }
        
        table.tbl2 td {
            padding: 0 5px 0 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            if (element('selBH')) initializeSelect(element('selBH'));
            
            var selCats = document.getElementsByClassName('selCat');
            
            if (selCats.length > 0){
                
                for (var i = 0; i < selCats.length; i++){
                    styleSelCatBox(selCats[i]);
                }
                
            }
        
            if (element('selOpt')){
                element('selOpt').styleOption();
                element('selTeacher').styleOption();
                element('selMod').initializeSelect();
            }
            
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27)){
                if (document.getElementById('calendar')) hideCalendar();
                hideHelpBox();
            }

        };
        
        window.onclick = function(e) {
            if(document.getElementById('calendar') && !document.getElementById('calendar').contains(e.target) && e.target.id.substring(0,7) != 'txtDate') {
                hideCalendar();
            }
        };
        
        var txtBoxIdFlag;

        function showCal(txtBox){
            
            if (txtBox.id != txtBoxIdFlag && CalendarIsOpen()){
                hideCalendar();
            }
            
            txtBoxIdFlag = txtBox.id;

            var abs = getAbsPosition(txtBox);
            var dateStr = txtBox.value.trim();
            var year;
            var month;
            
            if (isValidDate(dateStr)){
                var parts = dateStr.split('/');
                month = parseInt(parts[1], 10);
                year = parseInt(parts[2], 10);
            }
            
            showCalendar(txtBox, abs[0] + 24, abs[1], year, month);
            
        }
        
        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
        }
        
        function showHelpBox1(){
            element('overlay').style.visibility = 'visible';
            element('helpBox1').style.visibility = 'visible';
        }
        
        function hideHelpBox(){
            element('overlay').style.visibility = 'hidden';
            element('helpBox').style.visibility = 'hidden';
            if (element('helpBox1')) element('helpBox1').style.visibility = 'hidden';
        }
        
<?php if ($step == 1){ ?>
        function validateFileUpload(){
            
            if (!element('selBH').selectedIndex){
                alert('Por favor selecione o banco de horas.');
                return false;
            }
            
            if (element('fileToUpload').value == ''){
                alert('Por favor selecione um arquivo.');
                return false;
            }
            
            return true;
            
        }
<?php } elseif ($step == 2){ ?>
        function validateFields(){
            
            var selCats = document.getElementsByClassName('selCat');
            var cls = 0;
            var tch = 0;
                        
            for (var i = 0; i < selCats.length; i++){
                if (selCats[i].selectedIndex == 1) cls++;
                else if (selCats[i].selectedIndex == 2) tch++;
            }
            
            if (!cls){
                alert('Por favor selecione a coluna relativa \340s turmas.');
                return false;
            }
            
            if (cls > 1){
                alert('Apenas uma coluna relativa \340s turmas deve ser selecionada.');
                return false;
            }
            
            if (!tch){
                alert('Por favor selecione a coluna relativa aos professores.');
                return false;
            }
            
            if (tch > 1){
                alert('Apenas uma coluna relativa aos professores deve ser selecionada.');
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
            
            styleSelCatBox(sel);
            
            if (sel.selectedIndex > 0){
            
                var sels = document.getElementsByClassName('selCat');

                for (var i = 0; i < sels.length; i++){
                    if (sel != sels[i] && sel.selectedIndex == sels[i].selectedIndex){
                        sels[i].selectedIndex = 0;
                        styleSelCatBox(sels[i]);
                    }
                }
            
            }
            
        }
<?php } elseif ($step == 3){ ?>
        function showDiv(val){
            
            element('divSelTch').style.display = (val == 1 ? 'block' : 'none');
            element('divNewTch').style.display = (val == 2 ? 'block' : 'none');
            element('divBlank').style.display = (val != 1 && val != 2 ? 'block' : 'none');
            
        }
        
        function validateInput(){
            
            // validate teacher
            var opt = element('selOpt').selectedValue();
            
            if (opt == 0){
                alert('Por favor selecione a optção para professor da turma.');
                element('selOpt').focus();
                return false;
            }
            
            if (opt == 1 && !element('selTeacher').selectedIndex){
                alert('Por favor selecione o professor da turma.');
                element('selTeacher').focus();
                return false;
            }
            
            if (opt == 2 && element('txtNewTch').value.trim().length < 4){
                alert('O nome do professor deve conter pelo menos 4 caracteres.');
                element('txtNewTch').focus();
                return false;
            }
            
            // function from inscls_validation.js
            return inscls_validate(element('txtClsName').value, element('selSem').selectedIndex, element('txtDate1').value, element('txtDate2').value, element('txtDate3').value, element('txtDate4').value, element('selMod').selectedValue(), document.getElementsByClassName('chkDays'));
            
        }
<?php } ?>
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="../images/banner3<?php echo ($isAdmin ? 'admin' : ''); ?>.jpg"/></a>
        
<?php

renderDropDown($db, $isAdmin);

if ($step == 1){
    selectFile($db, $msg);
}
elseif ($step == 2) {
    loadDOMDoc($target_file, $tbId, $tbYear, $cats, $msg);
}
elseif ($step == 3){
    displayInfo($db, $target_file, $tbId, $tbYear, $cats, $line, $saveErr, $msg);
}

?>
    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>