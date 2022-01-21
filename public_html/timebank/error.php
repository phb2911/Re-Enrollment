<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../../dbconn/dbconn.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

$code = $_SERVER['QUERY_STRING'];

switch ($code) {
    case '400':
        $desc = 'Falha no requerimento.';
        break;
    case '401':
        $desc = 'Operação não autorizada.';
        break;
    case '403':
        $desc = 'Operação proibida.';
        break;
    case '404':
        $desc = 'Página não encontrada.';
        break;
    case '500':
        $desc = 'Erro interno no servidor.';
        break;
    default:
        $code = '';
        $desc = 'Erro desconhecido.';
}

// record error
$inCode = ($code == '' ? '0' : $code);
$redirScript = (isset($_SERVER['REDIRECT_SCRIPT_URI']) && strlen($_SERVER['REDIRECT_SCRIPT_URI']) ? "'" . $db->real_escape_string($_SERVER['REDIRECT_SCRIPT_URI']) . "'" : 'null');

$db->query("INSERT INTO error_log (Code, Redirect_Script) VALUES ($inCode, $redirScript)");

$db->close();

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Rematrícula - Erro</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
      
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        body {
            margin-top: 0;
            background-color: #efefef;
        }

        a {
            color: blue;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        hr {
            height: 1px;
            background: #000000;
            border: 0;
        }
        
        div.main {
            width: 1000px;
            margin-left: auto; 
            margin-right:auto;
            margin-top: 0px;
            position: relative;
            font-family: calibri;
        }

        div.top {
            position: absolute;
            height: 139px;
            width: 100%;
            top: 0;
            left: 0;
            right: 0;
            background-color: #1b9a87;
        }
        
        div.panel {
            background-color: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 3px 3px 3px #808080;
        }
        
    </style>
    
    <script type="text/javascript">
        
    </script>
    
</head>
<body>
    
    <div class="top" style="height: 139px;"></div>
    
    <div class="main">
        
        <img style="display: block;" src="../images/banner3.jpg"/>
      
        <p>&nbsp;</p>
        
        <div class="panel" style="width: 400px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Erro <?php echo $code; ?></span>
            <hr/>
            
            <div style="padding: 10px;"><?php echo $desc; ?></div>
            
        </div>
        
    </div>
    
</body>
</html>
