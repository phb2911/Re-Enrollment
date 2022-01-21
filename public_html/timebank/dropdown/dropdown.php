<?php

// dropdown timebank

function renderDropDown(&$db, $isAdmin){
    
    $menu = new DropDown();
    
    if ($isAdmin){
        
        // check if there are active time banks
        $activeFlag = !!($db->query("SELECT COUNT(*) FROM tb_banks WHERE Active = 1")->fetch_row()[0]);
    
        // home menu
        $menu->createAndAddMenu('Home', '.');
        
        // time bank menu
        $tbMenu = new DropDownMenu('Banco de Horas');
        
        $tbMenu->createAndAddSubMenu('Lista de Bancos de Horas', 'bankslist.php');
        $tbMenu->createAndAddSubMenu('Criar Novo Banco de Horas', 'newbank.php');
        
        if ($activeFlag){
            
            // add remove discouts sub-menu
            $addRemMenu = new DropDownMenu('Adicionar/Remover Descontos');
            
            $addRemMenu->createAndAddSubMenu('Por Professor', 'adddisc.php');
            $addRemMenu->createAndAddSubMenu('Multiplos Professores', 'addmultidisc.php');
            
            $tbMenu->addSubMenu($addRemMenu);
            
            
            $tbMenu->createAndAddSubMenu('Adicionar/Remover Créditos', 'addcredit.php');
            
        }
        
        $menu->addMenu($tbMenu);
        
        // classes menu
        $clsMenu = new DropDownMenu('Turmas');
        
        $clsMenu->createAndAddSubMenu('Lista de Turmas', 'classlist.php');
        $clsMenu->createAndAddSubMenu('Buscar Turma', 'classsearch.php');
        
        if ($activeFlag){
            $clsMenu->createAndAddSubMenu('Adicionar Turmas', 'addclasses.php');
            $clsMenu->createAndAddSubMenu('Importar Turmas', 'importclasses.php');
        }
        
        $menu->addMenu($clsMenu);
        
        // holydays menu
        $holMenu = new DropDownMenu('Feriados');
        
        $holMenu->createAndAddSubMenu('Lista de Feriados', 'holidays.php');
        $holMenu->createAndAddSubMenu('Adicionar/Modificar Feriado', 'addholiday.php');
        
        $menu->addMenu($holMenu);
        
        // Report menu
        $rptMenu = new DropDownMenu('Relatórios');
        
        $rptMenu->createAndAddSubMenu('Geral', 'report.php?t=1');
        $rptMenu->createAndAddSubMenu('Por Professor', 'report.php?t=2');
        
        $menu->addMenu($rptMenu);
        
        // sites menu
        $siteMenu = new DropDownMenu('Sites');
        
        $siteMenu->createAndAddSubMenu('Admin', '../admin');
        $siteMenu->createAndAddSubMenu('Rematr&iacute;cula', '../rema');
                        
        $menu->addMenu($siteMenu);
    
    }
    else {
        
        // time bank menu
        $tbMenu = new DropDownMenu('Banco de Horas');
        
        $tbMenu->createAndAddSubMenu('Lista de Bancos de Horas', 'bankslist.php');
        
        $menu->addMenu($tbMenu);
        
        // rema menu
        $menu->createAndAddMenu('Rematr&iacute;cula', '../rema');
        
    }
    
    // logout menu
    $menu->createAndAddMenu('Logout', 'login.php?l=1', 'Tem certeza que deseja sair?');
    
    // render menu
    $menu->renderDropDownMenu();
    
}

?>