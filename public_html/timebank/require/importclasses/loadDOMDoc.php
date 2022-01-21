<?php

function loadDOMDoc($fileName, $tbId, $bhYear, $cats, $msg){
    
    $rowArr = array();
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
        $dom->encoding = 'iso-8859-1';

        // load html into dom object
        if ($html !== false && $dom->loadHTML($html)){

            // get table from object
            $table = $dom->getElementsByTagName('table')->item(0);

            // get rows from table
            $rows = $table->getElementsByTagName('tr');

            // loop over the table rows
            foreach ($rows as $row){

                $colArr = array();
                
                // get columns from row
                $cols = $row->getElementsByTagName('td');

                // loop over the columns
                foreach ($cols as $col) {
                    // file originally encoded in utf8.
                    // convert to latin1 using utf8_decode
                    $colArr[] = sTrim(utf8_decode($col->nodeValue));
                }
                
                $rowArr[] = $colArr;

            }

        }
    
    } catch (Exception $ex) {
        $errorFlag = true;
        $msg = 'Ocorreu um erro inesperado. Certifique-se que o arquivo enviado &eacute; v&aacute;lido. Clique <a href="importclasses.php">aqui</a> para tentar novamente.';
    }
    
    $rowCount = count($rowArr);
    
    if (!$rowCount){
        $errorFlag = true;
        $msg = 'O arquivo enviado est&aacute; v&aacute;sio. Clique <a href="importclasses.php">aqui</a> para selecionar outro arquivo.';
    }
    
?>
                
        <br/>
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <span style="color: red; font-style: italic;"><?php echo $msg; ?></span>
            </div>
            <br/>
        </div>
<?php if ($errorFlag) return; // error found in uploaded file ?>
        
        <form id="form1" method="get" action="importclasses.php">
        <div class="panel">
            
            <span style="font-weight: bold;">Importar Turmas - Passo 2/3</span>
            <img src="../images/question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/>
            <hr/>
            
            <div style="padding: 5px; line-height: 150%;">
                Banco de Horas: <span style="font-weight: bold;"><?php echo $bhYear; ?></span><br/><br/>
                <input type='submit' value="Pr&oacute;ximo Passo >>" onclick="return validateFields();"/>
            </div>
            
        </div>
    </div> <!--CLOSE MAIN DIV -->
    
        <br/>
        <table class="tbl" style="min-width: 1000px;">
            <tr style="background-color: #61269e; text-align: center;">
<?php

    // get the column with the most value count
    $colCount = 0;
    
    for ($i = 0; $i < $rowCount; $i++){
        if (count($rowArr[$i]) > $colCount) $colCount = count($rowArr[$i]);
    }

    // create headders
    for($i = 0; $i < $colCount; $i++){
        
?>
                <td>
                    <select class="selCat" id="selCat<?php echo $i; ?>" name="cats[<?php echo $i; ?>]" onchange="selChanged(this);" onkeyup="selChanged(this);">
                        <option value="0" style="font-style: italic; background-color: white;">- Selecione -</option>
                        <option value="1" style="font-style: normal; background-color: cornflowerblue;"<?php if (isset($cats[$i]) && $cats[$i] == 1) echo ' selected="selected"'; ?>>Turma</option>
                        <option value="2" style="font-style: normal; background-color: cornflowerblue;"<?php if (isset($cats[$i]) && $cats[$i] == 2) echo ' selected="selected"'; ?>>Professor</option>
                    </select>
                </td>
<?php
        
    }

?>
            </tr>
<?php

    $bgcolor = null;

    for ($i = 0; $i < $rowCount; $i++){
        
        $bgcolor = ($bgcolor == '#c6c6c6' ? '#ffffff' : '#c6c6c6');
        
        echo '<tr style="background-color: ' . $bgcolor . '";>' . PHP_EOL;
        
        for($j = 0; $j < $colCount; $j++){
            
            echo '<td style="white-space: nowrap;">' . htmlentities($rowArr[$i][$j], 0, 'ISO-8859-1') . '</td>' . PHP_EOL;
            
        }
        
        echo '</tr>' . PHP_EOL;
        
    }

?>
        </table>

        <input type="hidden" name="tb" value="<?php echo $tbId; ?>"/>
        <input type="hidden" name="f" value="<?php echo $fileName; ?>"/>
        <input type="hidden" name="step" value="3"/>
        </form>
        
        <div class="overlay" id="overlay" onclick="hideHelpBox();"></div>
        <div class="helpBox" id="helpBox" style="width: 500px; height: 180px;">
            <div class="closeImg" onclick="hideHelpBox()"></div>
            <span style="font-weight: bold;">Ajuda - Importar Alunos</span>
            <hr>
            <ul style="line-height: 150%;">
                <li>Verifique na tabela abaixo se os dados do arquivo foram carregados corretamente.</li>
                <li>Selecione as categorias <span style="font-style: italic;">'Turma'</span> e <span style="font-style: italic;">'Professor'</span> em suas respectivas colunas.</li>
                <li>Click no bot&atilde;o <span style='font-style: italic;'>'Pr&oacute;ximo Passo'</span>.</li>
            </ul>
        </div>
        <div> <!--REOPEN MAIN DIV -->
<?php
    
}

?>