<?php

function loadXmlObj(&$db, $fileName, $campName, $uid, $cats, $isDropOuts, $msg){
  
    // fetch active units
    $result = $db->query("SELECT * FROM schools WHERE Active = 1 ORDER BY Name");
    
    // $units = [ID, Name]
    $units = array();
    
    while ($row = $result->fetch_assoc()){
        $units[] = $row;
    }
    
    $result->close();
    
    $countUnit = count($units);
    
    if (!$countUnit) $msg = 'Não há unidades ativas.';
    
?>
        
        
        <br/>
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <span style="color: red; font-style: italic;"><?php echo $msg; ?></span>
            </div>
            <br/>
        </div>
<?php

    if (!$countUnit) return;

?>
        <form id="form1" method="post" action="importstudents.php">
        <div class="panel">
            
            <span style="font-weight: bold;">Importar Alunos - Passo 2/4</span>
            <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox(true);"/>
            <hr/>
            
            <div style="padding: 5px; line-height: 150%;">
                Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span>
<?php
    if ($countUnit == 1) {
        // only one active unit
        echo '<p>Unidade: <span style="font-weight: bold;">' . htmlentities($units[0]['Name'], 0, 'ISO-8859-1') . '</span></p>';
    }
    else {
        // more than one active unit
?>
                <p>Selecione a Unidade: &nbsp;
                <select id="selUnit" name="uid" style="width: 200px;" onchange="styleSelectBox(this);" onkeyup="styleSelectBox(this);">
                    <option style="font-style: italic;">- Selecione -</option>
<?php

    foreach ($units as $uInfo){
        echo '<option value="' . $uInfo['ID'] . '" style="font-style: normal;"' . ($uInfo['ID'] == $uid ? ' selected="selected"' : '') . '>' . htmlentities($uInfo['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }
    
?>
                </select></p>
<?php
    }

?>
                <p><input type="checkbox" id="chkDos" name="dos" value="1"<?php if ($isDropOuts) echo ' checked="checked"'; ?>/><label for="chkDos"> Alunos evadidos</label></p>
                <input type='submit' value="Pr&oacute;ximo Passo >>" onclick="return validateFields();"/>
            </div>
            
        </div>
        
<?php

    $xml = new XMLReader();

    $xml->open(UPLOAD_DIR . $fileName);

    $tableOpen = false;
    $trOpen = false;
    $tdOpen = false;
    $firstRow = true;
    $colCount = 0;
    $bgcolor = '#61269e';

    while($xml->read()) {

        if ($tableOpen){
            
            if (strtolower($xml->name) == 'table'){
                echo '</table><br/><div>' . PHP_EOL;
                break;
            }
            elseif (strtolower($xml->name) == 'tr'){

                if ($trOpen){
                    
                    if (!$colCount){
                        echo '<td style="font-style: italic; color: red;">O arquivo importado está vazio.</td>';
                    }
                    
                    echo '</tr>' . PHP_EOL;
                    $bgcolor = ($bgcolor == '#ffffff') ? '#c6c6c6' : '#ffffff';
                    $firstRow = false;
                    
                }
                else {
                    echo '<tr>' . PHP_EOL;
                }

                $trOpen = !$trOpen;

            }
            elseif (strtolower($xml->name) == 'td'){

                if ($tdOpen){
                    
                    if ($firstRow){
                        
                        $colCount++;
                        
?>
        <select class="selCat" id="selCat<?php echo $colCount; ?>" name="cats[<?php echo $colCount; ?>]" onchange="styleSelCatBox(this); selChanged(this);" onkeyup="styleSelCatBox(this); selChanged(this);">
            <option value="0" style="font-style: italic; background-color: white;">- Selecione -</option>
            <option value="1" style="font-style: normal; background-color: cornflowerblue;"<?php if ($cats[$colCount] == 1) echo ' selected="selected"'; ?>>Aluno</option>
            <option value="2" style="font-style: normal; background-color: cornflowerblue;"<?php if ($cats[$colCount] == 2) echo ' selected="selected"'; ?>>Professor</option>
            <option value="3" style="font-style: normal; background-color: cornflowerblue;"<?php if ($cats[$colCount] == 3) echo ' selected="selected"'; ?>>Turma</option>
            <option value="4" style="font-style: normal; background-color: cornflowerblue;"<?php if ($cats[$colCount] == 4) echo ' selected="selected"'; ?>>Estágio</option>
        </select>
<?php
                        
                    }
                    else {
                        echo trim(preg_replace("/\s+/", " ", $val));
                    }
                    
                    echo '</td>' . PHP_EOL;
                    
                }
                else {
                    echo '<td style="background-color: ' . $bgcolor . ';' . ($firstRow ? ' color: white; text-align: center;' : '') . '">' . PHP_EOL;
                    $val = '';
                }

                $tdOpen = !$tdOpen;

            }
            elseif ($tdOpen){
                // file originally encoded in utf8.
                // convert to latin1
                $val .= utf8_decode($xml->value);
            }

        }
        elseif (strtolower($xml->name) == 'table'){
                // start table
                // closes the main div tag
                echo '</div><br/><table class="tbl">' . PHP_EOL;
                $tableOpen = true;
        }

    }
    
    $xml->close();

?>
        
        <div class="overlay" id="overlay" onclick="showHelpBox(false);"></div>
        <div class="helpBox" id="helpBox" style="width: 500px; height: 240px;">
            <div class="closeImg" onclick="showHelpBox(false)"></div>
            <span style="font-weight: bold;">Ajuda - Importar Alunos</span>
            <hr>
            <ul style="line-height: 150%;">
                <li>Selecione a unidade desejada.</li>
                <li>Selecione a opção <span style="font-style: italic;">'Alunos evadidos'</span> caso a lista importada seja referente a alunos evadidos.</li>
                <li>Na tabela que mostra os dados importados, selecione as categorias das colunas encontradas. Deverão ser selecionadas 4 categorías em 4 colunas diferentes, não podendo nenhuma categoría ficar de fora.</li>
                <li>Click no botão <span style='font-style: italic;'>'Próximo Passo'</span>.</li>
            </ul>
        </div>
        <?php if ($countUnit == 1) echo '<input type="hidden" name="uid" value="' . $units[0]['ID'] . '"/>' . PHP_EOL; ?>
        <input type="hidden" name="f" value="<?php echo $fileName; ?>"/>
        <input type="hidden" name="step" value="3"/>
        </form>
<?php
    
}

?>