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

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Admin</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
        };
        
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
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 33%; vertical-align: top; font-weight: bold;">
                    <fieldset style="border-radius: 5px;">
                        <legend>Colaboradores</legend>
                        <ul>
                            <li><a href="newuser.php">Adicionar Novo Colaborador</a></li>
                            <li><a href="searchuser.php">Buscar Colaborador</a></li>
                            <li>Eventos
                                <ul>
                                    <li><a href="events.php">Lista de Eventos</a></li>
                                    <li><a href="newevent.php">Adicionar Novo Evento</a></li>
                                </ul>
                            </li>
                            <li><a href="userlist.php">Lista de Colaboradores</a></li>
                            <li><a href="lidlist.php">Lista de Login IDs</a></li>
                            <li><a href="userprofile.php">Perfl do Colaborador</a></li>
                        </ul>
                    </fieldset>
                </td>
                <td style="width: 33%; vertical-align: top; font-weight: bold;">
                    <fieldset style="border-radius: 5px;">
                        <legend>Geral</legend>
                        <ul>
                            <li><a href="backup.php">Backup</a></li>
                            <li><a href="manageunits.php">Gerenciar Unidades</a></li>
                            <li><a href="loginstats.php">Histórico de Acesso</a></li>
                            <li><a href="campclosinglog.php">Histórico de Fechamento de Campnha de Rema</a></li>
                        </ul>
                    </fieldset>
                </td>
                <td style="width: 33%; vertical-align: top; font-weight: bold;">
                    <fieldset style="border-radius: 5px;">
                        <legend>Sites</legend>
                        <ul>
                            <li><a href="../jbadmin">Job Bank</a></li>
                            <li><a href="../rema">Rematrícula</a></li>
                            <li><a href="../timebank">Time Bank</a></li>
                            <!--
                            <li><a href="<?php echo createLink('jbadmin'); ?>">Job Bank</a></li>
                            <li><a href="<?php echo createLink('rema'); ?>">Rematrícula</a></li>
                            <li><a href="<?php echo createLink('timebank'); ?>">Time Bank</a></li>
                            -->
                        </ul>
                    </fieldset>
                </td>
            </tr>
        </table>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>