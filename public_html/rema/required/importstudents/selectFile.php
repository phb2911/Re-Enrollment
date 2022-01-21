<?php

function selectFile($errMsg, $campName){

?>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Importar Alunos - Passo 1/4</span>
            <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox(true);"/>
            <hr/>
            
            <div style="padding: 5px; line-height: 200%;">
                Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span><br/>
                Selecione o arquivo:<br/>
                <form action="importstudents.php" method="post" enctype="multipart/form-data">
                    <input type="file" name="fileToUpload" id="fileToUpload" style="width: 95%; border: #d1d1d1 solid 1px; padding: 10px; border-radius: 5px;"/><br/>
                    <input type="submit" value="Enviar" onclick="return validateFileUpload();"/><br/>
                </form>
                <span style="font-style: italic; color: red; display: <?php echo (isset($errMsg) ? 'inline' : 'none'); ?>;"><?php echo $errMsg; ?></span>
            </div>
            
        </div>
        
        <div class="overlay" id="overlay" onclick="showHelpBox(false);"></div>
        <div class="helpBox" id="helpBox" style="width: 500px; height: 260px;">
            <div class="closeImg" onclick="showHelpBox(false);"></div>
            <span style="font-weight: bold;">Ajuda - Selecionar arquivo para importação</span>
            <hr>
            Siga os seguintes passos:
            <ul style="line-height: 150%;">
                <li>Click no botão superior e selecione o arquivo exportado do SGY.</li>
                <li>Após selecionado, click no botão <span style='font-style: italic;'>'Enviar'</span>.</li>
            </ul>
            <span style="color: red;">Atenção:</span> O arquivo importado do SGY deve conter as seguintes informações:
            <ul>
                <li>Nome do aluno</li>
                <li>Nome do professor</li>
                <li>Turma que o aluno está matriculado</li>
                <li>Estágio referente à turma</li>
            </ul>
        </div>
        
<?php
    
}

?>