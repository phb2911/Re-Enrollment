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
else {
    $myUid = $loginObj->userId;
}

$uid = $_GET['uid'];
$del = isset($_POST['del']) ? $_POST['del'] : null;
$msg = null;

if (isset($uid) && preg_match('/^([0-9])+$/', $uid) && $userInfo = $db->query("SELECT Name, Email, LoginID, Status, Blocked FROM users WHERE ID = $uid")->fetch_assoc()){
    $userName = $userInfo['Name'];
    $email = $userInfo['Email'];
    $loginId = $userInfo['LoginID'];
    $st = $userInfo['Status'];
    $isActive = !$userInfo['Blocked'];
    $isValid = true;
}
else {
    $msg = '<span style="font-style: italic; color: red;">ID inválida.</span>';
}

if ($isValid && isset($_POST['postback']) && $_POST['postback'] == '1'){
    
    $userName = trim($_POST['usrname']);
    $loginId = trim($_POST['lid']);
    $email = trim($_POST['email']);
    $st = $_POST['st'];
    
    // validate input
    if (!isset($userName) || strlen($userName) === 0 || strlen($userName) > 55 || strlen($userName) < 4){
        $msg = 'O nome do colaborador não é válido.';
    }
    
    if (!isset($loginId) || !preg_match('/^([0-9a-zA-Z]|\.|\_)+$/', $loginId) || strlen($loginId) < 6 || strlen($loginId) > 12){
        if (isset($msg)) $msg .= '<br/>';
        $msg .= 'O Login ID não é válido.';
    }
    
    if (isset($email) && strlen($email) && !preg_match('/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/', $email)){
        if (isset($msg)) $msg .= '<br/>';
        $msg .= 'O formato do e-mail não é válido.';
    }
    
    if (!isset($st) || ($st != '0' && $st != '1' && $st != '2')){
        if (isset($msg)) $msg .= '<br/>';
        $msg .= 'O status não é válido.';
    }
    
    // check if there is another user with the same login id
    if (!isset($msg) && !!$db->query("SELECT 1 FROM users WHERE LoginID = '" . $db->real_escape_string($loginId) . "' AND ID != $uid")->fetch_row()[0]){
        $msg = 'O Login ID \'' . htmlentities($loginId, 0, 'ISO-8859-1') . '\' está sendo utilizado por outro colaborador. Por favor escolha um diferente.';
    }
    
    // update record
    if (!isset($msg)){
        
        if ($db->query("UPDATE users SET Name = '" . $db->real_escape_string($userName) . "', LoginID = '" . $db->real_escape_string($loginId) . "', Email = " . (!!strlen($email) ? "'" . $db->real_escape_string($email) . "'" : 'null') . ", Status = $st WHERE ID = $uid")){
            $msg = '<span style="font-style: italic; color: blue;">Dados do colaborador alterados com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $msg . '</span>';
    }
    
}
elseif ($isValid && isset($_POST['pwd'])){
    
    $pwd = $_POST['pwd'];
    
    if (!strlen($pwd) || !preg_match('/^([0-9a-f])+$/', $pwd)) {
        $errMsg .= 'A senha não é válida.<br/>';
    }
    
    // create random salt
    $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));

    // create hash of the hash
    $pwdHash = hash('sha512', $pwd . $salt);
    
    if ($db->query("UPDATE users SET Password = '$pwdHash', Salt = '$salt' WHERE ID = $uid")){
        $msg = '<span style="font-style: italic; color: blue;">Senha alterada com sucesso.</span>';
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
    }
    
}
elseif ($isValid && isset($_POST['block'])){
    
    $block = $_POST['block'];
    
    if ($block == '0' || $block == '1'){
        
        if ($db->query("UPDATE users SET Blocked = $block WHERE ID = $uid")){
            $msg = '<span style="font-style: italic; color: blue;">Operação realizada com sucesso.</span>';
            $isActive = ($block == '0');
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Parametros inválidos.</span>';
    }
    
}
elseif ($isValid && isset($del) && preg_match('/^([0-9])+$/', $del)){
    
    if ($del == $myUid){
        $msg = '<span style="font-style: italic; color: red;">Apenas outro administrador pode remover este colaborador.</span>';
    }
    elseif (deleteUser($del, $msg)){
        $isValid = false;
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Editar Colaborador</title>
    
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
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27)) closeBox();

        };
        
        function showInfoBox(index){
            
            element('overlay').style.visibility = 'visible';
            
            if (index == 1){
                element('divLoginIdInfoBox').style.visibility = 'visible';
            }
            else if (index == 2){
                
                element('chkDel').checked = false;
                element('btnDel').disabled = true;
                
                element('delDia').style.visibility = 'visible';
                
            }
            else {
                element('divPwdInfoBox').style.visibility = 'visible';
            }
            
        }
        
        function closeBox(){
            
            element('overlay').style.visibility = 'hidden';
            element('divLoginIdInfoBox').style.visibility = 'hidden';
            element('divPwdInfoBox').style.visibility = 'hidden';
            element('delDia').style.visibility = 'hidden';
            
        }
        
        function submitForm(index){
            
            if (index === 0){
                
                var usrName = element('txtName').value.trim();
                var lid = element('txtLoginID').value.trim();
                var email = element('txtEmail').value.trim();

                if (usrName.length === 0){
                    alert('Por favor entre o nome do colaborador.');
                    element('txtName').focus();
                }
                else if (usrName.length < 4){
                    alert('O nome do colaborador não pode conter menos de 4 caracteres.');
                    element('txtName').focus();
                }
                else if (usrName.length > 55){
                    alert('O nome do colaborador não pode conter mais de 55 caracteres.');
                    element('txtName').focus();
                }
                else if (lid.length === 0){
                    alert('Por favor entre o Login ID.');
                    element('txtLoginID').focus();
                }
                else if (!/^([0-9a-zA-z]|\_|\.)+$/.test(lid)){
                    alert('O Login ID contém caracteres inválidos.');
                    element('txtLoginID').focus();
                }
                else if (lid.length < 6 || lid.length > 16){
                    alert('O Login ID deve ter entre 6 e 16 caracteres.');
                    element('txtLoginID').focus();
                }
                else if (email.length > 0 && !validateEmail(email)){
                    alert('O formato do e-mail não é válido.');
                    element('txtEmail').focus();
                }
                else if (!element('chkTeacher').checked && !element('chkAdmin').checked){
                    alert('Por favor selecione o status.');
                }
                else {
                    
                    element('hidUsrName').value = usrName;
                    element('hidLid').value = lid;
                    element('hidEmail').value = email;
                    
                    if (element('chkTeacher').checked && element('chkAdmin').checked) element('hidSt').value = '1';
                    else if (element('chkTeacher').checked) element('hidSt').value = '0';
                    else if (element('chkAdmin').checked) element('hidSt').value = '2';

                    element('form1').submit();
                    
                }
                
            }
            else if (index === 1){
                
                var pwd = element('txtPwd').value;
                var pwd2 = element('txtPwd2').value;
                var re = /^([0-9a-zA-Z]|\_|\.|\!|\@|\#|\$|\%|\&|\*|\=|\+|\-)+$/;

                if (pwd.length === 0){
                    alert('Por favor entre a senha.');
                    element('txtPwd').focus();
                }
                else if (!re.test(pwd)){
                    alert('A senha contém caracteres inválidos.');
                    element('txtPwd').focus();
                }
                else if (pwd.length < 6 || pwd.length > 12){
                    alert('A senha deve ter entre 6 e 12 caracteres.');
                    element('txtPwd').focus();
                }
                else if (pwd != pwd2){
                    alert('As senhas devem ser idênticas.');
                    element('txtPwd').focus();
                }
                else {
                    
                    var frm = element('form2');
                    
                    // clear children - gen.gs
                    frm.clearChildren();
                    
                    // create hidden element
                    var hidPwd = document.createElement('input');
                    
                    hidPwd.type = 'hidden';
                    hidPwd.name = 'pwd';
                    hidPwd.value = hex_sha512(element('txtPwd').value);
                    
                    // append child and submit form
                    frm.appendChild(hidPwd);
                    frm.submit();
                    
                }
                
            }
            
        }
        
        function actUser(val){
            
            var frm = element('form2');
                    
            // clear children - gen.gs
            frm.clearChildren();

            // create hidden element
            var hidBlock = document.createElement('input');

            hidBlock.type = 'hidden';
            hidBlock.name = 'block';
            hidBlock.value = val;

            // append child and submit form
            frm.appendChild(hidBlock);
            frm.submit();
            
        }
        
        function deleteUser(uid){
            
            if (element('chkDel').checked){
            
                var frm = element('form2');

                // clear children - gen.gs
                frm.clearChildren();

                // create hidden element
                var hidDel = document.createElement('input');

                hidDel.type = 'hidden';
                hidDel.name = 'del';
                hidDel.value = uid;

                // append child and submit form
                frm.appendChild(hidDel);
                frm.submit();
            
            }
            else {
                alert('Selecione a opção "Remover Colaborador".');
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <br/>
            <div style="width: 600px; left: 0; right: 0; margin: auto; background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
        </div>
<?php
if ($isValid) {
?>
        <br/>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Editar Colaborador:</span>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Nome:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtName" style="width: 300px;" value="<?php echo htmlentities($userName, 3, 'ISO-8859-1'); ?>" maxlength="55"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Login ID:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtLoginID" style="width: 300px;" value="<?php echo htmlentities($loginId, 3, 'ISO-8859-1'); ?>" maxlength="16"/>
                        <img src="<?php echo IMAGE_DIR; ?>question.png" style="cursor: pointer;" onclick="showInfoBox(1);"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">E-mail:</td>
                    <td style="width: 100%;" colspan="2">
                        <input type="text" id="txtEmail" style="width: 300px;" value="<?php echo htmlentities($email, 3, 'ISO-8859-1'); ?>" maxlength="200"/>
                        <span style="font-style: italic;">(opcional)</span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Situação:</td>
                    <td style="width: 100%;">
                       <span style="font-weight: bold;"><?php echo ($isActive ? 'Ativo' : 'Inativo'); ?></span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; vertical-align: top;">Status:</td>
                    <td>
                        <input type="checkbox" id="chkTeacher"<?php if ($myUid == $uid) echo ' disabled="disabled"'; if ($st === '0' || $st === '1') echo ' checked="checked"'; ?>/><label for="chkTeacher"> Professor</label><br/>
                        <input type="checkbox" id="chkAdmin"<?php if ($myUid == $uid) echo ' disabled="disabled"'; if ($st === '1' || $st === '2') echo ' checked="checked"'; ?>/><label for="chkAdmin"> Administrador</label>
                        <?php if ($myUid == $uid) echo '<br/><span style="font-style: italic; color: red; font-size: 12px;">* O seu status só pode ser modificado por outro administrador.</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td style="width: 100%;">
                        <input type="button" value="Salvar" onclick="submitForm(0)"/>
                        <input type="button" value="Reset" onclick="window.location = 'edituser.php?uid=<?php echo $uid; ?>';"/>
                    </td>
                </tr>
            </table>
            
            
        </div>
        
        <br/>
        
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Editar Senha:</span>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Senha:</td>
                    <td style="width: 100%;">
                        <input type="password" id="txtPwd" style="width: 300px;" maxlength="12"/>
                        <img src="<?php echo IMAGE_DIR; ?>question.png" style="cursor: pointer;" onclick="showInfoBox(0);"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap">Redigite a senha:</td>
                    <td>
                        <input type="password" id="txtPwd2" style="width: 300px;" maxlength="12"/>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><input type="button" value="Salvar" onclick="submitForm(1)"/></td>
                </tr>
            </table>
            
        </div>
        
        <br/>
        
        <div style="width: 600px; left: 0; right: 0; margin: auto;">
            <?php echo '<button type="button" onclick="actUser(' . ($isActive ? '1' : '0') . ')"' . ($myUid == $uid ? ' disabled="disabled"' : '') . '><img src="' . IMAGE_DIR . ($myUid == $uid ? 'on_gr.png' : ($isActive ? 'off.png' : 'on.png')) . '"/> ' . ($isActive ? 'Desativar' : 'Reativar') . ' Colaborador</button>'; ?>
            <button type="button" onclick="showInfoBox(2);"<?php if ($myUid == $uid) echo ' disabled="disabled"'; ?>><img src="<?php echo IMAGE_DIR . ($myUid == $uid ? 'recycle.png' : 'recycle2.png'); ?>"/> Remover Colaborador</button>
        </div>
        
        <br/>
        <div style="width: 600px; left: 0; right: 0; margin: auto;">
            <img src="<?php echo IMAGE_DIR; ?>back.png" title="Voltar" style="cursor: pointer;" onclick="window.location = 'user.php?uid=<?php echo $uid; ?>';"/>
        </div>
        
        <form id="form1" method="post" action="edituser.php?uid=<?php echo $uid; ?>">
            <input type="hidden" id="hidUsrName" name="usrname"/>
            <input type="hidden" id="hidLid" name="lid"/>
            <input type="hidden" id="hidEmail" name="email"/>
            <input type="hidden" id="hidSt" name="st"/>
            <input type="hidden" name="postback" value="1">
        </form>

        <form id="form2" method="post" action="edituser.php?uid=<?php echo $uid; ?>">
            <input type="hidden" id="hidPwd" name="pwd"/>
        </form>
<?php 
}
?>
        
    </div>
    
    <p>&nbsp;</p>

    <div id="overlay" class="overlay" onclick="closeBox();"></div>
    
    <div id="divPwdInfoBox" class="helpBox" style="width: 420px; height: 130px;">
        <div class="closeImg" onclick="closeBox();"></div>
        <span style="font-weight: bold;">Senha:</span>
        <ul>
            <li>Mínimo 6 e maximo 12 characters.</li>
            <li>Deve conter apenas letras, números e alguns caracteres especiais ( _ . ! @ # $ % & * = + - ).</li>
            <li>É "case sensitive", ou seja, "ABC" é diferente a "abc".</li>
        </ul>
    </div>
    
    <div id="divLoginIdInfoBox" class="helpBox" style="width: 420px; height: 130px;">
        <div class="closeImg" onclick="closeBox();"></div>
        <span style="font-weight: bold;">Login ID:</span>
        <ul>
            <li>Mínimo 6 e maximo 16 characters.</li>
            <li>Deve conter apenas letras, números, ponto ( . ) e underscore ( _ ).</li>
            <li>Não é "case sensitive", ou seja, "ABC" é igual a "abc".</li>
        </ul>
    </div>
    
    <div class="helpBox" id="delDia" style="width: 500px; height: 240px;">
        <span style="font-weight: bold;">Remover Colaborador</span>
        <hr/>

        <div style="padding: 10px;">
            <span style="color: red;">Atenção:</span> O colaborador <span style="font-weight: bold;"><?php echo htmlentities($userInfo['Name'], 0, 'ISO-8859-1'); ?></span> e todos os dados
            relacionados a este(a), tais como participações em campanhas, turmas, alunos, etc., serão removidos permanentemente.
            <p>Para continuar, marque a opção abaixo e click no botão "Remover":</p>
            <p style="padding-left: 15px;"><input type="checkbox" id="chkDel" onclick="element('btnDel').disabled = !this.checked;"/><label for="chkDel"> Remover colaborador</label></p>
        </div>
        <div style="text-align: right; padding-right: 10px;">
            <input type="button" id="btnDel" value="Remover" onclick="deleteUser(<?php echo $uid; ?>);"/>
            <input type="button" value="Cancelar" onclick="closeBox();"/>
        </div>
    </div>
    
</body>
</html>
<?php

$db->close();

//-------------------------------

function deleteUser($uid, &$msg){
    
    global $db;
    
    // deletes the user and all other records from other tables will cascade
    if ($db->query("DELETE FROM users WHERE ID = $uid")){
        if ($db->affected_rows){
            $msg = '<span style="font-style: italic; color: blue;">Colaborador removido com sucesso.</span>';
            return true;
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Colaborador não encontrado.</span>';
        }
    }
    else {
        $msg = '<span style="font-style: italic; color: blue;">Error:' . $db->error . '</span>';
    }
    
    return false;
    
}

?>