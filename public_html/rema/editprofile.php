<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';
require_once 'required/campaigninfo.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()) closeDbAndGoTo($db, LOGIN_PAGE);

$myUid = $loginObj->userId;
$isAdmin = $loginObj->isAdmin();

if ($isAdmin) closeDbAndGoTo($db, ".");

// fetch all campaigns and store into array
$allCamp = allCampaigns($db);

$cid = getPost('cid');

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

if ($row = $db->query("SELECT Name, LoginID FROM users WHERE ID = $myUid")->fetch_assoc()){
    $userName = $row['Name'];
    $loginId = $row['LoginID'];
}
else {
    $db->close();
    die('Invalid user id.');
}

$tempLoginId = getPost('lid');
$msg = null;

if (isset($tempLoginId)){
    
    $tempLoginId = trim($tempLoginId);
    $oldPwd = getPost('oldpwd');
    $newPwd = getPost('newpwd');
    
    // validate login id
    if (validateLoginId($myUid, $tempLoginId, $msg)){
        
        // check if password not submitted, if so, validate it
        if ((!isset($oldPwd) && !isset($newPwd)) || validatePassword($myUid ,$oldPwd, $newPwd, $msg)){
            
            // build query
            $q = "UPDATE users SET LoginID = '" . $db->real_escape_string($tempLoginId) . "'";
            
            if (isset($newPwd)){
                
                // create random salt
                $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));

                // create hash of the hash
                $pwdHash = hash('sha512', $newPwd . $salt);
                
                // append to query
                $q .= ", Password = '$pwdHash', Salt = '$salt'";
                
            }
            
            // close query
            $q .= " WHERE ID = $myUid";
            
            if ($db->query($q)){
                $msg = '<span style="font-style: italic; color: blue;">Dados atualizados com sucesso.</span>';
                $loginId = $tempLoginId;
            }
            else {
                $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
            }
            
        }
        
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
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/sha512.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27))
                hideBoxes();

        };
        
        function showHelpBox(){
            element('overlay').style.visibility = 'visible';
            element('helpBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('helpBox').style.opacity = '1';
        }
        
        function hideBoxes(){
            element('helpBox').style.opacity = '0';
            element('overlay').style.opacity = '0';
            element('overlay').style.visibility = 'hidden';
            element('helpBox').style.visibility = 'hidden';
        }
        
        function validateLoginId(lid){
            
            if (lid.length < 6 || lid.length > 16) {
                alert('O login ID deve conter entre 6 e 16 caracteres.');
                return false;
            }
            
            if (!/^([0-9a-zA-z]|\_|\.)+$/.test(lid)){
                alert('O Login ID contém caracteres inválidos.');
                return false;
            }
            
            return true;
            
        }
        
        function validatePwd(oldPwd, newPwd, newPwd2){
            
            // do not validate if fields are empty
            if (!oldPwd.length && !newPwd.length && !newPwd2.length) return true;
            
            if (!oldPwd.length){
                alert('Por favor digite a senha atual.');
                element('txtOldPwd').focus();
                return false;
            }
            
            if (newPwd.length < 6 || newPwd.length > 12){
                alert('A nova senha deve conter entre 6 e 12 caracteres.');
                element('txtNewPwd').focus();
                return false;
            }
            
            var re = /^([0-9a-zA-Z]|\_|\.|\!|\@|\#|\$|\%|\&|\*|\=|\+|\-)+$/;
            
            if (!re.test(newPwd)){
                alert('A nova senha contém caracteres inválidos.');
                element('txtNewPwd').focus();
                return false;
            }
            
            if (newPwd != newPwd2){
                alert('A senha re-digitada deve ser identica à nova senha.');
                element('txtNewPwd2').focus();
                return false;
            }
            
            if (oldPwd == newPwd){
                alert('A nova senha deve ser diferente da senha atual.');
                element('txtNewPwd').focus();
                return false;
            }
            
            return true;
            
        }
        
        function submitInput(){
            
            var lid = element('txtLoginID').value.trim();
            var oldPwd = element('txtOldPwd').value;
            var newPwd = element('txtNewPwd').value;
            var newPwd2 = element('txtNewPwd2').value;
            
            
            if (validateLoginId(lid) && validatePwd(oldPwd, newPwd, newPwd2)){
                alert('ops');
                return;
                // create form
                var form = document.createElement('form');
                form.method = 'post';
                form.action = 'editprofile.php';

                document.body.appendChild(form);
                
                // create login id input element
                var lidObj = document.createElement('input');
                lidObj.type = 'hidden';
                lidObj.name = 'lid';
                lidObj.value = lid;
                
                form.appendChild(lidObj);
                
                // create password input elements if
                // password fields are not blank
                if (oldPwd.length && newPwd.length){
                    
                    // old password
                    var pwd = document.createElement('input');
                    pwd.type = 'hidden';
                    pwd.name = 'oldpwd';
                    pwd.value = hex_sha512(oldPwd); // password hash
                    
                    form.appendChild(pwd);
                    
                    // new password
                    var pwd2 = document.createElement('input');
                    pwd2.type = 'hidden';
                    pwd2.name = 'newpwd';
                    pwd2.value = hex_sha512(newPwd); // password hash
                    
                    form.appendChild(pwd2);
                    
                }
                
                // submit form
                form.submit();
                
            }
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="editprofile.php">
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <br/>
            <div style="width: 500px; left: 0; right: 0; margin: auto; background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 15px 10px 15px 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
        </div>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Perfil do Colaborador</span>
            <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Nome:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Login ID:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtLoginID" name="loginid" value="<?php echo htmlentities($loginId, 3, 'ISO-8859-1'); ?>" style="width: 250px;" maxlength="16"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Senha Atual:</td>
                    <td style="width: 100%;">
                        <input type="password" id="txtOldPwd" name="oldpwd" value="" style="width: 250px;" maxlength="12"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Nova Senha:</td>
                    <td style="width: 100%;">
                        <input type="password" id="txtNewPwd" name="newpwd" value="" style="width: 250px;" maxlength="12"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Re-digite a Nova Senha:</td>
                    <td style="width: 100%;">
                        <input type="password" id="txtNewPwd2" name="newpwd2" value="" style="width: 250px;" maxlength="12"/>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        <button type="button" onclick="return submitInput();"><img src="<?php echo IMAGE_DIR; ?>disk2.png" /> Salvar</button>
                    </td>
                </tr>
            </table>
            
        </div>
        <br/>
                        
    </div>
    
    <p>&nbsp;</p>
    
    <div class="overlay" id="overlay" onclick="hideBoxes();"></div>
    <div class="helpBox" id="helpBox" style="width: 620px; height: 370px;">
        <div class="closeImg" onclick="hideBoxes()"></div>
        <span style="font-weight: bold;">Ajuda - Alterar o Login ID e/ou a Senha</span>
        <hr>
        <ul style="line-height: 150%;">
            <li>Para alterar o login ID e/ou a senha, preencha os campos abaixo e peressione o botão <span style="font-style: italic;">'Salvar'</span>.</li>
            <li>
                É necessário que o login ID siga os seguintes requesitos:
                <ul>
                    <li>Tenha entre 6 e 16 caracteres.</li>
                    <li>Contenha letras maúsculas ou minúsculas, números, ponto ( . ) ou underline ( _ ).</li>
                    <li>O login ID é <span style="font-style: italic;">case insensitive</span>, ou seja 'ABC' é o mesmo que 'abc'.</li>
                </ul>
            </li>
            <li>
                É necessário que a senha siga os seguintes requesitos:
                <ul>
                    <li>Tenha entre 6 e 12 caracteres.</li>
                    <li>Contenha letras maúsculas ou minúsculas, números, ou alguns caracteres especiais ( _ . ! @ # $ % & * = + - ).</li>
                    <li>A senha é <span style="font-style: italic;">case sensitive</span>, ou seja 'ABC' é diferente de 'abc'.</li>
                </ul>
            </li>
            <li><span style="color: red;">Atenção:</span> Para alterer apenas o login ID e deixar a senha inalterada, deixe todos os campos relativos à senha em branco.</li>
        </ul>
    </div>
    
</body>
</html>
<?php

$db->close();

// ------------------------------------------

function validateLoginId($myUid, $loginId, &$msg){
    
    global $db;
    
    if (isset($loginId) && preg_match('/^([0-9a-zA-Z]|\.|\_)+$/', $loginId) && strlen($loginId) > 6 && strlen($loginId) < 12){
        
        // check if login id belongs to another user
        if ($db->query("SELECT COUNT(*) FROM users WHERE LoginID = '" . $db->real_escape_string($loginId) . "' AND ID != $myUid")->fetch_row()[0]){
            $msg = '<span style="font-style: italic; color: red;">O Login ID \'' . $loginId . '\' está sendo usado por outro usuário. Por favor entre um diferente.</span>';
            return false;
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">O login ID \'' . $loginId . '\' não é válido.</span>';
        return false;
    }
    
    return true;
    
}

// ------------------------------------------

function validatePassword($myUid, $oldPwd, $newPwd, &$msg){
    
    global $db;
    
    if (!strlen($newPwd) || !preg_match('/^([0-9a-f])+$/', $newPwd)) {
        $msg = '<span style="font-style: italic; color: red;">A nova senha não é válida.</span>';
        return false;
    }
    
    $row = $db->query("SELECT Password, Salt FROM users WHERE ID = $myUid")->fetch_assoc();
    
    if (hash('sha512', $oldPwd . $row['Salt']) != $row['Password']){
        $msg = '<span style="font-style: italic; color: red;">A senha atual está incorreta.</span>';
        return false;
    }
    
    if (hash('sha512', $newPwd . $row['Salt']) == $row['Password']){
        $msg = '<span style="font-style: italic; color: red;">A senha atual e a nova senha são identicas. Por favor escolha uma senha diferente.</span>';
        return false;
    }
    
    return true;
    
}

?>