<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';
require_once 'dropdown/dropdownMenu.php';
require_once 'required/campaigninfo.php';

// specific to this script
require_once 'required/campbyuser/displayCampaign.php';
require_once 'required/campbyuser/displayStudentTable.php';
require_once 'required/campbyuser/selectCamp.php';
require_once 'editboxes/editboxes.php';


$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()) closeDbAndGoTo($db, LOGIN_PAGE);

$isAdmin = $loginObj->isAdmin();

if (!$isAdmin) closeDbAndGoTo($db, ".");

// assign input to variables
$cid = getPost('cid');
$uid = getGet('uid');

// fetch all campaigns and store into array
$allCamp = allCampaigns($db);

// check if current campaign id submitted by select element
if (isNum($cid) && isset($allCamp[intval($cid, 10)])){
    $cInfo = $allCamp[intval($cid, 10)];
    // save current campaign id into cookie
    setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN);
}
elseif (!$cInfo = getCampaignInfo($db, $allCamp)){ // get current campaign info
    // invalid campaign
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

// validate user id and fetch user name
$isValid = (isset($uid) && isNum($uid) && $usrName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]);

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Campanha por Colaborador</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
    
    <link href="dropdown/dropdownMenu.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="dropdown/dropdownMenu.js"></script>
    <script type="text/javascript" src="editboxes/editboxes.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        table.list {
            width: 100%; 
            box-shadow: 3px 3px 3px #808080; 
            border: #61269e solid 1px;
        }
              
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            DropdownMenu.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selCamp')) element('selCamp').initializeSelect();
            if (element('selUsr')) element('selUsr').styleOption();
            if (element('selEditReason')) element('selEditReason').styleOption();
            if (element('selEditStatus')) styleStatusSelect();
            
        };
        
        function styleStatusSelect(){
            
            var sel = element('selEditStatus');
            var si = sel.selectedIndex;
            
            if (si === 0) sel.style.backgroundColor = 'yellow';
            else if (si === 1) sel.style.backgroundColor = 'orange';
            else if (si === 2) sel.style.backgroundColor = 'red';
            else sel.style.backgroundColor = 'green';
            
            element('selEditReason').disabled = (sel.selectedIndex != 2);
            
        }
        
        function menuClicked(menu, sid){
            
            switch (menu){
                case 1:
                    //window.location = 'modind.php?sid=' + sid;
                    showStatusBox(sid);
                    break;
                case 2:
                    window.location = 'student.php?sid=' + sid;
                    break;
                case 3:
                    window.location = 'editstudent.php?sid=' + sid;
                    break;
                case 4:
                    //window.location = 'flagstd.php?sid=' + sid;
                    showFlagBox(sid);
                    break;
                default:
            }
            
        }
                
