<?php

function selectFile(&$db, $errMsg){

    // fetch active banks
    $result = $db->query("SELECT `ID`, `Year` FROM `tb_banks` WHERE `Active` = 1 ORDER BY `Year`");
    
    $banks = array();
    
    while ($row = $result ->fetch_assoc()){
        $banks[$row['ID']] = $row['Year'];
    }
    
    $result->close();
    
    $bh = getPost('bh');
    
?>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Importar Turmas - Passo 1/3</span>
            <img src="../images/question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/>
            <hr/>
<?php if (count($banks)){ ?>
            <form action="importclasses.php?step=2" method="post" enctype="multipart/form-data">
            <div style="padding: 5px; line-height: 200%;">
                Banco de Horas: 
                <select id="selBH" name="tb">
                    <option value="0" style="font-style: italic;">- Selecione -</option>
                    <?php

                        foreach ($banks as $bid => $byear) {
                            echo '<option value="' . $bid . '" style="font-style: italic;"' . ($bh == $bid ? ' selected="selected"' : '') . '>' . $byear . '</option>' . PHP_EOL;
                        }

                    ?>
                </select>
                <br/>
                Selecione o arquivo:<br/>
                
                <input type="file" name="fileToUpload" id="fileToUpload" style="width: 95%; border: #d1d1d1 solid 1px; padding: 10px; border-radius: 5px;"/><br/>
                <input type="submit" value="Enviar" onclick="return validateFileUpload();"/><br/>
                
                <span style="font-style: italic; color: red; display: <?php echo (isset($errMsg) ? 'inline' : 'none'); ?>;"><?php echo $errMsg; ?></span>
            </div>
            </form>
<?php } else {?>
            <div style="padding: 5px; font-style: italic; color: red;">N&atilde;o h&aacute; nenhum banco de horas ativo no banco de dados.</div>
<?php } ?>
        </div>
        
        <div class="overlay" id="overlay" onclick="hideHelpBox();"></div>
        <div class="helpBox" id="helpBox" style="width: 500px; height: 260px;">
            <div class="closeImg" onclick="hideHelpBox();"></div>
            <span style="font-weight: bold;">Ajuda - Selecionar arquivo para importa&ccedil;&atilde;o</span>
            <hr>
            Siga os seguintes passos:
            <ul style="line-height: 150%;">
                <li>Selecione o Banco de Horas desejado.</li>
                <li>Click no bot&atilde;o superior e selecione o arquivo exportado do SGY.</li>
                <li>Ap&oacute;s selecionado, click no bot&atilde;o <span style='font-style: italic;'>'Enviar'</span>.</li>
            </ul>
            <span style="color: red;">Aten&ccedil;&atilde;o:</span> O arquivo importado do SGY deve conter as seguintes informa&ccedil;&otilde;es:
            <ul>
                <li>Nome da turma</li>
                <li>Professor titular da turma</li>
            </ul>
        </div>
        
<?php
    
}

?>