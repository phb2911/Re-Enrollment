<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if ($loginObj->isLoggedIn()) closeDbAndGoTo($db, LOGIN_PAGE);

$email = getPost('email');
$errMsg = null;

if (isset($email)){
    
    $email = trim($email);
    
    if (!isValidEmail($email)){
        $errMsg = 'O formato do Email não é válido.';
    }
    elseif (!$db->query("SELECT COUNT(*) FROM `users` WHERE `Email` = '" . $db->real_escape_string($email). "'")->fetch_row()[0]){
        $errMsg = 'O Email inserido não foi encontrado no sistema.';
    }
    else {
        $errMsg = 'Ok';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Rematrícula - Recuperar Senha</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
        
        window.onload = function(){
                        
        };
        
        function validateInput(){
            
            if (!validateEmail(element('txtEmail').value.trim())){
                element('divErrMsg').innerHTML = 'O formato do Email não é válido.';
                return false;
            }
            
            return true;
            
        }
                        
    </script>
    
</head>
<body>
    
    <div class="top" style="height: 139px;"></div>
    
    <div class="main">
        
        <img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR; ?>banner.jpg"/>
      
        <p>&nbsp;</p>
        
        <div class="panel" style="width: 400px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Recuperar a Senha:</span>
            <hr/>
            
            <div style="padding: 10px 5px 10px 5px;">Digite no campo abaixo o Email cadastrado no sistema. Caso o Email não esteja cadastrado, contate a coordenação.</div>
            
            <form method="post" action="retpwd.php">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Email:</td>
                    <td style="width: 100%;"  colspan="2">
                        <input type="text" id="txtEmail" name="email" style="width: 300px;" value="<?php echo $email; ?>" onkeyup="element('divErrMsg').innerHTML = '';"/>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td style="width: 100%;"><input type="submit" value="Enviar" onclick="return validateInput();"/></td>
                </tr>
            </table>
            </form>
            
        </div>
        <br/>
        <div id="divErrMsg" style="color: red; width: 400px; left: 0; right: 0; margin: auto; font-style: italic;"><?php echo $errMsg; ?></div>
        
        
    </div>
    
</body>
</html>
<?php

$db->close();

?>