<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (getGet('l') == '1'){
    
    $loginObj->doLogOut();
    
    $db->close();
    
    header('Location: login.php');
    die();
    
}

// check if user already logged in
if ($loginObj->isLoggedIn(true)){
    $db->close();
    header("Location: .");
    die();
}

// declare variables
$errMsg = null;
$lid = getPost('lid');
$pwd = getPost('pwd');
$rli = getPost('rli');

if (isset($lid) || isset($pwd)){
    
    $lid = trim($lid);
        
    if (strlen($lid) === 0){
        $errMsg = 'Login ID inválido.';
    }
    elseif (strlen($pwd) === 0){
        $errMsg = 'Senha inválida.';
    }
    elseif ($loginObj->doLogin($lid, $pwd, true)){
        
        // save login id cookies
        if ($rli == 1){
            setcookie('remLid', '1', time() + (86400 * 30), '/', COOKIE_DOMAIN);
            setcookie('Lid', $lid, time() + (86400 * 30), '/', COOKIE_DOMAIN);
        }
        else {
            setcookie('remLid', '0', time() + (86400 * 30), '/', COOKIE_DOMAIN);
            setcookie('Lid', '', time() - 3600, '/', COOKIE_DOMAIN);
        }
        
        $db->close();
        
        $redir = getGet('redir');
        
        header("Location: " . (validateRedir($redir) ? $redir : '.'));
        die();
        
    }
    else {
        $errMsg = $loginObj->error;
    }
    
}
else {
    
    // remember login id
    $rli = getCookie('remLid');
    
    if (!isset($rli)){
        // cookie not set, check checkbox
        $rli = 1;
    }
    elseif ($rli == 1){
        // get login id
        $lid = getCookie('Lid');
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Admin - Login</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/sha512.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
        
        window.onload = function(){
            
            if (!navigator.cookieEnabled) element('divCookie').style.display = 'block';
            
            if (!element('txtLoginID').value.trim().length){
                element('txtLoginID').focus();
            }
            else if (!element('txtPwd').value.trim().length){
                element('txtPwd').focus();
            }
            
        };
        
        function doLogin(){
            
            clearErrorMessage();
            
            if (element('txtLoginID').value.trim().length === 0){
                element('spUsr').style.visibility = 'visible';
                element('txtLoginID').focus();
                element('divErrMsg').innerHTML = '* Usuário inválido.';
                return;
            }
            
            if (element('txtPwd').value.length === 0){
                element('spPwd').style.visibility = 'visible';
                element('txtPwd').focus();
                element('divErrMsg').innerHTML = '* Senha inválida.';
                return;
            }
            
            var frm = document.createElement('form');
            
            frm.method = 'post';
            frm.action = 'login.php<?php if (strlen($_SERVER['QUERY_STRING'])) echo '?' . $_SERVER['QUERY_STRING']; ?>';
            
            document.body.appendChild(frm);
            
            var hidLid = document.createElement('input');
            
            hidLid.type = 'hidden';
            hidLid.name = 'lid';
            hidLid.value = element('txtLoginID').value.trim();
            
            var hidPwd = document.createElement('input');
            
            hidPwd.type = 'hidden';
            hidPwd.name = 'pwd';
            hidPwd.value = hex_sha512(element('txtPwd').value);
            
            var hidRemLid = document.createElement('input');
            
            hidRemLid.type = 'hidden';
            hidRemLid.name = 'rli';
            hidRemLid.value = (element('chkRemLoginId').checked ? '1' : '0');
            
            frm.appendChild(hidLid);
            frm.appendChild(hidPwd);
            frm.appendChild(hidRemLid);
            frm.submit();
            
        }
        
        function clearErrorMessage(){
            element('spUsr').style.visibility = 'hidden';
            element('spPwd').style.visibility = 'hidden';
            element('divErrMsg').innerHTML = '';
        }
        
        function LidKeyPrs(e){
            var char = e.which || e.keyCode;
            if (char === 13) element('txtPwd').focus();
        }
        
        function PwdKeyPrs(e){
            var char = e.which || e.keyCode;
            if (char === 13) doLogin();
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" style="height: 139px;"></div>
    
    <div class="main">
        
        <img style="display: block;" src="<?php echo IMAGE_DIR; ?>banner1.jpg"/>
      
        <p>&nbsp;</p>
        
        <div class="panel" style="width: 400px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Login:</span>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Login ID:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtLoginID" style="width: 200px;" value="<?php echo $lid; ?>" onkeydown="clearErrorMessage(); LidKeyPrs(event);"/>
                        <span id="spUsr" style="color: red; visibility: hidden;">*</span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">Senha:</td>
                    <td>
                        <input type="password" id="txtPwd" style="width: 200px;" onkeydown="clearErrorMessage(); PwdKeyPrs(event);"/>
                        <span id="spPwd" style="color: red; visibility: hidden;">*</span>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><input type="checkbox" id="chkRemLoginId"<?php if ($rli == 1) echo 'checked="checked"'; ?>/><label for="chkRemLoginId"> Lembrar o Login ID</label></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><input type="button" value="Entrar" onclick="doLogin();"/></td>
                </tr>
            </table>
            
        </div>
        <br/>
        <div id="divErrMsg" style="color: red; width: 400px; left: 0; right: 0; margin: auto;"><?php echo $errMsg; ?></div>
        
        <noscript>
            <div class="panel" style="width: 400px; left: 0; right: 0; margin: auto;">
                <span style="color: red;">Atenção:</span><br/>
                Foi detectado que o seu navegador está com javascript desabilitado.<br/><br/>
                Para que você possa utilizar esta página, você precisa habilitar javascript.<br/><br/>
                Para instruções sobre como faze-lo, clique no link abaixo.<br/><br/>
                <div style="text-align: center;"><a href="http://enable-javascript.com/pt/" target="_blank">Como habilitar o JavaScript no seu navegador</a></div>
            </div>
        </noscript>
        
        <div id="divCookie" class="panel" style="width: 400px; left: 0; right: 0; margin: auto; display: none;">
            <span style="color: red;">Atenção:</span><br/>
            Foi detectado que o seu navegador está com Cookies desabilitado.<br/><br/>
            Para que você possa utilizar este web site, você precisa habilitar Cookies.<br/><br/>
            Para instruções sobre como faze-lo, clique no <a href="https://www.google.com.br/search?q=habilitar+cookies+no+browser" target="_blank">aqui</a>.<br/>
        </div>
        
    </div>
           
</body>
</html>
<?php

$db->close();

?>