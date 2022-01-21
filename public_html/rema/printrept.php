<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()){
    $db->close();
    die('<span style="font-style: italic; color: red;">Você precisa fazer o login.</style>');
}

if (!$loginObj->isAdmin()){
    $db->close();
    die('<span style="font-style: italic; color: red;">Acesso restrito.</style>');
}

$type = getGet('t');

if (!isNum($type)){
    $db->close();
    die('<span style="font-style: italic; color: red;">Parametros inválidos (0).</style>');
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Imprimir Relatório</title>
    
    <style type="text/css">
        
        div.main {
            width: 800px;
            margin-left: auto; 
            margin-right:auto;
            margin-top: 0px;
            position: relative;
            font-family: calibri;
        }
        
        table.headder td {
            padding: 0;
        }
        
        table.main {
            width: 100%;
            border-collapse: collapse;
        }
        
        table.main td {
            padding: 5px;
            border-top: #404040 solid 1px;
            border-bottom: #404040 solid 1px;
        }
        
        td.numbers {
            width: 10%;
            text-align: right;
            padding-right: 20px !important;
        }
        
    </style>
    
    <style type="text/css" media="print">
        
        .noprint {
            display: none;
        }
               
    </style>
    
    <script type="text/javascript">
    
        
    </script>
    
</head>
<body>

    <div class="main">
        
        <img src="<?php echo IMAGE_DIR; ?>logo.jpg" style="vertical-align: middle; width: 250px; height: 70px;"/>
        
<?php

$cid = getGet('cid');
$units = getGet('sid');
$scid = getGet('scid');

if ($type == 1 && $campName = getCampName($cid)){
    require_once 'required/printrpt/displayGeneralReport.php';
    displayGeneralReport($db, $cid, $campName);
}
elseif ($type == 2 && $campName = getCampName($cid)){
    require_once 'required/printrpt/displayReportByUnit.php';
    displayReportByUnit($db, $cid, $campName);
}
elseif ($type == 3 && $campName = getCampName($cid)){
    require_once 'required/printrpt/displayReportByTeacher.php';
    displayReportByTeacher($db, $cid, $campName);
}
elseif ($type == 4 && $campName = getCampName($cid)){
    require_once 'required/printrpt/displayPpyReport.php';
    displayPpyReport($db, $cid, $campName);
}
elseif ($type == 5 && $campName = getCampName($cid)){
    require_once 'required/printrpt/displayUnenrolledReport.php';
    displayUnenrolledReport($db, $cid, $campName, $units);
}
elseif ($type == 6 && $subCampInfo = getSubCampInfo($scid)){
    require_once 'required/printrpt/displaySubcamaignReport.php';
    displaySubcamaignReport($db, $subCampInfo);
}
elseif ($type == 7 && $campName = getCampName($cid)){
    require_once 'required/printrpt/displayReportByClass.php';
    displayReportByClass($db ,$cid, $campName, $units);
}
elseif ($type == 8 && $campName = getCampName($cid)){
    require_once 'required/printrpt/displayReportByProgram.php';
    displayReportByProgram($db, $cid, $campName, $units);
}
elseif ($type == 9 && $campName = getCampName($cid)){
    require_once 'required/printrpt/displayReasonsReport.php';
    displayReasonsReport($db, $cid, $campName, $units);
}
elseif ($type == 10 && $campName = getCampName($cid)){
    require_once 'required/printrpt/displayReportByLevel.php';
    displayReportByLevel($db, $cid, $campName, $units);
}
elseif ($type == 11 && $campName = getCampName($cid)){
    require_once 'required/printrpt/displayGroupReport.php';
    displayGroupReport($db, $cid, $campName);
}
elseif ($type == 12 && $campName = getCampName($cid)){
    require_once 'required/printrpt/displaySingleGroupReport.php';
    displaySingleGroupReport($db, $cid, $campName, getGet('gid'));
}
else {
    echo '<br/><br/><span style="font-style: italic; color: red;">Parametros inválidos (1).</style>';
}

?>
        
    </div>
    
</body>
</html>
<?php

$db->close();

// ----------------------------------------------------

// returns true if campain id is valid, returns false otherwise
// the second parameter is passes by reference and receives the
// campaign name value.
function getCampName($cid){
    
    global $db;

    if (isNum($cid) && $campName = $db->query("SELECT campaignName($cid)")->fetch_row()[0]){
        return $campName;
    }
    
    return null;
    
}

// ----------------------------------------------------

// returns true if subcampain id is valid, returns false otherwise
// the second parameter is passes by reference and it is the reference
// of an array containing the subcampain info.
function getSubCampInfo($scid){
    
    global $db;
    
    $q = "SELECT subcamps.*, campaignName(campaigns.ID) AS CampName, campaigns.Open AS CampOpen FROM subcamps " .
            "LEFT JOIN campaigns ON subcamps.Parent = campaigns.ID WHERE subcamps.ID = $scid";

    if (isNum($scid) && $subCampInfo = $db->query($q)->fetch_assoc()){
        return $subCampInfo;
    }
    
    return null;
       
}


?>