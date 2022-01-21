<?php

function displayInfo(&$db, $fileName, $tbId, $tbYear, $cats, $line, $saveErr, $msg){
    
    $errorFlag = false;
    
    try {

        // disable xml parser errors
        libxml_use_internal_errors(true);

        // load file
        $html = file_get_contents(UPLOAD_DIR . $fileName);

        // create a new dom object 
        $dom = new domDocument;

        // discard white spaces
        $dom->preserveWhiteSpace = false; 

        // set encoding to latin-1
        //$dom->encoding = 'iso-8859-1';

        // load html into dom object
        if ($html !== false && $dom->loadHTML($html)){
            
            // get table from object
            $table = $dom->getElementsByTagName('table')->item(0);

            // get rows from table
            $rows = $table->getElementsByTagName('tr');
            
            $rowCount = $rows->length;
            
            if (!$rowCount){
                $errorFlag = true;
                $msg = 'O arquivo importado está vasio. Clique <a href="importclasses.php">aqui</a> para selecionar outro arquivo.';
            }
            elseif ($line < $rowCount){
            
                // get columns from row
                $cols = $rows[$line]->getElementsByTagName('td');

                $colArr = array('Class' => null, 'Teacher' => null);

                // loop over the columns
                for ($i = 0; $i < $cols->length; $i++){
                    if (isset($cats[$i]) && $cats[$i] == 1) $colArr['Class'] = sTrim(utf8_decode($cols[$i]->nodeValue));
                    elseif (isset($cats[$i]) && $cats[$i] == 2) $colArr['Teacher'] = sTrim(utf8_decode($cols[$i]->nodeValue));
                }
            
            }
            else {
                
                // end of file reached
?>
        <br/>
        <div class="panel" style="width: 800px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Importar Turmas</span>
            <hr/>
            
            <div style="padding: 5px; color: blue; font-style: italic;">A importação das turmas do arquivo selecionado foi concluída.</div>
<?php

                if (isset($_SESSION[$fileName])){
                    
                    echo '<div style="padding: 5px;">Turmas Criadas: <span style="font-weight: bold;">' . $_SESSION[$fileName]['Saved'] . '</span></div>' . PHP_EOL .
                         '<div style="padding: 5px;">Turmas Ignoradas: <span style="font-weight: bold;">' . $_SESSION[$fileName]['Skipped'] . '</span></div>' . PHP_EOL;

                    // kill session
                    session_destroy();

                }
?>
        </div>
<?php
                
                // delete uploaded file
                unlink(UPLOAD_DIR . $fileName);
                
                return;
                
            }
            
        }
    
    } catch (Exception $ex) {
        $errorFlag = true;
        $msg = 'Ocorreu um erro inesperado. Certifique-se que o arquivo enviado &eacute; v&aacute;lido. Clique <a href="importclasses.php">aqui</a> para tentar novamente.';
    }
               
?>
                
        <br/>
        <div id="msgBox" style="width: 800px; left: 0; right: 0; margin: auto; display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <span style="color: red; font-style: italic;"><?php echo $msg; ?></span>
            </div>
            <br/>
        </div>
<?php

    // error found in uploaded file 
    if ($errorFlag) return;
    
    // select all active teachers
    $result = $db->query("SELECT `ID`, `Name` FROM `users` WHERE `Blocked` = 0 AND Status < 2 ORDER BY `Name`");
    
    $act_teachers = array();
    
    while($row = $result->fetch_assoc()){
        $act_teachers[$row['ID']] = $row['Name'];
    }
    
    $result->close();
    
    // initialize variables
    $selOpt = 0;
    $showOpt3 = false;
    $showOpt4 = false;
    $showOpt5 = false;
    $warning = '';
    $newTchName = '';
    $sem = null;
    $mode = null;
    $date1 = null;
    $date2 = null;
    $date3 = null;
    $date4 = null;
    $chkFlag = createCheckFlagArray(0);
        
    // check if teacher is active
    $selTchId = array_search($colArr['Teacher'], $act_teachers);
    
    if ($selTchId === false){
        
        // check if teacher exists, verify status and if is blocked
        $result = $db->query("SELECT `Status`, `Blocked` FROM `users` WHERE `Name` = '" . $db->real_escape_string($colArr['Teacher']) . "'");
        
        if ($result->num_rows == 1){
            // one teacher found
            $row = $result->fetch_assoc();
            
            if ($row['Status'] == 2 && $row['Blocked'] == 1){
                // not a teacher and blocked
                $showOpt5 = true;
                $warning = '<div style="color: red; font-style: italic;"><img src="../images/warning2.png" style="width: 16px; height: 16px;" /> O colaborador \'' . htmlentities($colArr['Teacher'], 0, 'ISO-8859-1') . '\' está bloqueado e não possui o status de professor.</div>';
                $selOpt = 5;                
            }
            elseif ($row['Status'] == 2){
                // not a teacher
                $showOpt4 = true;
                $warning = '<div style="color: red; font-style: italic;"><img src="../images/warning2.png" style="width: 16px; height: 16px;" /> O colaborador \'' . htmlentities($colArr['Teacher'], 0, 'ISO-8859-1') . '\' não possui o status de professor.</div>';
                $selOpt = 4;
            }
            elseif ($row['Blocked'] == 1){
                // blocked
                $showOpt3 = true;
                $warning = '<div style="color: red; font-style: italic;"><img src="../images/warning2.png" style="width: 16px; height: 16px;" /> O professor \'' . htmlentities($colArr['Teacher'], 0, 'ISO-8859-1') . '\' está inativo.</div>';
                $selOpt = 3;
            }
            
        }
        elseif ($result->num_rows > 1){
            // too many teacher found
            $warning = '<div style="color: red; font-style: italic;"><img src="../images/warning2.png" style="width: 16px; height: 16px;" /> Foram encontrados multipls colaboradores com o nome \'' . htmlentities($colArr['Teacher'], 0, 'ISO-8859-1') . '\' no banco de dados.</div>';
        }
        else {
            // no teachers found
            $warning = '<div style="color: red; font-style: italic;"><img src="../images/warning2.png" style="width: 16px; height: 16px;" /> O professor \'' . htmlentities($colArr['Teacher'], 0, 'ISO-8859-1') . '\' não existe no banco de dados.</div>';
            $newTchName = $colArr['Teacher'];
            $selOpt = 2;
        }
        
        $result->close();
        
    }
    else {
        // teacher found
        $warning = '<div style="color: blue;">Professor titular da turma: <span style="font-style: italic;">' . htmlentities($colArr['Teacher'], 0, 'ISO-8859-1') . '</span></div>';
        $selOpt = 1;
    }
    
    if ($saveErr){
        // change options above if error occur when saving
        $selOpt = getPost('selopt');
        
        if ($selOpt == 1) $selTchId = getPost('teacher');
        elseif ($selOpt == 2) $newTchName = getPost('newtch');
        
        $date1 = getPost('date1');
        $date2 = getPost('date2');
        $date3 = getPost('date3');
        $date4 = getPost('date4');
        
        $sem = getPost('sem');
        $mode = getPost('mode');
        
        $daysArr = getPost('days');
        $days = 0;
        
        if ($daysArr !== null && is_array($daysArr)) {
            
            foreach ($daysArr as $key => $val){
                if (isNum($key) && ($key == 1 || $key == 2 || $key == 4 || $key == 8 || $key == 16 || $key == 32 || $key == 64) && $val === '1'){
                    $days += intval($key, 10);
                }
            }
            
        }
        
        $chkFlag = createCheckFlagArray($days);
        
    }
    
    // build queue
    $queue = '?step=3&f=' . $fileName . '&tb=' . $tbId . '&line=' . $line;
    
    foreach ($cats as $key => $val){
        if ($val == 1 || $val == 2){
            $queue .= '&cats[' . $key . ']=' . $val;
        }
    }
    
?>
        <div class="panel" style="width: 800px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Importar Turmas - Passo 3/3</span>
            <img src="../images/question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/>
            <hr/>
            
            <div style="padding: 5px;">Banco de Horas: &nbsp; <span style="font-weight: bold;"><?php echo $tbYear; ?></span></div>
            
        </div>
        <br/>
        <div class="panel" style="width: 800px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Inserir Turma - <?php echo ($line + 1) . '/' . $rowCount; ?></span>
            <hr/>
            
            <form action="importclasses.php<?php echo $queue; ?>" method="post">
            <div style="padding: 5px; line-height: 200%;">
                
                <div style="padding-bottom: 10px;">
                    <fieldset style=" border-radius: 5px;">
                        <legend>Professor</legend>
                                                                        
                        <?php
                            echo $warning . PHP_EOL . '<div>';
                            buildSelectOptionField($selOpt, $showOpt3, $showOpt4, $showOpt5);
                            echo '</div>';
                        ?>
                        
                        <div id="divSelTch" style="display: <?php echo ($selOpt == 1 ? 'block' : 'none'); ?>;">
                            <select id="selTeacher" name="teacher" style="width: 400px;" onchange="this.styleOption();" onkeyup="this.styleOption();">
                                <option value="0" style="font-style: italic;">- Selecione o professor -</option>
<?php

    foreach ($act_teachers as $tchId => $tchName) {
        echo '<option value="' . $tchId . '" style="font-style: normal;"' . ($tchId == $selTchId ? ' selected="selected"' : '') . '>' . htmlentities($tchName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }

?>
                            </select>
                        </div>
                        <div id="divNewTch" style="display: <?php echo ($selOpt == 2 ? 'block' : 'none'); ?>;">
                            <input type="text" id="txtNewTch" name="newtch" value="<?php echo htmlentities($newTchName, 3, 'ISO-8859-1'); ?>" style="width: 400px;" maxlength="55"/>
                        </div>
                        <div id="divBlank" style="display: <?php echo ($selOpt != 1 && $selOpt != 2 ? 'block' : 'none'); ?>;">&nbsp;</div>
                    </fieldset>
                </div>
                <table class="tbl2" style="border-collapse: collapse; width: 100%;">
                    <tr>
                        <td style="text-align: right; white-space: nowrap;">Nome da Turma:</td>
                        <td style="width: 100%;"><span style="font-weight: bold;"><input type="text" id="txtClsName" name="class_name" value="<?php echo htmlentities(($saveErr ? getPost('class_name') : $colArr['Class']), 3, 'ISO-8859-1'); ?>" style="width: 400px;" maxlength="55"/></span></td>
                    </tr>
                    <tr>
                        <td style="text-align: right; white-space: nowrap;">Semestre:</td>
                        <td style="width: 100%;">
                            <?php echo $tbYear; ?>.<select id="selSem" name="sem">
                                <option value="0"></option>
                                <option value="1"<?php if ($sem == 1) echo ' selected="selected"'; ?>>1</option>
                                <option value="2"<?php if ($sem == 2) echo ' selected="selected"'; ?>>2</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                    <td style="text-align: right; white-space: nowrap;">Período Pago:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtDate1" name="date1" value="<?php echo htmlentities($date1, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                        a
                        <input type="text" id="txtDate2" name="date2" value="<?php echo htmlentities($date2, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                        <img src="../images/question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox1();"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Período de Aula:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtDate3" name="date3" value="<?php echo htmlentities($date3, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                        a
                        <input type="text" id="txtDate4" name="date4" value="<?php echo htmlentities($date4, 3, 'ISO-8859-1'); ?>" style="width: 100px;" placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off" onclick="showCal(this);" onfocus="showCal(this);"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Modalidade:</td>
                    <td style="width: 100%;">
                        <select id="selMod" name="mode">
                            <option style="font-style: italic;" value="0">- Selecione -</option>
                            <option style="font-style: normal;" value="1"<?php if ($mode == 1) echo ' selected="selected"'; ?>>Duas aulas de 1:15</option>
                            <option style="font-style: normal;" value="2"<?php if ($mode == 2) echo ' selected="selected"'; ?>>Uma aula de 2:15</option>
                            <option style="font-style: normal;" value="3"<?php if ($mode == 3) echo ' selected="selected"'; ?>>Uma aula de 2:30</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Dias da Semana:</td>
                    <td style="width: 100%;">
                        <input type="checkbox" class="chkDays" id="chkDays64" name="days[64]" value="1"<?php if ($chkFlag[0]) echo ' checked="checked"'; ?>/><label for="chkDays64"> Dom</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays32" name="days[32]" value="1"<?php if ($chkFlag[1]) echo ' checked="checked"'; ?>/><label for="chkDays32"> Seg</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays16" name="days[16]" value="1"<?php if ($chkFlag[2]) echo ' checked="checked"'; ?>/><label for="chkDays16"> Ter</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays8" name="days[8]" value="1"<?php if ($chkFlag[3]) echo ' checked="checked"'; ?>/><label for="chkDays8"> Qua</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays4" name="days[4]" value="1"<?php if ($chkFlag[4]) echo ' checked="checked"'; ?>/><label for="chkDays4"> Qui</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays2" name="days[2]" value="1"<?php if ($chkFlag[5]) echo ' checked="checked"'; ?>/><label for="chkDays2"> Sex</label> &nbsp;
                        <input type="checkbox" class="chkDays" id="chkDays1" name="days[1]" value="1"<?php if ($chkFlag[6]) echo ' checked="checked"'; ?>/><label for="chkDays1"> Sab</label>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;"></td>
                    <td style="width: 100%;">
                        <button type="submit" name="save" value="1" style="padding: 2px; width: 90px;" onclick="return validateInput();">Salvar</button> &nbsp;
                        <button type="submit" name="skip" value="1" style="padding: 2px; width: 90px;" onclick="return confirm('Tem certeza que deseja ignorar essa turma?');">Ignorar</button>
                    </td>
                </tr>
                </table>
                                
            </div>
            <input type="hidden" name="PostBack" value="1"/>
            <input type="hidden" name="tchfromfile" value="<?php echo htmlentities($colArr['Teacher'], 3, 'ISO-8859-1'); ?>"/>
            </form>
        </div>
        
        <div class="overlay" id="overlay" onclick="hideHelpBox();"></div>
        <div class="helpBox" id="helpBox" style="width: 600px; height: 360px;">
            <div class="closeImg" onclick="hideHelpBox()"></div>
            <span style="font-weight: bold;">Ajuda - Importar Alunos</span>
            <hr>
            <ul style="line-height: 150%;">
                <li><span style="color: red;">Atenção:</span> os passos a seguir devem ser minuciosamente seguidos.</li>
                <li>No campo 'Professor' observe com cuidado a mensagem e selecione a opção desejada.</li>
                <ul>
                    <li>O professor titular poderá ser modificado ou um novo professor poderá ser criado mesmo que a turma possua um porfessor titular.
                    <li>A opção de reativar o professor ou dar status de professor a um colaborador será dada caso o professor esteja inativo ou este não tenha status de professor.</li>
                </ul>
                </li>
                <li>Preencha cuidadosamente os demais campos.</li>
                <li>Click no bot&atilde;o <span style='font-style: italic;'>'Salvar'</span> para gravar as informações da turma atual e passar para a próxima.</li>
                <li>Click no bot&atilde;o <span style='font-style: italic;'>'Ignorar'</span> caso deseje passar para a proxima turma sem salvar a atual.</li>
                <li><span style="color: red;">Importante:</span> Não utilize os botões <span style='font-style: italic;'>recarregar</span> e <span style='font-style: italic;'>voltar</span> do seu navegador, pois isto pode acarretar em turmas duplicadas no banco de dados.</li>
            </ul>
        </div>
        <div class="helpBox" id="helpBox1" style="width: 500px; height: 310px;">
            <div class="closeImg" onclick="hideHelpBox();"></div>
            <span style="font-weight: bold;">Ajuda - Período Pago e Período de Aula</span>
            <hr/>
            <div style="padding: 5px;">O <span style="font-style: italic;">'Período Pago'</span> corresponde ao período em que o professor será pago pela turma, enquanto que o <span style="font-style: italic;">'Período de Aula'</span> corresponde ao período entre o primeiro e o último dia de aula.<br/><br/>
            Por exemplo, se uma determinada turma será paga ao professor entre os meses de fevereiro e junho, o período pago será de 01 de fevereiro a 30 de junho. Caso a mesma turma inicie as aulas no dia 02 de fevereiro e finalize no dia 20 de junho, este será o período de aula.
                <span style="color: red;">Atenção:</span>
                <ul>
                    <li>Caso o período pago se estenda ao mês de janeiro, obrigatótiamente o dia deverá ser 31.</li>
                    <li>O período de aula não poderá se estender ao mês de janeiro.</li>
                </ul>
            </div>
        </div>

<?php

}

//-----------------------------------------------------------------------

function buildSelectOptionField($selOpt, $showOpt3, $showOpt4, $showOpt5){
    
?>
                        <select id="selOpt" name="selopt" style="width: 400px;" onchange="this.styleOption(); showDiv(this.selectedValue());" onkeyup="this.styleOption(); showDiv(this.selectedValue())">
                            <option value="0" style="font-style: italic;">- Selecione a opção desejada -</option>
                            <option value="1" style="font-style: normal;"<?php if ($selOpt == 1) echo ' selected="selected"'; ?>>Selecionar professor existente</option>
                            <option value="2" style="font-style: normal;"<?php if ($selOpt == 2) echo ' selected="selected"'; ?>>Criar novo professor</option>
<?php if ($showOpt3){ ?>
                            <option value="3" style="font-style: normal;"<?php if ($selOpt == 3) echo ' selected="selected"'; ?>>Reativar professor</option>
<?php } if ($showOpt4){ ?>
                            <option value="4" style="font-style: normal;"<?php if ($selOpt == 4) echo ' selected="selected"'; ?>>Atribuir status de professor</option>
<?php } if ($showOpt5){ ?>
                            <option value="5" style="font-style: normal;"<?php if ($selOpt == 5) echo ' selected="selected"'; ?>>Reativar e atribuir status de professor</option>
<?php } ?>
                        </select>
<?php
    
}

?>