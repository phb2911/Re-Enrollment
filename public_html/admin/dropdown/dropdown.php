<?php

// dropdown admin

function renderDropDown(&$db){
    
?>
        <ul class="dropdown">
            <li><a href=".">Home</a></li>
            <li>
                <span>Colaboradores <img style="vertical-align: middle;" src="<?php echo IMAGE_DIR; ?>arrowdown.png"/></span>
                <ul style="min-width: 150px;">
                    <li><a href="newuser.php">Adicionar Novo Colaborador</a></li>
                    <li><a href="searchuser.php">Buscar Colaborador</a></li>
                    <li>
                        <div style="float: right;"><img style="vertical-align: middle; width: 16px; height: 16px;" src="<?php echo IMAGE_DIR; ?>arrowright.png"/></div>
                        <span>Eventos</span>
                        <ul>
                            <li><a href="events.php">Lista de Eventos</a></li>
                            <li><a href="newevent.php">Adicionar Novo Evento</a></li>
                        </ul>
                    </li>
                    <li><a href="userlist.php">Lista de Colaboradores</a></li>
                    <li><a href="lidlist.php">Lista de Login IDs</a></li>
                    <li><a href="userprofile.php">Perfl do Colaborador</a></li>
                    <!-- FEEDBACK SESSION/TALK SESSION
                    <li>
                        <div style="float: right;"><img style="vertical-align: middle; width: 16px; height: 16px;" src="<?php echo IMAGE_DIR; ?>arrowright.png"/></div>
                        <span>Feedback Session</span>
                        <ul>
                            <li><a href="newfs.php">Nova Feedback Session</a></li>
                            <li><a href="searchfs.php">Listar Feedback Session</a></li>
                        </ul>
                    </li>
                    <li>
                        <div style="float: right;"><img style="vertical-align: middle; width: 16px; height: 16px;" src="<?php echo IMAGE_DIR; ?>arrowright.png"/></div>
                        <span>Talk Session</span>
                        <ul>
                            <li><a href="newts.php">Nova Talk Session</a></li>
                            <li><a href="searchts.php">Buscar Talk Session</a></li>
                        </ul>
                    </li>
                    -->
                </ul>
            </li>
            <li>
                <span>Geral <img style="vertical-align: middle;" src="<?php echo IMAGE_DIR; ?>arrowdown.png"/></span>
                <ul style="min-width: 150px;">
                    <li><a href="backup.php">Backup</a></li>
                    <li><a href="manageunits.php">Gerenciar Unidades</a></li>
                    <li><a href="loginstats.php">Histórico de Acesso</a></li>
                    <li><a href="campclosinglog.php">Histórico de Fechamento de<br/>Campnha de Rema</a></li>
                </ul>
            </li>
            <li>
                <span>Sites <img style="vertical-align: middle;" src="<?php echo IMAGE_DIR; ?>arrowdown.png"/></span>
                <ul style="min-width: 150px;">
                    <li><a href="../rema">Rematrícula</a></li>
                    <li><a href="../timebank">Time Bank</a></li>
                    <!--
                    <li><a href="<?php echo createLink('jbadmin'); ?>">Job Bank</a></li>
                    <li><a href="<?php echo createLink('rema'); ?>">Rematr?cula</a></li>
                    <li><a href="<?php echo createLink('timebank'); ?>">Time Bank</a></li>
                    -->
                </ul>
            </li>
            <li><a href="login.php?l=1" onclick="return confirm('Tem certeza que deseja sair?');">Logout</a></li>
        </ul>
<?php
    
}

//---------------------------------------------------------

function createLink($page){
    // set current address for links
    return (isset($_SERVER['SERVER_NAME']) && strpos(strtolower($_SERVER['SERVER_NAME']), 'domain') !== false
                ? 'http://' . $page . '.domain.com'
                : '../' . $page);
}

?>