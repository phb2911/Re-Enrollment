<?php

function displayInfo(&$db, &$actTeachers, &$levels, &$programs, $fileName, $campName, $uid, $unitName, $cats, $isDropOuts, $errArr, $msg){
       
?>
        
        <br/>
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <span style="color: red; font-style: italic;"><?php echo $msg; ?></span>
            </div>
            <br/>
        </div>
        <div class="panel">
            
            <span style="font-weight: bold;">Importar Alunos - Passo 3/4</span>
            <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox(true);"/>
            <hr/>
            
            <table>
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Unidade:</td>
                    <td style="font-weight: bold;"><?php echo htmlentities($unitName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Alunos evadidos:</td>
                    <td style="font-weight: bold;"><?php echo ($isDropOuts ? 'Sim' : 'Não'); ?></td>
                </tr>
            </table>
            
        </div>
<?php

    // classify cols
    $columns = array();

    foreach ($cats as $col => $cat) {
        if ($cat == 1) $columns['student'] = $col;
        elseif ($cat == 2) $columns['teacher'] = $col;
        elseif ($cat == 3) $columns['class'] = $col;
        elseif ($cat == 4) $columns['level'] = $col;
    }

    // find errors
    $tableOpen = false;
    $trOpen = false;
    $tdOpen = false;
    $firstRow = true;
    $curCol = 0;
    $curRow = 0;
    $endOfCell = false;
    $val = '';
    
    $newTchCol = array(); // $newTchCol[] = array(Name);
    $lvlIssueCol = array(); // $lvlIssueCol[] = array(ID, Name, Program, Active)
    $invalidRows = array();
    
    $xml = new XMLReader();

    $xml->open(UPLOAD_DIR . $fileName);
    
    while($xml->read()) {
        
        if (strtolower($xml->name) == 'table' && $tableOpen){
            // end of table
            break;
        }
        elseif (strtolower($xml->name) == 'table'){
            // beginning of table
            $tableOpen = true;
        }
        elseif (strtolower($xml->name) == 'tr' && $trOpen){
            // end of row
            $trOpen = false;
            $firstRow = false;
            // reset column flag
            $curCol = 0;
        }
        elseif (strtolower($xml->name) == 'tr'){
            // beginning of row
            $trOpen = true;
            if (!$firstRow) $curRow++;
        }
        elseif (strtolower($xml->name) == 'td' && $tdOpen){
            // end of cell
            $tdOpen = false;
            $endOfCell = true;
            $val = trim(preg_replace("/\s+/", " ", $val)); // remove excess white spaces
        }
        elseif (strtolower($xml->name) == 'td'){
            // beginning of cell
            $tdOpen = true;
            // reset value
            $val = '';
            // set column position
            $curCol++;
        }
        elseif ($tdOpen){
            // file originally encoded in utf8.
            // convert to latin1
            $val .= utf8_decode($xml->value);
        }
        
        if ($endOfCell && !$firstRow){
            
            // check if the current cell is empty then add to
            // invalid rows collection
            if (in_array($curCol, $columns) && !in_array($curRow, $invalidRows) && !strlen($val)){
                $invalidRows[] = $curRow;
            }
            // if it is a teacher not previously found,
            // retrieve id, store in $tId and add to collection
            // if it is a new teacher, $tId will remain 0 
            elseif ($columns['teacher'] == $curCol && strlen($val) && !in_array($val, $newTchCol)){
                
                $found = false;
                
                // case insensitive comparison
                foreach ($actTeachers as $name){
                    if (strtolower($name) == strtolower($val)) {
                        $found = true;
                        break;
                    }
                }
                
                // new teacher not found, add to array
                if (!$found) $newTchCol[] = $val;
                
            }
            // check if the level can be found in DB, store into array collection
            // with respective id numter. If now, store into collection and
            // attribute the id = 0
            elseif ($columns['level'] == $curCol && strlen($val) && !in_collection($val, $lvlIssueCol)){
                
                $found = false;
                
                // case insensitive comparison
                foreach ($levels as $lvlInfo){
                    if (strtolower($lvlInfo['Name']) == strtolower($val)){
                        
                        // check if level is inactive
                        // if so, add to collection
                        if (!$lvlInfo['Active']) $lvlIssueCol[] = $lvlInfo;;
                        
                        $found = true;
                        break;
                        
                    }
                }
                
                // level not found
                if (!$found){
                    $lvlIssueCol[] = array('ID' => 0, 'Name' => $val);
                }
                
            }
            
            $endOfCell = false;
            
        }
                
    }
    
    $xml->close();
    
?>
        <br/>
        <form id="frmStep3" method="post" action="importstudents.php">
        <!--<form id="frmStep3" method="post" action="../temp/temp6.php">-->
        <div class="panel">
            
            <span style="font-weight: bold;">Verificação de Erros</span>
            <hr/>
<?php
    if (!$curRow){
        echo '<div style="padding: 5px; font-style: italic; color: red;">O arquivo importado não contém dados. Por favor selecione outro arquivo.</div>';
    }
    elseif (count($newTchCol) || count($lvlIssueCol) || count($invalidRows)){
        
?>
            <div style="padding: 5px; font-style: italic; color: red;">Abaixo está a descrição de alguns erros encontrados no arquivo importado. Selecione a ação e pressione 'Gravar Dados'.</div>
            
            <table style="width: 100%; border: #61269e solid 1px;">
                <tr>
                    <td style="width: 70%; background-color: #61269e; color: white;">Descrição</td>
                    <td style="width: 30%; background-color: #61269e; color: white;">Ação</td>
                </tr>
<?php

        $bgcolor = null;

        foreach ($newTchCol as $errIndex => $tName){
            
            $bgcolor = ($bgcolor == '#f1f1f1') ? '#c6c6c6' : '#f1f1f1';

            // try to find new teacher in db
            $row = $db->query("SELECT ID, Status, Blocked FROM users WHERE Name = '" . $db->real_escape_string($tName) . "'")->fetch_assoc();

            // check if the new teacher is an inactive teacher
            if ($row !== null && $row['Status'] < 2 && $row['Blocked']){
?>
                <tr>
                    <td style="background-color: <?php echo $bgcolor; ?>;">
                        Professor <span style="font-style: italic; font-weight: bold;">'<?php echo htmlentities($tName, 0, 'ISO-8859-1'); ?>'</span> foi encontrado no banco de dados, mas este encontra-se bloqueado.
                    </td>
                    <td style="background-color: <?php echo $bgcolor; ?>; line-height: 150%; white-space: nowrap;">
                        
                        <select class="selAction" id="selAction<?php echo $errIndex; ?>" style="width:250px;" name="action[<?php echo $errIndex; ?>]" onchange="styleActSel(this, <?php echo $errIndex; ?>);" onkeyup="styleActSel(this, <?php echo $errIndex; ?>);">
                            <option value="4" style="font-style: normal; background-color: white;">Desbloquear professor</option>
                            <option value="1" style="font-style: normal; background-color: white;"<?php if (getPostArr('action', $errIndex) == 1) echo ' selected="selected"'; ?>>Criar novo professor</option>
                            <option value="2" style="font-style: normal; background-color: white;"<?php if (getPostArr('action', $errIndex) == 2) echo ' selected="selected"'; ?>>Ignorar professor</option>
                            <option value="3" style="font-style: normal; background-color: white;"<?php if (getPostArr('action', $errIndex) == 3) echo ' selected="selected"'; ?>>Substituir professor por:</option>
                        </select>
                        
                        <select class="selTeacher" id="selTeacher<?php echo $errIndex; ?>" name="repbyid[<?php echo $errIndex; ?>]" style="width: 250px; visibility: hidden;<?php if (is_array($errArr) && in_array($errIndex, $errArr['action'])) echo ' background-color: #ff8080;'; ?>" onchange="this.styleOption(); this.style.backgroundColor = '';" onkeyup="this.styleOption(); this.style.backgroundColor = '';">
                            <option value="0" style="font-style: italic;  background-color: white;">- Selecione -</option>
<?php

                foreach ($actTeachers as $id => $name){
                    echo '                            <option value="' . $id . '" style="font-style: normal; background-color: white;"' . (getPostArr('action', $errIndex) == 3 && getPostArr('repbyid', $errIndex) == $id ? ' selected="selected"' : '') . '>' . htmlentities($name, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
                }

?>
                        </select>
                        <input type="hidden" name="teacher[<?php echo $errIndex; ?>]" value="<?php echo htmlentities($tName, 3, 'ISO-8859-1'); ?>"/>
                        <input type="hidden" name="teacherId[<?php echo $errIndex; ?>]" value="<?php echo $row['ID']; ?>"/>
                    </td>
                </tr>
<?php                    
            }
            elseif ($row !== null && $row['Status'] == 2){
                // teacher found as an admin
?>
                <tr>
                    <td style="background-color: <?php echo $bgcolor; ?>;">
                        Professor <span style="font-style: italic; font-weight: bold;">'<?php echo htmlentities($tName, 0, 'ISO-8859-1'); ?>'</span> foi encontrado no banco de dados, mas este está marcado apenas como administrador<?php if ($row['Blocked']) echo ' e encontra-se bloqueado';?>.
                    </td>
                    <td style="background-color: <?php echo $bgcolor; ?>; line-height: 150%; white-space: nowrap;">
                        
                        <select class="selAction" id="selAction<?php echo $errIndex; ?>" style="width:250px;" name="action[<?php echo $errIndex; ?>]" onchange="styleActSel(this, <?php echo $errIndex; ?>);" onkeyup="styleActSel(this, <?php echo $errIndex; ?>);">
                            <option value="5" style="font-style: normal; background-color: white;">Marcar como professor<?php if ($row['Blocked']) echo ' e desbloquear'; ?></option>
                            <option value="1" style="font-style: normal; background-color: white;"<?php if (getPostArr('action', $errIndex) == 1) echo ' selected="selected"'; ?>>Criar novo professor</option>
                            <option value="2" style="font-style: normal; background-color: white;"<?php if (getPostArr('action', $errIndex) == 2) echo ' selected="selected"'; ?>>Ignorar professor</option>
                            <option value="3" style="font-style: normal; background-color: white;"<?php if (getPostArr('action', $errIndex) == 3) echo ' selected="selected"'; ?>>Substituir professor por:</option>
                        </select>
                        
                        <select class="selTeacher" id="selTeacher<?php echo $errIndex; ?>" name="repbyid[<?php echo $errIndex; ?>]" style="width: 250px; visibility: hidden;<?php if (is_array($errArr) && in_array($errIndex, $errArr['action'])) echo ' background-color: #ff8080;'; ?>" onchange="this.styleOption(); this.style.backgroundColor = '';" onkeyup="this.styleOption(); this.style.backgroundColor = '';">
                            <option value="0" style="font-style: italic;  background-color: white;">- Selecione -</option>
<?php

                foreach ($actTeachers as $id => $name){
                    echo '                            <option value="' . $id . '" style="font-style: normal; background-color: white;"' . (getPostArr('action', $errIndex) == 3 && getPostArr('repbyid', $errIndex) == $id ? ' selected="selected"' : '') . '>' . htmlentities($name, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
                }

?>
                        </select>
                        <input type="hidden" name="teacher[<?php echo $errIndex; ?>]" value="<?php echo htmlentities($tName, 3, 'ISO-8859-1'); ?>"/>
                        <input type="hidden" name="teacherId[<?php echo $errIndex; ?>]" value="<?php echo $row['ID']; ?>"/>
                    </td>
                </tr>
<?php                    
            }
            else {
                // it is definitelly a new teacher
?>
                <tr>
                    <td style="background-color: <?php echo $bgcolor; ?>;">
                        Professor <span style="font-style: italic; font-weight: bold;">'<?php echo htmlentities($tName, 0, 'ISO-8859-1'); ?>'</span> não encontrado no banco de dados.
                    </td>
                    <td style="background-color: <?php echo $bgcolor; ?>; line-height: 150%; white-space: nowrap;">
                        
                        <select class="selAction" id="selAction<?php echo $errIndex; ?>" style="width:250px;" name="action[<?php echo $errIndex; ?>]" onchange="styleActSel(this, <?php echo $errIndex; ?>);" onkeyup="styleActSel(this, <?php echo $errIndex; ?>);">
                            <option value="1" style="font-style: normal; background-color: white;"<?php if (getPostArr('action', $errIndex) == 1) echo ' selected="selected"'; ?>>Criar novo professor</option>
                            <option value="2" style="font-style: normal; background-color: white;"<?php if (getPostArr('action', $errIndex) == 2) echo ' selected="selected"'; ?>>Ignorar professor</option>
                            <option value="3" style="font-style: normal; background-color: white;"<?php if (getPostArr('action', $errIndex) == 3) echo ' selected="selected"'; ?>>Substituir professor por:</option>
                        </select>
                        
                        <select class="selTeacher" id="selTeacher<?php echo $errIndex; ?>" name="repbyid[<?php echo $errIndex; ?>]" style="width: 250px; visibility: hidden;<?php if (is_array($errArr) && in_array($errIndex, $errArr['action'])) echo ' background-color: #ff8080;'; ?>" onchange="this.styleOption(); this.style.backgroundColor = '';" onkeyup="this.styleOption(); this.style.backgroundColor = '';">
                            <option value="0" style="font-style: italic;  background-color: white;">- Selecione -</option>
<?php

                foreach ($actTeachers as $id => $name){
                    echo '                            <option value="' . $id . '" style="font-style: normal; background-color: white;"' . (getPostArr('action', $errIndex) == 3 && getPostArr('repbyid', $errIndex) == $id ? ' selected="selected"' : '') . '>' . htmlentities($name, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
                }

?>
                        </select>
                        <input type="hidden" name="teacher[<?php echo $errIndex; ?>]" value="<?php echo htmlentities($tName, 3, 'ISO-8859-1'); ?>"/>
                    </td>
                </tr>
<?php
            }
            
        }
        
        // find level issues
        foreach ($lvlIssueCol as $errIndex => $lvlInfo){
            
            $bgcolor = ($bgcolor == '#f1f1f1') ? '#c6c6c6' : '#f1f1f1';
            $isNew = ($lvlInfo['ID'] == 0); // if not new, it is inactive
                
?>
                <tr>
                    <td style="background-color: <?php echo $bgcolor; ?>;">O estágio <span style="font-style: italic; font-weight: bold;">'<?php echo htmlentities($lvlInfo['Name'], 0, 'ISO-8859-1'); ?>'</span> <?php echo ($isNew ? 'não existe no banco de dados.' : 'está inativo.') ?></td>
                    <td style="background-color: <?php echo $bgcolor; ?>; line-height: 150%; white-space: nowrap;">
                        <table style="border-collapse: collapse;">
                            <tr>
                                <td style="padding: 0;">
                                    <select class="selLvlAction" id="selLvlAction<?php echo $errIndex; ?>" style="width:250px;" name="lvlaction[<?php echo $errIndex; ?>]" onchange="styleLvlActSel(this, <?php echo $errIndex; ?>);" onkeyup="styleLvlActSel(this, <?php echo $errIndex; ?>);">
                                        <?php
                                        
                                        if ($isNew){ // level can only be created if it doesn't exist (unique constraing in db)
                                            echo '<option value="1" style="font-style: normal; background-color: white;"' . (getPostArr('lvlaction', $errIndex) == 1 ? ' selected="selected"' : '') . '>Criar novo estágio:</option>';
                                        }
                                        else { // add option to activate level
                                            echo '<option value="4" style="font-style: normal; background-color: white;">Reativar estágio</option>';
                                        }
                                        
                                        ?>
                                        <option value="2" style="font-style: normal; background-color: white;"<?php if (getPostArr('lvlaction', $errIndex) == 2) echo ' selected="selected"'; ?>>Ignorar estágio</option>
                                        <option value="3" style="font-style: normal; background-color: white;"<?php if (getPostArr('lvlaction', $errIndex) == 3) echo ' selected="selected"'; ?>>Substituir estágio por:</option>
                                    </select>
                                </td>
                                <td style="padding: 0; padding-left: 3px; min-width: 250px;">
                                    <select class="selLevel" id="selLevel<?php echo $errIndex; ?>" name="replvl[<?php echo $errIndex; ?>]" style="width: 250px; display: none;<?php if (is_array($errArr) && in_array($errIndex, $errArr['lvlAct'])) echo ' background-color: #ff8080;'; ?>" onchange="this.styleOption(); this.style.backgroundColor = '';" onkeyup="this.styleOption(); this.style.backgroundColor = '';">
                                        <option value="0" style="font-style: italic;  background-color: white;">- Selecione -</option>
<?php
                
            foreach ($levels as $l){
                if ($l['Active']){ // check if level is active
                    echo '<option value="' . $l['ID'] . '" style="font-style: normal; background-color: white;"' . (getPostArr('lvlaction', $errIndex) == 3 && getPostArr('replvl', $errIndex) == $l['ID'] ? ' selected="selected"' : '') . '>' . htmlentities($l['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
                }
            }
                
?>
                                    </select>
<?php if ($isNew){ // not necessary if student is not new ?>
                                    <select class="selProgr" id="selProgr<?php echo $errIndex; ?>" name="repprg[<?php echo $errIndex; ?>]" style="width: 250px; <?php echo ($isNew ? 'display: inline;' : 'display: none;'); if (is_array($errArr) && in_array($errIndex, $errArr['lvlProg'])) echo ' background-color: #ff8080;'; ?>" onchange="this.styleOption(); this.style.backgroundColor = '';" onkeyup="this.styleOption(); this.style.backgroundColor = '';">
                                        <option value="0" style="font-style: italic;  background-color: white;">- Selecione o Programa -</option>
<?php
                
            foreach ($programs as $pID => $pName){
                echo '<option value="' . $pID . '" style="font-style: normal; background-color: white;"' . (getPostArr('lvlaction', $errIndex) == 1 && getPostArr('repprg', $errIndex) == $pID ? ' selected="selected"' : '') . '>' . htmlentities($pName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
            }
                
?>
                                    </select>
<?php } ?>
                                    <input type="hidden" name="levelName[<?php echo $errIndex; ?>]" value="<?php echo htmlentities($lvlInfo['Name'], 3, 'ISO-8859-1'); ?>"/>
                                    <?php if (!$isNew) echo '<input type="hidden" name="levelId[' . $errIndex . ']" value="' . $lvlInfo['ID'] . '"/>'; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
<?php
            
        }
        
        // print invalid rows
        foreach ($invalidRows as $rowNumber){
            
            $bgcolor = ($bgcolor == '#f1f1f1') ? '#c6c6c6' : '#f1f1f1';
                
?>
                <tr>
                    <td style="background-color: <?php echo $bgcolor; ?>;">A linha <span style="font-weight: bold; font-style: italic;"><?php echo $rowNumber; ?></span> contém dados inválidos. Clique <a href="#L<?php echo $rowNumber; ?>">aqui</a> para visualizar.</td>
                    <td style="background-color: <?php echo $bgcolor; ?>; font-style: italic; font-weight: bold;">Ignorar linha</td>
                </tr>
<?php
            
        }
        
        $bgcolor = null;

?>
            </table>
<?php
    }
    else {
        echo '<div style="padding: 5px; font-style: italic; color: blue;">Não foram encontrados erros. Clique em \'Gravar Dados\' para inserir os dados no banco de dados.</div>';
    }
    
    if ($curRow) echo '        <br/><button id="btnBack" type="button" style="width: 90px;" onclick="window.location = \'importstudents.php?f=' . $fileName . '\'"><img id="imgBack" src="' . IMAGE_DIR . 'arrowback.png"/> Voltar</button> <button type="button" onclick="submitForm(this);"><img id="imgDisk" src="' . IMAGE_DIR . 'disk2.png"/> Gravar Dados</button> <img id="imgRecLoader" src="' . IMAGE_DIR . 'circle_loader.gif" style="vertical-align: middle; visibility: hidden;"/>';
?>
        </div>
        <input type="hidden" name="f" value="<?php echo $fileName; ?>"/>
        <input type="hidden" name="uid" value="<?php echo $uid; ?>"/>
        <input type="hidden" name="dos" value="<?php echo ($isDropOuts ? '1' : '0'); ?>"/>
<?php
        
    foreach ($cats as $key => $value){
        if ($value){
            echo '        <input type="hidden" name="cats[' . $key . ']" value="' . $value . '"/>' . PHP_EOL;
        }
    }
        
?>
        <input type="hidden" name="step" value="4"/>
        </form>
<?php

    if ($curRow){
        
?>
        <br/>
        <table class="tbl">
            <tr>
                <td style="background-color: #61269e; color: white; text-align: center;">#</td>
                <td style="background-color: #61269e; color: white; width: 25%;">Aluno</td>
                <td style="background-color: #61269e; color: white; width: 25%;">Turma</td>
                <td style="background-color: #61269e; color: white; width: 25%;">Professor</td>
                <td style="background-color: #61269e; color: white; width: 25%;">Estágio</td>
            </tr>
<?php
        
        // exibir dados
        $tableOpen = false;
        $trOpen = false;
        $tdOpen = false;
        $firstRow = true;
        $curCol = 0;
        $curRow = 0;
        $val = '';
        $stdVal = '';
        $clsVal = '';
        $teaVal = '';
        $lvlVal = '';
        
        $xml = new XMLReader();

        $xml->open(UPLOAD_DIR . $fileName);
        
        $bgcolor = null;
        
        while($xml->read()) {
        
            if (strtolower($xml->name) == 'table' && $tableOpen){
                // end of table
                break;
            }
            elseif (strtolower($xml->name) == 'table'){
                // beginning of table
                $tableOpen = true;
            }
            elseif (strtolower($xml->name) == 'tr' && $trOpen){
                
                $bgcolor = ($bgcolor == '#ffffff') ? '#c6c6c6' : '#ffffff';
                
                if (!$firstRow){
?>
            <tr<?php echo (in_array($curRow, $invalidRows) ? ' style="color: red;"' : ''); ?>>
                <td style="background-color: <?php echo $bgcolor; ?>; text-align: right;"><?php echo '<span id="L' . $curRow . '">' . $curRow . '</span>'; ?></td>
                <td style="background-color: <?php echo $bgcolor; ?>;"><?php echo htmlentities($stdVal, 0, 'ISO-8859-1'); ?></td>
                <td style="background-color: <?php echo $bgcolor; ?>;"><?php echo htmlentities($clsVal, 0, 'ISO-8859-1'); ?></td>
                <td style="background-color: <?php echo $bgcolor; ?>;"><?php echo htmlentities($teaVal, 0, 'ISO-8859-1'); ?></td>
                <td style="background-color: <?php echo $bgcolor; ?>;"><?php echo htmlentities($lvlVal, 0, 'ISO-8859-1'); ?></td>
            </tr>
<?php
                }
                
                // end of row
                $trOpen = false;
                $firstRow = false;
                // reset column flag
                $curCol = 0;
                
            }
            elseif (strtolower($xml->name) == 'tr'){
                // beginning of row
                $trOpen = true;
                if (!$firstRow) $curRow++;
            }
            elseif (strtolower($xml->name) == 'td' && $tdOpen){
                // end of cell
                $tdOpen = false;
                // remove excess white spaces
                $val = sTrim($val); // sTrim from genreq.php
                
                if (!$firstRow){
                
                    if ($curCol == $columns['student'])     $stdVal = $val;
                    elseif ($curCol == $columns['class'])   $clsVal = $val;
                    elseif ($curCol == $columns['teacher']) $teaVal = $val;
                    elseif ($curCol == $columns['level'])   $lvlVal = $val;
                    
                }
                
            }
            elseif (strtolower($xml->name) == 'td'){
                // beginning of cell
                $tdOpen = true;
                // reset value
                $val = '';
                // set column position
                $curCol++;
            }
            elseif ($tdOpen){
                // file originally encoded in utf8.
                // convert to latin1
                $val .= utf8_decode($xml->value);
            }

        }
        
        $xml->close();
        
?>
        </table>
        
        <div class="overlay" id="overlay" onclick="showHelpBox(false);"></div>
        <div class="helpBox" id="helpBox" style="width: 700px; height: 480px;">
            <div class="closeImg" onclick="showHelpBox(false)"></div>
            <span style="font-weight: bold;">Ajuda - Verificação de Erros</span>
            <hr>
            
            <ul style="line-height: 150%;">
                <li>No painel <span style="font-style: italic;">'Verificação de Erros'</span> serão exibidos os erros encontrados no arquivo importado.</li>
                <li>
                    Caso haja erro com o professor, selecione a ação desejada:
                    <ul>
                        <li><span style="font-weight: bold;">Criar professor:</span> Será criado um novo professor e as turmas atribuídas à este.</li>
                        <li><span style="font-weight: bold;">Ignorar professor:</span> Os alunos relativos à este professor não serão gravados no banco de dados.</li>
                        <li><span style="font-weight: bold;">Substituir por:</span> Após selecionar esta opção, o nome de um professor já existente deverá ser selecionado. Os alunos serão associados ao professor escolhido.</li>
                        <li><span style="font-weight: bold;">Desbloquear professor:</span> Caso o professor esteja bloqueado, esta opção irá desbloquea-lo.</li>
                        <li><span style="font-weight: bold;">Marcar como professor:</span> Caso o colaborador esteja marcado como administrador e não como professor, selecionando esta opção o marcará como professor e administrador.</li>
                    </ul>
                </li>
                <li>
                    Caso haja erro com o estágio, selecione a ação desejada:
                    <ul>
                        <li><span style="font-weight: bold;">Criar novo estágio:</span> Será criado um novo estágio. É necessário que um programa seja atribuído ao novo estágio.</li>
                        <li><span style="font-weight: bold;">Ignorar estágio:</span> Os alunos relativos às turmas deste estágio serão ignorados.</li>
                        <li><span style="font-weight: bold;">Substituir estágio por:</span> Após selecionar esta opção, o nome de um estágio já existente deverá ser selecionado. As turmas serão atribuidas ao estágio selecionado.</li>
                    </ul>
                </li>
                <li>Caso hava campos em branco, a linha será automaticamente ignorada. Para verificar o erro, clique no número da linha.</li>
                <li>Click no botão <span style='font-style: italic;'>'Gravar Dados'</span> para finalizar.</li>
            </ul>

        </div>
<?php

    }
    
}

//--------------------------------------------------------------------------

// searches the collection for a specific element name.
// the collection must be an array with the following format:
// $col[] = array(Name, [...]);
function in_collection($name, $col){
    
    // the comparison is case insensitive
    foreach ($col as $elArr){
        if (strtolower($elArr['Name']) == strtolower($name)) return true;
    }
    
    return false;
    
}

//--------------------------------------------------------------------------

// gets value from $_POST 2 elements deep
function getPostArr($index1, $index2){
    return (isset($_POST[$index1]) && isset($_POST[$index1][$index2]) ? $_POST[$index1][$index2] : null);
}

?>