<?php

// drop down rema

function renderDropDown(&$db, $isAdmin){
    
    $menu = new DropDown();
    
    // home menu
    $menu->createAndAddMenu('Home', '.');
    
    if ($isAdmin){
        
        $cid = $db->query("SELECT ID FROM campaigns WHERE Open = 1 LIMIT 1")->fetch_row()[0];
        
        // campaign menu
        $campMenu = new DropDownMenu('Campanha');
        
        if (!isset($cid)) $campMenu->createAndAddSubMenu('Criar Nova Campanha', 'newcamp.php');
        
        $campMenu->createAndAddSubMenu('Campanha por Colaborador', 'campbyuser.php');
        $campMenu->createAndAddSubMenu('Buscar Campanha', 'searchcamp.php');
        
        if (isset($cid)){
            // subcamp. menu
            $subCampMenu = new DropDownMenu('Subcampanha');

            $subCampMenu->createAndAddSubMenu('Criar Nova Subcampanha', 'newsubcamp.php');
            $subCampMenu->createAndAddSubMenu('Subcampanha por Colaborador', 'subcampbyuser.php');
            
            $campMenu->addSubMenu($subCampMenu);
        }
        
        $menu->addMenu($campMenu);
        
        // students menu
        $stdMenu = new DropDownMenu('Alunos');
        
        $stdMenu->createAndAddSubMenu('Adicionar Alunos', 'newstudent.php');
        $stdMenu->createAndAddSubMenu('Buscar Aluno', 'searchstudent.php');
        $stdMenu->createAndAddSubMenu('Importar Alunos', 'importstudents.php');
        
        $menu->addMenu($stdMenu);
        
        // group menu
        $groupMenu = new DropDownMenu('Grupos');
        
        $groupMenu->createAndAddSubMenu('Relatório', 'reportgrp.php');
        $groupMenu->createAndAddSubMenu('Criar Grupo', 'newgroup.php');
        $groupMenu->createAndAddSubMenu('Gerenciar Grupos', 'managegrp.php');
        
        $menu->addMenu($groupMenu);
        
        // PPY menu
        $ppyMenu = new DropDownMenu('PPY');
        
        $ppyMenu->createAndAddSubMenu('Relatório', 'ppyrpt.php');
        $ppyMenu->createAndAddSubMenu('Criar/Modificar', 'ppyedit.php');
        $ppyMenu->createAndAddSubMenu('Visualizar Detalhes', 'ppy.php');
        $ppyMenu->createAndAddSubMenu('Índice de Cálculo', 'ppycalcindex.php');
        
        $menu->addMenu($ppyMenu);
        
        // report menu
        $repMenu = new DropDownMenu('Relatórios');
        
        $repMenu->createAndAddSubMenu('Alunos Não Rematriculados', 'reportunenrolled.php');
        $repMenu->createAndAddSubMenu('Motivos', 'reportreasons.php');
        $repMenu->createAndAddSubMenu('PPY', 'ppyrpt.php');
        
        // rema report sub-menu
        $remaRepMenu = new DropDownMenu('Rematrícula');
        
        $remaRepMenu->createAndAddSubMenu('Rema Por Estágio', 'reportlevel.php');
        $remaRepMenu->createAndAddSubMenu('Rema Por Grupo', 'reportgrp.php');
        $remaRepMenu->createAndAddSubMenu('Rema Por Professor', 'reportteacher.php');
        $remaRepMenu->createAndAddSubMenu('Rema Por Programa', 'reportprog.php');
        $remaRepMenu->createAndAddSubMenu('Rema Por Turma', 'reportclass.php');
        $remaRepMenu->createAndAddSubMenu('Rema Por Unidade', 'reportunit.php');
        
        $repMenu->addSubMenu($remaRepMenu);
        
        $repMenu->createAndAddSubMenu('Subcampanha', 'subcamprpt.php');
        
        $menu->addMenu($repMenu);
        
        // general menu
        $genMenu = new DropDownMenu('Geral');
        
        $genMenu->createAndAddSubMenu('Gerenciar Contratos Anuais', 'manageyrcont.php');
        $genMenu->createAndAddSubMenu('Gerenciar Estágios', 'managelevels.php');
        $genMenu->createAndAddSubMenu('Gerenciar Grupos', 'managegrp.php');
        $genMenu->createAndAddSubMenu('Gerenciar Motivos', 'managereasons.php');
        $genMenu->createAndAddSubMenu('Gerenciar Programas', 'manageprog.php');
        $genMenu->createAndAddSubMenu('Gerenciar Turmas', 'manageclasses.php');
        
        $menu->addMenu($genMenu);
        
        // sites menu
        $siteMenu = new DropDownMenu('Sites');
        
        $siteMenu->createAndAddSubMenu('Admin', '../admin');
        $siteMenu->createAndAddSubMenu('Time Bank', '../timebank');
        
        $menu->addMenu($siteMenu);
        
    }
    else {
        
        // search menu
        $searchMenu = new DropDownMenu('Buscar');
        
        $searchMenu->createAndAddSubMenu('Aluno', 'searchstudent.php');
        $searchMenu->createAndAddSubMenu('Campanha', 'searchcamp.php');
        $searchMenu->createAndAddSubMenu('Subcampanha', 'subcampbyuser.php');
        
        $menu->addMenu($searchMenu);
        
        // other menus
        $menu->createAndAddMenu('Banco de Horas', '../timebank');
        $menu->createAndAddMenu('Perfil', 'editprofile.php');
        $menu->createAndAddMenu('PPY', 'ppy.php');
        
    }
    
    // logout menu
    $menu->createAndAddMenu('Logout', 'login.php?l=1', 'Tem certeza que deseja sair?');
    
    
    // render menu
    $menu->renderDropDownMenu();
    
}

?>