<?php if ($isValid){ ?>
        function updateValues(){
            
            var xmlhttp = xmlhttpobj();

            xmlhttp.onreadystatechange = function() {

                // request ready
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

                    var obj = JSON.parse(xmlhttp.responseText);

                    if (!obj.Error){ // skip on error
                        
                        var Active = parseInt(obj.Active, 10);
                        var ActiveEnrolled = parseInt(obj.ActiveEnrolled, 10);
                        var DropOuts = parseInt(obj.DropOut, 10);
                        var DropOutEnrolled = parseInt(obj.DropOutEnrolled, 10);
                        var NotContacted = parseInt(obj.NotContacted, 10);
                        var Contacted = parseInt(obj.Contacted, 10);
                        var NotComingBack = parseInt(obj.NotComingBack, 10);
                        var Enrolled = parseInt(obj.Enrolled, 10);
                        var Total = NotContacted + Contacted + NotComingBack + Enrolled;
                        var Enrollable = (Active + DropOuts);
                        var YearlyContract = parseInt(obj.YearlyContract, 10);
                                                
                        // total students
                        element("tdTotalStds").innerHTML = Total;
                        
                        // enrollable
                        element('tdEnrl').innerHTML = Enrollable;
                        element('tdEnrlPrc').innerHTML = (Total > 0 ? +((Enrollable * 100) / Total).toFixed(2) : '0') + '%';
                        
                        // active
                        element('tdStds').innerHTML = Active;
                        element('tdStdsPrc').innerHTML = (Total > 0 ? +((Active * 100) / Total).toFixed(2) : '0') + '%';
                        
                        // dropouts
                        element('tdDos').innerHTML = DropOuts;
                        element('tdDosPrc').innerHTML = (Total > 0 ? +((DropOuts * 100) / Total).toFixed(2) : '0') + '%';
                        
                        // enrollable semestral
                        element('tdYrCont').innerHTML = (Enrollable - YearlyContract);
                        element('tdYrContPrc').innerHTML = (Total > 0 ? +(((Enrollable - YearlyContract) * 100) / Total).toFixed(2) : '0') + '%';
                        
                        // contacted + enrolled
                        element('tdCont').innerHTML = Contacted;
                        element('tdContPrc').innerHTML = (Total > 0 ? +((Contacted * 100) / Total).toFixed(2) : '0') + '%';
                        
                        // not contacted
                        element('tdNotCont').innerHTML = NotContacted;
                        element('tdNotContPrc').innerHTML = (Total > 0 ? +((NotContacted * 100) / Total).toFixed(2) : '0') + '%';
                        
                        // not coming back
                        element('tdWontBeBack').innerHTML = NotComingBack;
                        element('tdWontBeBackPrc').innerHTML = (Total > 0 ? +((NotComingBack * 100) / Total).toFixed(2) : '0') + '%';
                        
                        // active & enrolled
                        element('tdEnr').innerHTML = ActiveEnrolled;
                        element('tdEnrPrc').innerHTML = (Active > 0 ? +((ActiveEnrolled * 100) / Active).toFixed(2) : '0') + '%';
                        
                        // dropout & enrolled
                        element('tdDoEnr').innerHTML = DropOutEnrolled;
                        element('tdDoEnrPrc').innerHTML = (DropOuts > 0 ? +((DropOutEnrolled * 100) / DropOuts).toFixed(2) : '0') + '%';
                        
                        // enrolled semestrals
                        element('tdTotalSemEnr').innerHTML = (ActiveEnrolled + DropOutEnrolled - YearlyContract);
                        element('tdTotalEnrSemPrc').innerHTML = ((Enrollable - YearlyContract) > 0 ? +(((ActiveEnrolled + DropOutEnrolled - YearlyContract) * 100) / (Enrollable - YearlyContract)).toFixed(2) : '0') + '%';
                        
                        // enrolled
                        element('tdTotalEnr').innerHTML = (ActiveEnrolled + DropOutEnrolled);
                        element('tdTotalEnrPrc').innerHTML = (Enrollable > 0 ? +(((ActiveEnrolled + DropOutEnrolled) * 100) / Enrollable).toFixed(2) : '0') + '%';
                        
                    }

                }

            };

            // THIS WILL NOT STOP PAGE
            xmlhttp.open("POST", "aj_getnumbers.php?" + Math.random(), true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.send(<?php echo '"uid=' . $uid . '&cid=' . $cInfo['ID'] . '"'; ?>);
            
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
                <form id="frmChangeCamp" method="post" action="campbyuser.php<?php if ($isValid) echo '?uid=' . $uid;?>">
                Campanha: &nbsp;
                <select name="cid" style="width: 100px; border-radius: 5px;" onchange="element('imgCampLoader').style.visibility = 'visible'; element('frmChangeCamp').submit();">
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

if ($isValid){
    // valid user, display campaign
    displayCampaign($db, $uid, $usrName, $cInfo);
    displayEditBoxes($db);
}
else {
    // invalid user, select user
    selectCamp($db);
}

?>

        
    </div>
    
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>