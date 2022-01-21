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
    header("Location: " . LOGIN_PAGE);
    die();
}

$errMsg = '';
$errFlag = false;

$usrName = trim(getPost('usrname'));
$email = trim(getPost('email'));
$lid = trim(getPost('lid'));
$pwd = getPost('pwd');
$st = getPost('st');

if (getPost('postback') == '1'){
    
    // validate input
    if (!isset($usrName) || strlen($usrName) === 0 || strlen($usrName) > 55 || strlen($usrName) < 4){
        $errMsg = 'O nome do colaborador não é válido.';
        $errFlag = true;
    }
    
    if (isset($email) && strlen($email) && !isValidEmail($email)){
        if ($errFlag) $errMsg .= '<br/>';
        $errMsg .= 'O formato do e-mail não é válido.';
        $errFlag = true;
    }
    
    if (!isset($lid) || !preg_match('/^([0-9a-zA-Z]|\.|\_)+$/', $lid) || strlen($lid) < 6 || strlen($lid) > 16){
        if ($errFlag) $errMsg .= '<br/>';
        $errMsg .= 'O Login ID não é válido.';
        $errFlag = true;
    }
    
    if (!isset($pwd) || !strlen($pwd) || !preg_match('/^([0-9a-f])+$/', $pwd)) {
        if ($errFlag) $errMsg .= '<br/>';
        $errMsg .= 'A senha não é válida.';
        $errFlag = true;
    }
    
    if (!isset($st) || ($st != '0' && $st != '1' && $st != '2')){
        if ($errFlag) $errMsg .= '<br/>';
        $errMsg .= 'O status não é válido.';
        $errFlag = true;
    }
    
    // check if user name exists in db
    if (!$errFlag && !!$db->query("SELECT COUNT(*) FROM users WHERE LoginID = '" . $db->real_escape_string($lid) . "'")->fetch_row()[0]){
        $errMsg = 'O Login ID \'' . htmlentities($lid, 0, 'ISO-8859-1') . '\' está sendo utilizado por outro colaborador. Por favor escolha um diferente.';
        $errFlag = true;
    }
    
    if (!$errFlag){
        
        // create random salt
        $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
        
        // create hash of the hash
        $pwdHash = hash('sha512', $pwd . $salt);
        
        // save data
        if ($db->query("INSERT INTO users (Name, Email, LoginID, Password, Salt, Status) VALUES ('" . $db->real_escape_string($usrName) . "', " . (!!strlen($email) ? "'" . $db->real_escape_string($email) . "'" : 'null') . ", '" . $db->real_escape_string($lid) . "', '$pwdHash', '$salt', $st)")){
            
            $insert_id = $db->insert_id;
            
            $db->close();
            
            header('Location: user.php?uid=' . $insert_id);
            die();
            
        }
        else {
            $errMsg = $db->error;
            $errFlag = true;
        }
        
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Novo Colaborador</title>
    
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
        
        div.infoBox {
            position: absolute;
            background-color: white;
            border: #fd7706 solid 2px;
            border-radius: 5px;
            padding: 5px;
            font-size: 12px;
            visibility: hidden;
            box-shadow: 5px 5px 5px #cccccc;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
        };
        
        function UserNameKeyPrs(e){
            var char = e.which || e.keyCode;
            if (char === 13) element('txtEmail').focus();
        }
        
        function EmailKeyPrs(e){
            var char = e.which || e.keyCode;
            if (char === 13) element('txtLoginID').focus();
        }
        
        function LidKeyPrs(e){
            var char = e.which || e.keyCode;
            if (char === 13) element('txtPwd').focus();
        }
        
        function PwdKeyPrs(e){
            var char = e.which || e.keyCode;
            if (char === 13) element('txtPwd2').focus();
        }
        
        function validateFields(){
            
            var usrName = element('txtName').value.trim();
            
            if (usrName.length === 0){
                element('divErrMsg').innerHTML = 'Por favor entre o nome do colaborador.';
                element('txtName').focus();
                return false;
            }
            
            if (usrName.length < 4){
                element('divErrMsg').innerHTML = 'O nome do colaborador não pode conter menos de 4 caracteres.';
                element('txtName').focus();
                return false;
            }
            else if (usrName.length > 55){
                element('divErrMsg').innerHTML = 'O nome do colaborador não pode conter mais de 55 caracteres.';
                element('txtName').focus();
                return false;
            }
            
            var email = element('txtEmail').value.trim();
            
            if (email.length > 0 && !validateEmail(email)){
                element('divErrMsg').innerHTML = 'O formato do e-mail não é válido.';
                element('txtEmail').focus();
                return false;
            }
            
            var lid = element('txtLoginID').value.trim();
            
            if (lid.length === 0){
                element('divErrMsg').innerHTML = 'Por favor entre o Login ID.';
                element('txtLoginID').focus();
                return false;
            }
            
            if (!/^([0-9a-zA-z]|\_|\.)+$/.test(lid)){
                element('divErrMsg').innerHTML = 'O Login ID contém caracteres inválidos.';
                element('txtLoginID').focus();
                return false;
            }
            
            if (lid.length < 6 || lid.length > 16){
                element('divErrMsg').innerHTML = 'O Login ID deve ter entre 6 e 16 caracteres.';
                element('txtLoginID').focus();
                return false;
            }
            
            var pwd = element('txtPwd').value;
            var pwd2 = element('txtPwd2').value;
            
            if (pwd.length === 0){
                element('divErrMsg').innerHTML = 'Por favor entre a senha.';
                element('txtPwd').focus();
                return false;
            }
            
            var re = /^([0-9a-zA-Z]|\_|\.|\!|\@|\#|\$|\%|\&|\*|\=|\+|\-)+$/;
            
            if (!re.test(pwd)){
                element('divErrMsg').innerHTML = 'A senha contém caracteres inválidos.';
                element('txtPwd').focus();
                return false;
            }
            
            if (pwd.length < 6 || pwd.length > 12){
                element('divErrMsg').innerHTML = 'A senha deve ter entre 6 e 12 caracteres.';
                element('txtPwd').focus();
                return false;
            }
            
            if (pwd != pwd2){
                element('divErrMsg').innerHTML = 'As senhas devem ser idênticas.';
                element('txtPwd').focus();
                return false;
            }
            
            if (!element('chkTeacher').checked && !element('chkAdmin').checked){
                element('divErrMsg').innerHTML = 'Por favor selecione o status.';
                return false;
            }
            
            return true;
            
        }
        
        function submitForm(){
            
            if (validateFields()){
                
                element('hidUsrName').value = element('txtName').value.trim();
                element('hidEmail').value = element('txtEmail').value.trim();
                element('hidLid').value = element('txtLoginID').value.trim();
                element('hidPwd').value = hex_sha512(element('txtPwd').value);
                
                if (element('chkTeacher').checked && element('chkAdmin').checked) element('hidSt').value = '1';
                else if (element('chkTeacher').checked) element('hidSt').value = '0';
                else if (element('chkAdmin').checked) element('hidSt').value = '2';
                
                element('form1').submit();
                
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
        
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Adicionar Novo Colaborador</span>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Nome:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtName" style="width: 300px;" value="<?php echo $usrName; ?>" onkeydown="element('divErrMsg').innerHTML = ''; UserNameKeyPrs(event);" maxlength="55"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">Email:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtEmail" style="width: 300px;" value="<?php echo $email; ?>" onkeydown="element('divErrMsg').innerHTML = ''; EmailKeyPrs(event);" maxlength="200"/>
                        <span style="font-style: italic;">(opcional)</span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">Login ID:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtLoginID" style="width: 300px;" value="<?php echo $lid; ?>" onkeydown="element('divErrMsg').innerHTML = ''; LidKeyPrs(event);" maxlength="16"/>
                        <img src="<?php echo IMAGE_DIR; ?>question.png" style="cursor: pointer;" onmouseover="element('divUserPanel').style.visibility = 'visible';" onmouseout="element('divUserPanel').style.visibility = 'hidden'"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">Senha:</td>
                    <td>
                        <input type="password" id="txtPwd" style="width: 300px;" onkeydown="element('divErrMsg').innerHTML = ''; PwdKeyPrs(event);" maxlength="12"/>
                        <img src="<?php echo IMAGE_DIR; ?>question.png" style="cursor: pointer;" onmouseover="element('divPwdPanel').style.visibility = 'visible';" onmouseout="element('divPwdPanel').style.visibility = 'hidden'"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap">Redigite a senha:</td>
                    <td>
                        <input type="password" id="txtPwd2" style="width: 300px;" onkeydown="element('divErrMsg').innerHTML = '';" maxlength="12"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; vertical-align: top;">Status:</td>
                    <td>
                        <input type="checkbox" id="chkTeacher" onclick="element('divErrMsg').innerHTML = '';"<?php if ($st === '0' || $st === '1') echo ' checked="checked"'; ?>/><label for="chkTeacher"> Professor</label><br/>
                        <input type="checkbox" id="chkAdmin" onclick="element('divErrMsg').innerHTML = '';"<?php if ($st === '1' || $st === '2') echo ' checked="checked"'; ?>/><label for="chkAdmin"> Administrador</label><br/>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><input type="button" value="Salvar" onclick="submitForm()"/></td>
                </tr>
            </table>
            
            
        </div>
        <br/>
        <div id="divErrMsg" style="color: red; width: 600px; left: 0; right: 0; margin: auto; font-style: italic;"><?php echo $errMsg; ?></div>
        
        <div id="divUserPanel" class="infoBox" style="width: 200px; height: 100px; top: 280px; left: 665px;">
            Login ID:<br/>
            - Mínimo 6 e maximo 16 characters.<br/>
            - Deve conter apenas letras, números, ponto ( . ) e underscore ( _ ).<br/>
            - Não é "case sensitive", ou seja, "ABC" é igual a "abc".
        </div>
        
        <div id="divPwdPanel" class="infoBox" style="width: 200px; height: 112px; top: 315px; left: 665px;">
            Senha:<br/>
            - Mínimo 6 e maximo 12 characters.<br/>
            - Deve conter apenas letras, números e alguns caracteres especiais ( _ . ! @ # $ % & * = + - ).<br/>
            - É "case sensitive", ou seja, "ABC" é diferente a "abc".
        </div>
        
    </div>
    
    <form id="form1" method="post" action="newuser.php">
        <input type="hidden" id="hidUsrName" name="usrname"/>
        <input type="hidden" id="hidEmail" name="email"/>
        <input type="hidden" id="hidLid" name="lid"/>
        <input type="hidden" id="hidPwd" name="pwd"/>
        <input type="hidden" id="hidSt" name="st"/>
        <input type="hidden" name="postback" value="1"/>
    </form>
    
</body>
</html>
<?php

$db->close();

?>